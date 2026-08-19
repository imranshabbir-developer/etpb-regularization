<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\ArrearsService;
use App\Services\ReportDataService;
use App\Services\SettingService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The home screen, which is a different thing depending on who signed in.
 *
 * A member of the public wants one question answered — what is happening with
 * my application, and is anything waiting on me. An officer wants their work
 * queue. The Chairman wants performance. Showing all three to everyone would
 * make the screen useless to all of them, so the controller picks a view
 * rather than piling conditionals into one template.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly ArrearsService $arrears,
        private readonly SettingService $settings,
        private readonly ReportDataService $data,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isApplicant()) {
            return $this->applicantHome($request);
        }

        // Chairman and above see performance first; everyone else sees work.
        if ($user->hasPermission('reports.executive') && ! $user->hasPermission('applications.scrutinise')) {
            return $this->executiveHome($request);
        }

        return $this->officerHome($request);
    }

    // ---- the public ---------------------------------------------------------

    private function applicantHome(Request $request): View
    {
        $user = $request->user();

        $applications = Application::query()
            ->with(['property:id,property_no,sub_unit_no', 'district:id,name', 'possession'])
            ->whereHas('applicant', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->get();

        // What, if anything, is waiting on the applicant rather than the office.
        $actions = [];
        foreach ($applications as $app) {
            if ($app->status === WorkflowService::DRAFT) {
                $actions[] = [
                    'app'   => $app,
                    'tone'  => 'warn',
                    'title' => 'Finish this application',
                    'body'  => 'It has not been submitted yet, so the department has not seen it.',
                    'cta'   => 'Continue',
                    'route' => route('apply.evidence', $app),
                ];
            } elseif ($app->status === WorkflowService::RETURNED_DEFICIENT) {
                $actions[] = [
                    'app'   => $app,
                    'tone'  => 'danger',
                    'title' => 'The office needs something from you',
                    'body'  => $app->status_remarks ?: 'A document is missing or unclear.',
                    'cta'   => 'Open',
                    'route' => route('documents.index', $app),
                ];
            } elseif ($app->payment_status === 'PENDING') {
                $actions[] = [
                    'app'   => $app,
                    'tone'  => 'warn',
                    'title' => 'Your Rs. ' . number_format((float) $this->settings->decimal('processing_fee', '5000.00'), 0) . ' deposit is not confirmed',
                    'body'  => 'Nothing moves until Accounts confirms it with the bank.',
                    'cta'   => 'Deposit',
                    'route' => route('fee.index', $app),
                ];
            } elseif ((float) $app->arrears_balance > 0) {
                $actions[] = [
                    'app'   => $app,
                    'tone'  => 'warn',
                    'title' => 'Rs. ' . number_format((float) $app->arrears_balance, 0) . ' is outstanding',
                    'body'  => 'Arrears must be cleared, or spread over instalments, before approval.',
                    'cta'   => 'See the ledger',
                    'route' => route('arrears.index', $app),
                ];
            }
        }

        return view('dashboard.applicant', [
            'applications' => $applications,
            'actions'      => $actions,
            'fee'          => $this->settings->decimal('processing_fee', '5000.00'),
            'cutoff'       => $this->settings->date('possession_cutoff_date', '2009-12-31'),
        ]);
    }

    // ---- officers ------------------------------------------------------------

    private function officerHome(Request $request): View
    {
        $user = $request->user();
        $scope = fn () => Application::query()->visibleTo($user);
        $today = now()->toDateString();

        // Each tile is a piece of work, and clicking it goes to that work.
        $work = [];

        if ($user->hasPermission('fee.verify')) {
            $work[] = [
                'label' => 'Deposits to confirm',
                'value' => $scope()->where('payment_status', 'PENDING')
                             ->whereHas('feePayments', fn ($q) => $q->where('status', 'PENDING'))->count(),
                'sub'   => 'nothing proceeds until confirmed',
                'tone'  => 'is-warn',
                'route' => route('queue.scrutiny'),
            ];
        }

        if ($user->hasPermission('applications.scrutinise')) {
            $work[] = [
                'label' => 'Waiting for scrutiny',
                'value' => $scope()->where('payment_status', 'PAID')
                             ->whereIn('status', [WorkflowService::FEE_VERIFICATION, WorkflowService::SCRUTINY])
                             ->count(),
                'sub'   => 'paid, and ready to examine',
                'tone'  => '',
                'route' => route('queue.scrutiny'),
            ];
        }

        if ($user->hasPermission('assessment.propose')) {
            $overdue = $scope()->open()
                ->whereNotNull('assessment_due_date')->whereNull('rent_fixed_at')
                ->whereDate(DB::raw('COALESCE(assessment_extended_to, assessment_due_date)'), '<', $today)
                ->count();

            $work[] = [
                'label' => 'Assessments in hand',
                'value' => $scope()->open()->whereIn('status', [
                    WorkflowService::ASSESSMENT_PROPOSED, WorkflowService::NOTICE_ISSUED,
                    WorkflowService::OBJECTION_WINDOW, WorkflowService::HEARING,
                ])->count(),
                'sub'   => $overdue > 0 ? $overdue . ' past the 60-day limit' : 'all within the 60-day limit',
                'tone'  => $overdue > 0 ? 'is-danger' : '',
                'route' => route('queue.assessment'),
            ];
        }

        if ($user->hasPermission('objections.decide')) {
            $open = DB::table('objections as o')
                ->join('applications as a', 'a.id', '=', 'o.application_id')
                ->whereIn('o.status', ['FILED', 'UNDER_HEARING'])
                ->whereNull('o.deleted_at')->whereNull('a.deleted_at')
                ->when(! $user->hasPermission('applications.view_all'),
                       fn ($q) => $q->where('a.district_id', $user->district_id))
                ->count();

            $work[] = [
                'label' => 'Objections to decide',
                'value' => $open,
                'sub'   => 'rent cannot be fixed until decided',
                'tone'  => $open > 0 ? 'is-warn' : '',
                'route' => route('queue.objections'),
            ];
        }

        if ($user->hasPermission('approvals.administrator')) {
            $overdue = $scope()->where('status', WorkflowService::PENDING_ADMIN_APPROVAL)
                ->whereDate('admin_approval_due_date', '<', $today)->count();

            $work[] = [
                'label' => 'Awaiting your approval',
                'value' => $scope()->where('status', WorkflowService::PENDING_ADMIN_APPROVAL)->count(),
                'sub'   => $overdue > 0 ? $overdue . ' past the one-month limit' : 'within the one-month limit',
                'tone'  => $overdue > 0 ? 'is-danger' : '',
                'route' => route('queue.approvals'),
            ];
        }

        if ($user->hasPermission('arrears.view')) {
            $work[] = [
                'label' => 'Arrears outstanding',
                'value' => 'Rs. ' . number_format((float) ($scope()->sum('arrears_balance') ?: 0), 0),
                'sub'   => 'across all open cases',
                'tone'  => 'is-gold',
                'route' => route('queue.arrears'),
            ];
        }

        $recent = $scope()
            ->with(['applicant:id,full_name', 'property:id,property_no,sub_unit_no', 'district:id,name'])
            ->orderByDesc('updated_at')->limit(8)->get();

        return view('dashboard.officer', [
            'work'     => $work,
            'recent'   => $recent,
            'byStatus' => $scope()->select('status', DB::raw('COUNT(*) as total'))
                            ->groupBy('status')->pluck('total', 'status'),
            'labels'   => WorkflowService::LABELS,
        ]);
    }

    // ---- Chairman and above --------------------------------------------------

    private function executiveHome(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.executive', [
            'headline'    => $this->data->headline($user),
            'performance' => $this->data->performance($user),
            'byDistrict'  => $this->data->byDistrict($user),
            'monthly'     => $this->data->monthlyIntake($user),
            'objections'  => $this->data->objections(),
            'recent'      => Application::query()->visibleTo($user)
                                ->with(['applicant:id,full_name', 'district:id,name'])
                                ->orderByDesc('updated_at')->limit(6)->get(),
        ]);
    }
}
