<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Approval;
use App\Services\ArrearsService;
use App\Services\SettingService;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Head 3, last row — "Remarks / approval by Administrator".
 *
 * Clause 3(ii)(d): "the regularization shall be approved by the Administrator
 * within one month **after recording reasons**." Both halves of that are
 * enforced here — reasons are mandatory and of substance, and the one-month
 * clock is measured and reported, including when it has been breached.
 *
 * The Administrator is shown the whole basis of the decision on one screen —
 * eligibility, evidence, the rent fixed and the reasons for it, objections and
 * how they went, arrears and whether they are cleared — because an approval
 * recorded without sight of those is not a reasoned approval.
 */
class ApprovalController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflow,
        private readonly ArrearsService $arrears,
        private readonly SettingService $settings,
    ) {
    }

    /** The Administrator's queue: everything waiting on a decision. */
    public function queue(Request $request): View
    {
        $user = $request->user();

        $applications = Application::query()
            ->visibleTo($user)
            ->with(['applicant:id,full_name,parentage_name,parentage_type', 'property:id,property_no,sub_unit_no',
                    'district:id,name', 'districtOfficer:id,name'])
            ->where('status', WorkflowService::PENDING_ADMIN_APPROVAL)
            ->orderBy('admin_approval_due_date')
            ->paginate(25);

        return view('approvals.queue', [
            'applications' => $applications,
            'slaDays'      => $this->settings->int('admin_approval_sla_days', 30),
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        $this->authoriseView($request, $application);

        $application->load([
            'applicant', 'property.district', 'property.currentArea', 'possession',
            'documents.documentType', 'feePayments', 'objections', 'litigations',
            'approvals', 'nominees', 'districtOfficer:id,name',
        ]);

        $round = $application->rounds()->orderByDesc('round_no')->first();

        $decision = $round
            ? DB::table('assessment_decisions')
                ->where('assessment_round_id', $round->id)
                ->where('is_superseded', false)
                ->whereNull('deleted_at')
                ->orderByDesc('decided_at')->first()
            : null;

        $objectionDecisions = DB::table('objection_decisions')
            ->whereIn('objection_id', $application->objections->pluck('id'))
            ->whereNull('deleted_at')->get()->keyBy('objection_id');

        return view('approvals.show', [
            'application'        => $application,
            'round'              => $round,
            'decision'           => $decision,
            'objectionDecisions' => $objectionDecisions,
            'arrears'            => $this->arrears->summary($application->id),
            'clearance'          => $this->arrears->clearanceStatus($application->id),
            'sla'                => $application->adminApprovalSla(),
            'canApprove'         => $this->workflow->check($application->id, WorkflowService::APPROVED),
            'milestones'         => app(\App\Services\RentAssessmentService::class)
                                      ->milestoneTable($application->id),
        ]);
    }

    /**
     * Record the Administrator's decision.
     *
     * The approval row is written first and the transition attempted second,
     * because the guard on APPROVED reads that row back — Clause 3(ii)(d) is
     * satisfied by a recorded, reasoned approval, not by the act of clicking.
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseView($request, $application);

        $data = $request->validate([
            'action'     => ['required', Rule::in(['APPROVE', 'REJECT', 'REMAND'])],
            'reasons'    => ['required', 'string', 'min:40', 'max:8000'],
            'conditions' => ['nullable', 'string', 'max:4000'],
            'order_reference' => ['nullable', 'string', 'max:120'],
        ], [
            'reasons.required' => 'Clause 3(ii)(d) requires the Administrator to record reasons. This field cannot be left blank.',
            'reasons.min'      => 'The reasons must be substantive — set out what was considered and why the decision follows.',
        ]);

        $user = $request->user();
        $due  = $application->admin_approval_due_date;

        $daysTaken = $application->rent_fixed_at
            ? (int) Carbon::parse($application->rent_fixed_at)->diffInDays(Carbon::today())
            : null;

        DB::transaction(function () use ($application, $data, $user, $due, $daysTaken) {
            Approval::create([
                'application_id'  => $application->id,
                'level'           => 'ADMINISTRATOR',
                'action'          => $data['action'] === 'APPROVE' ? 'APPROVE'
                                     : ($data['action'] === 'REJECT' ? 'REJECT' : 'REMAND'),
                'reasons'         => $data['reasons'],
                'conditions'      => $data['conditions'] ?? null,
                'acted_by'        => $user->id,
                'acted_at'        => now(),
                'due_by'          => $due,
                'is_within_sla'   => $due === null || Carbon::today()->lte(Carbon::parse($due)),
                'days_taken'      => $daysTaken,
                'order_reference' => $data['order_reference'] ?? null,
            ]);
        });

        $target = match ($data['action']) {
            'APPROVE' => WorkflowService::APPROVED,
            'REJECT'  => WorkflowService::REJECTED,
            'REMAND'  => WorkflowService::REMANDED,
        };

        try {
            $this->workflow->transition(
                $application->id, $target, $user->id, $user->primaryRole()?->code,
                $data['reasons'], $request->ip(),
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($data['action'] === 'REJECT') {
            $application->forceFill(['rejection_reason' => $data['reasons']])->save();
        }

        $late = $due && Carbon::today()->gt(Carbon::parse($due));

        return redirect()->route('approvals.show', $application)->with(
            'status',
            match ($data['action']) {
                'APPROVE' => 'Regularization approved under Clause 3(ii)(d).'
                             . ($late ? ' Recorded as decided beyond the one-month limit.' : ''),
                'REJECT'  => 'Application rejected, with reasons recorded.',
                'REMAND'  => 'Application remanded to the District Officer.',
            },
        );
    }

    private function authoriseView(Request $request, Application $application): void
    {
        $user = $request->user();

        if ($user->hasPermission('applications.view_all')) {
            return;
        }
        if ($user->hasPermission('applications.view_district')
            && (int) $application->district_id === (int) $user->district_id) {
            return;
        }

        abort(403, 'This application is outside your jurisdiction.');
    }
}
