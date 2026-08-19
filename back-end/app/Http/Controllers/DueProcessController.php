<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Hearing;
use App\Models\Objection;
use App\Models\PublicNotice;
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
 * Due process under Clause 10(i)(b)–(d).
 *
 * The proposed assessment is made openly available; notice is given to the
 * tenant and the general public; fifteen days are allowed from receipt for
 * objections; and the rent is fixed only after an opportunity of hearing to
 * the tenant and to any objector.
 *
 * Notices, objections and hearings are one sequence rather than three
 * independent registers, so they are handled together.
 */
class DueProcessController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly WorkflowService $workflow,
    ) {
    }

    public function index(Request $request, Application $application): View
    {
        $this->authoriseDistrict($request, $application);

        $application->load([
            'notices' => fn ($q) => $q->orderByDesc('issued_on'),
            'objections.notice',
            'hearings' => fn ($q) => $q->orderByDesc('scheduled_for'),
            'applicant', 'property',
        ]);

        $decisions = DB::table('objection_decisions')
            ->whereIn('objection_id', $application->objections->pluck('id'))
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('objection_id');

        $round = $application->rounds()->orderByDesc('round_no')->first();

        return view('due-process.index', [
            'application'   => $application,
            'round'         => $round,
            'decisions'     => $decisions,
            'objectionDays' => $this->settings->int('objection_window_days', 15),
            'slaDays'       => $this->settings->int('assessment_sla_days', 60),
        ]);
    }

    /**
     * Issue a notice. The objection deadline is derived, not typed: Clause
     * 10(i)(c) allows fifteen days from receipt, so the clock runs from the
     * date of service where that is known and from issue otherwise.
     */
    public function storeNotice(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseDistrict($request, $application);

        $data = $request->validate([
            'notice_type'           => ['required', Rule::in(['PUBLIC', 'TENANT', 'OBJECTOR', 'SHOW_CAUSE', 'HEARING'])],
            'issued_on'             => ['required', 'date'],
            'served_on'             => ['nullable', 'date', 'after_or_equal:issued_on'],
            'service_mode'          => ['required', Rule::in([
                'HAND', 'REGISTERED_POST', 'COURIER', 'NEWSPAPER',
                'NOTICE_BOARD', 'AFFIXATION', 'EMAIL', 'SMS',
            ])],
            'newspaper_name'        => ['nullable', 'string', 'max:150'],
            'published_on'          => ['nullable', 'date'],
            'publication_reference' => ['nullable', 'string', 'max:150'],
            'subject'               => ['nullable', 'string', 'max:500'],
            'body'                  => ['nullable', 'string', 'max:20000'],
        ]);

        $window = $this->settings->int('objection_window_days', 15);
        $clockFrom = Carbon::parse($data['served_on'] ?? $data['issued_on']);

        $round = $application->rounds()->orderByDesc('round_no')->first();

        $notice = PublicNotice::create($data + [
            'application_id'      => $application->id,
            'assessment_round_id' => $round?->id,
            'notice_no'           => $this->nextNoticeNo($application),
            'objection_deadline'  => $clockFrom->copy()->addDays($window)->toDateString(),
            'status'              => $data['served_on'] ?? false ? 'SERVED' : 'ISSUED',
            'issued_by'           => $request->user()->id,
        ]);

        // The first notice starts the 60-day assessment clock of Clause 10(i)(e).
        if ($application->status === WorkflowService::ASSESSMENT_PROPOSED) {
            try {
                $this->workflow->transition(
                    $application->id, WorkflowService::NOTICE_ISSUED,
                    $request->user()->id, $request->user()->primaryRole()?->code,
                    "Notice {$notice->notice_no} issued.", $request->ip(),
                );
                $this->workflow->transition(
                    $application->id, WorkflowService::OBJECTION_WINDOW,
                    $request->user()->id, $request->user()->primaryRole()?->code,
                    "Objection window open until {$notice->objection_deadline}.", $request->ip(),
                );
            } catch (Throwable $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        $slaDays = $this->settings->int('assessment_sla_days', 60);

        if ($round && ! $round->first_notice_date) {
            $round->update([
                'first_notice_date'   => $data['issued_on'],
                'completion_due_date' => Carbon::parse($data['issued_on'])
                    ->addDays($slaDays)->toDateString(),
                'status'              => 'NOTICE_ISSUED',
            ]);
        }

        // The transition stamps the clock with today's date as a safe default.
        // The notice itself is the authority for when the 60 days of Clause
        // 10(i)(e) actually began: a notice entered into the system a week
        // after it issued must not buy the office a week of extra time.
        $application->refresh();

        if ($application->first_notice_date === null
            || Carbon::parse($data['issued_on'])->lt(Carbon::parse($application->first_notice_date))) {
            $application->forceFill([
                'first_notice_date'   => $data['issued_on'],
                'assessment_due_date' => Carbon::parse($data['issued_on'])
                    ->addDays($slaDays)->toDateString(),
            ])->save();
        }

        return back()->with('status', sprintf(
            'Notice %s issued. Objections may be filed until %s.',
            $notice->notice_no,
            Carbon::parse($notice->objection_deadline)->format('d-m-Y'),
        ));
    }

    /**
     * Record an objection. A late objection is recorded rather than refused —
     * whether to entertain it is the District Officer's decision on the merits,
     * not something the intake form should pre-empt.
     */
    public function storeObjection(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseDistrict($request, $application);

        $data = $request->validate([
            'public_notice_id'         => ['nullable', 'integer', 'exists:public_notices,id'],
            'objector_name'            => ['required', 'string', 'max:150'],
            'objector_parentage'       => ['nullable', 'string', 'max:150'],
            'objector_cnic'            => ['nullable', 'digits:13'],
            'objector_address'         => ['nullable', 'string', 'max:500'],
            'objector_contact'         => ['nullable', 'string', 'max:20'],
            'relationship_to_property' => ['nullable', 'string', 'max:150'],
            'plea'                     => ['required', 'string', 'min:20', 'max:20000'],
            'filed_on'                 => ['required', 'date'],
        ], [
            'plea.required' => 'The objection must record the plea taken, in the objector\'s own terms.',
            'plea.min'      => 'Record the plea in full — it forms part of the case file and must be answerable.',
        ]);

        // A nullable field that was not submitted is absent from the validated
        // array entirely, not present-and-null.
        $notice = ! empty($data['public_notice_id'])
            ? PublicNotice::find($data['public_notice_id'])
            : $application->notices()->orderByDesc('issued_on')->first();

        $withinTime = $notice
            ? Carbon::parse($data['filed_on'])->lte(Carbon::parse($notice->objection_deadline))
            : true;

        Objection::create($data + [
            'application_id'   => $application->id,
            'public_notice_id' => $notice?->id,
            'objection_no'     => $this->nextObjectionNo($application),
            'is_within_time'   => $withinTime,
            'status'           => 'FILED',
            'created_by'       => $request->user()->id,
        ]);

        return back()->with('status', $withinTime
            ? 'Objection recorded within time.'
            : 'Objection recorded. It was filed after the 15-day window, which the District Officer must address when deciding it.');
    }

    /**
     * Decide an objection. Reasons are mandatory: an objection disposed of
     * without reasons leaves the eventual fixation of rent open to challenge.
     */
    public function decideObjection(Request $request, Objection $objection): RedirectResponse
    {
        $this->authoriseDistrict($request, $objection->application);

        $data = $request->validate([
            'decision'    => ['required', Rule::in(['ACCEPTED', 'REJECTED', 'PARTIALLY_ACCEPTED', 'WITHDRAWN'])],
            'reasons'     => ['required', 'string', 'min:30', 'max:8000'],
            'rent_impact' => ['nullable', 'numeric'],
            'hearing_id'  => ['nullable', 'integer', 'exists:hearings,id'],
        ], [
            'reasons.required' => 'An objection must be decided for reasons.',
            'reasons.min'      => 'Set out why the plea succeeds or fails.',
        ]);

        DB::transaction(function () use ($objection, $data, $request) {
            DB::table('objection_decisions')->insert($data + [
                'objection_id' => $objection->id,
                'decided_by'   => $request->user()->id,
                'decided_at'   => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $objection->update(['status' => 'DECIDED']);
        });

        return back()->with('status', 'Objection ' . $objection->objection_no . ' decided.');
    }

    public function storeHearing(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseDistrict($request, $application);

        $data = $request->validate([
            'scheduled_for'         => ['required', 'date'],
            'venue'                 => ['nullable', 'string', 'max:200'],
            'presiding_designation' => ['nullable', 'string', 'max:120'],
            'parties_summoned'      => ['nullable', 'string', 'max:2000'],
        ]);

        $round = $application->rounds()->orderByDesc('round_no')->first();

        Hearing::create([
            'application_id'        => $application->id,
            'assessment_round_id'   => $round?->id,
            'hearing_no'            => $this->nextHearingNo($application),
            'scheduled_for'         => $data['scheduled_for'],
            'venue'                 => $data['venue'] ?? null,
            'presiding_officer_id'  => $request->user()->id,
            'presiding_designation' => $data['presiding_designation'] ?? $request->user()->designation,
            'parties_summoned'      => array_values(array_filter(array_map(
                'trim', explode("\n", (string) ($data['parties_summoned'] ?? ''))
            ))),
            'status'                => 'SCHEDULED',
            'created_by'            => $request->user()->id,
        ]);

        if ($application->status === WorkflowService::OBJECTION_WINDOW) {
            try {
                $this->workflow->transition(
                    $application->id, WorkflowService::HEARING,
                    $request->user()->id, $request->user()->primaryRole()?->code,
                    'Hearing scheduled.', $request->ip(),
                );
            } catch (Throwable $e) {
                // Scheduling a hearing is useful even if the case is not ready
                // to move; the hearing itself is already recorded.
                return back()->with('warning', $e->getMessage());
            }
        }

        return back()->with('status', 'Hearing scheduled.');
    }

    public function recordHearing(Request $request, Hearing $hearing): RedirectResponse
    {
        $this->authoriseDistrict($request, $hearing->application);

        $data = $request->validate([
            'proceedings'        => ['required', 'string', 'min:20', 'max:20000'],
            'attendance'         => ['nullable', 'string', 'max:2000'],
            'status'             => ['required', Rule::in(['HELD', 'ADJOURNED', 'CANCELLED'])],
            'adjourned_to'       => ['nullable', 'date', 'required_if:status,ADJOURNED'],
            'adjournment_reason' => ['nullable', 'string', 'max:2000'],
        ], [
            'proceedings.required' => 'Record what happened at the hearing — it is the evidence that an opportunity of hearing was given.',
        ]);

        $hearing->update([
            'proceedings'        => $data['proceedings'],
            'attendance'         => array_values(array_filter(array_map(
                'trim', explode("\n", (string) ($data['attendance'] ?? ''))
            ))),
            'status'             => $data['status'],
            'adjourned_to'       => $data['adjourned_to'] ?? null,
            'adjournment_reason' => $data['adjournment_reason'] ?? null,
        ]);

        return back()->with('status', 'Hearing proceedings recorded.');
    }

    // ---- numbering -------------------------------------------------------

    private function nextNoticeNo(Application $application): string
    {
        $n = $application->notices()->withTrashed()->count() + 1;

        return sprintf('%s/NOT/%02d', $application->application_no, $n);
    }

    private function nextObjectionNo(Application $application): string
    {
        $n = $application->objections()->withTrashed()->count() + 1;

        return sprintf('%s/OBJ/%02d', $application->application_no, $n);
    }

    private function nextHearingNo(Application $application): string
    {
        $n = $application->hearings()->withTrashed()->count() + 1;

        return sprintf('%s/HRG/%02d', $application->application_no, $n);
    }

    private function authoriseDistrict(Request $request, Application $application): void
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
