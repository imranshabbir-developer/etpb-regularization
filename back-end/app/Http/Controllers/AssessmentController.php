<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AssessmentComparable;
use App\Models\AssessmentRateInput;
use App\Models\AssessmentRound;
use App\Models\RateSource;
use App\Services\ArrearsService;
use App\Services\RentAssessmentService;
use App\Services\SettingService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Assessment of rent under Clause 10 of the Scheme 1977.
 *
 * The District Officer assesses "keeping in view the market rent and rent of
 * other properties in the vicinity in similar circumstances". Each source of
 * value is recorded as its own row so the determination stands on a visible
 * record; the DO's own figure is the operative one and cannot be saved without
 * written reasons, because Clause 10(i)(d) requires the fixation to be reasoned
 * after hearing.
 */
class AssessmentController extends Controller
{
    public function __construct(
        private readonly RentAssessmentService $rent,
        private readonly ArrearsService $arrears,
        private readonly WorkflowService $workflow,
        private readonly SettingService $settings,
    ) {
    }

    public function show(Request $request, Application $application): View
    {
        $this->authoriseDistrict($request, $application);

        $round = $application->rounds()
            ->with(['rateInputs.rateSource', 'comparables'])
            ->orderByDesc('round_no')
            ->first();

        $area = $application->property?->currentArea;

        return view('assessment.show', [
            'application' => $application->load(['applicant', 'property.district', 'possession']),
            'round'       => $round,
            'decision'    => $round
                ? DB::table('assessment_decisions')
                    ->where('assessment_round_id', $round->id)
                    ->where('is_superseded', false)
                    ->whereNull('deleted_at')
                    ->orderByDesc('decided_at')
                    ->first()
                : null,
            'rateSources' => RateSource::where('is_active', true)->orderBy('display_order')->get(),
            'areaSqft'    => $area?->area_sqft,
            'coveredSqft' => $area?->covered_area_sqft,
            'schedule'    => $round
                ? DB::table('rent_schedules')->where('assessment_round_id', $round->id)->orderBy('year')->get()
                : collect(),
            'milestones'  => $this->rent->milestoneTable($application->id),
            'defaults'    => [
                'base_date'      => $this->settings->date('assessment_base_date', '2006-07-01')?->toDateString(),
                'rate'           => $this->settings->decimal('enhancement_rate', '8.00'),
                'method'         => $this->settings->string('enhancement_method', 'COMPOUND'),
                'cycle'          => $this->settings->int('reassessment_cycle_years', 6),
            ],
        ]);
    }

    /**
     * Open an assessment round. Clause 10(i) fixes the base date at 01-07-2006;
     * the anchor may be moved but the reason is recorded on the round.
     */
    public function openRound(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseDistrict($request, $application);

        $data = $request->validate([
            'base_date'          => ['required', 'date'],
            'effective_from'     => ['required', 'date'],
            'enhancement_rate'   => ['required', 'numeric', 'between:0,100'],
            'enhancement_method' => ['required', Rule::in(['SIMPLE', 'COMPOUND'])],
            'round_type'         => ['required', Rule::in(['INITIAL', 'PERIODICAL', 'REVISION'])],
        ]);

        $next = (int) ($application->rounds()->max('round_no') ?? 0) + 1;

        $round = AssessmentRound::create([
            'application_id'           => $application->id,
            'property_id'              => $application->property_id,
            'round_no'                 => $next,
            'round_type'               => $data['round_type'],
            'base_date'                => $data['base_date'],
            'effective_from'           => $data['effective_from'],
            'enhancement_rate'         => $data['enhancement_rate'],
            'enhancement_method'       => $data['enhancement_method'],
            'reassessment_cycle_years' => $this->settings->int('reassessment_cycle_years', 6),
            'status'                   => 'DRAFT',
            'district_officer_id'      => $request->user()->id,
            'created_by'               => $request->user()->id,
        ]);

        return redirect()->route('assessment.show', $application)
            ->with('status', "Assessment round {$round->round_no} opened.");
    }

    public function storeRateInput(Request $request, AssessmentRound $round): RedirectResponse
    {
        $this->authoriseDistrict($request, $round->application);

        $data = $request->validate([
            'rate_source_id'      => ['required', 'integer', 'exists:rate_sources,id'],
            'rate_value'          => ['required', 'numeric', 'min:0'],
            'rate_unit'           => ['required', Rule::in([
                'PER_SQFT_PER_MONTH', 'PER_MARLA_PER_MONTH', 'PER_MONTH_TOTAL',
                'PER_SQFT_VALUE', 'PER_MARLA_VALUE', 'TOTAL_VALUE',
            ])],
            'notification_no'     => ['nullable', 'string', 'max:120'],
            'notification_date'   => ['nullable', 'date'],
            'valuator_name'       => ['nullable', 'string', 'max:150'],
            'valuator_licence_no' => ['nullable', 'string', 'max:80'],
            'report_no'           => ['nullable', 'string', 'max:120'],
            'report_date'         => ['nullable', 'date'],
            'remarks'             => ['nullable', 'string', 'max:2000'],
        ]);

        $source = RateSource::findOrFail($data['rate_source_id']);

        if ($source->requires_reference_no && blank($data['notification_no'] ?? null) && blank($data['report_no'] ?? null)) {
            return back()->with('error', sprintf(
                'A %s rate must carry its notification or report number, otherwise it cannot be relied on.',
                $source->name
            ))->withInput();
        }

        AssessmentRateInput::create($data + [
            'assessment_round_id' => $round->id,
            'created_by'          => $request->user()->id,
        ]);

        return back()->with('status', $source->name . ' recorded.');
    }

    public function destroyRateInput(Request $request, AssessmentRateInput $input): RedirectResponse
    {
        $round = $input->round;
        $this->authoriseDistrict($request, $round->application);
        $this->refuseIfDetermined($round);

        $input->delete();

        return back()->with('status', 'Rate input removed.');
    }

    /**
     * Prevailing market rent of adjoining properties in similar circumstances —
     * the test named in Clause 10(i)(a) and defined in Clause 2(i)(l).
     */
    public function storeComparable(Request $request, AssessmentRound $round): RedirectResponse
    {
        $this->authoriseDistrict($request, $round->application);

        $data = $request->validate([
            'property_description' => ['required', 'string', 'max:255'],
            'address'              => ['nullable', 'string', 'max:255'],
            'area_sqft'            => ['nullable', 'numeric', 'min:0'],
            'monthly_rent'         => ['required', 'numeric', 'min:0'],
            'usage_type'           => ['required', Rule::in([
                'RESIDENTIAL', 'COMMERCIAL', 'RESIDENTIAL_CUM_COMMERCIAL', 'OTHER',
            ])],
            'distance_meters'      => ['nullable', 'numeric', 'min:0'],
            'information_source'   => ['nullable', 'string', 'max:200'],
            'observed_on'          => ['nullable', 'date'],
            'remarks'              => ['nullable', 'string', 'max:2000'],
        ]);

        AssessmentComparable::create($data + [
            'assessment_round_id' => $round->id,
            'created_by'          => $request->user()->id,
        ]);

        return back()->with('status', 'Comparable property recorded.');
    }

    public function destroyComparable(Request $request, AssessmentComparable $comparable): RedirectResponse
    {
        $round = $comparable->round;
        $this->authoriseDistrict($request, $round->application);
        $this->refuseIfDetermined($round);

        $comparable->delete();

        return back()->with('status', 'Comparable removed.');
    }

    /**
     * Record the proposed assessment, which is what goes on public display and
     * into the notice under Clause 10(i)(b) and (c).
     */
    public function propose(Request $request, AssessmentRound $round): RedirectResponse
    {
        $this->authoriseDistrict($request, $round->application);

        $data = $request->validate([
            'proposed_monthly_rent' => ['required', 'numeric', 'min:0'],
        ]);

        $round->update([
            'proposed_monthly_rent' => $data['proposed_monthly_rent'],
            'status'                => 'PROPOSED',
            'updated_by'            => $request->user()->id,
        ]);

        $application = $round->application;

        if ($application->status === WorkflowService::SITE_INSPECTION) {
            try {
                $this->workflow->transition(
                    $application->id,
                    WorkflowService::ASSESSMENT_PROPOSED,
                    $request->user()->id,
                    $request->user()->primaryRole()?->code,
                    'Assessment proposed at Rs. ' . number_format((float) $data['proposed_monthly_rent'], 2) . ' per month.',
                    $request->ip(),
                );
            } catch (Throwable $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return back()->with('status', 'Proposed assessment recorded. Issue the notice next.');
    }

    /**
     * The operative determination. Clause 10(i)(d) requires the rent to be fixed
     * after an opportunity of hearing and for reasons, so reasons are mandatory
     * and a substantive length is enforced — a one-word "approved" is not a reason.
     *
     * Generating the schedule and the arrears ledger is part of the same
     * transaction, because a determination without a ledger is not actionable.
     */
    public function determine(Request $request, AssessmentRound $round): RedirectResponse
    {
        $this->authoriseDistrict($request, $round->application);

        $data = $request->validate([
            'determined_monthly_rent' => ['required', 'numeric', 'min:0'],
            'rate_per_sqft'           => ['nullable', 'numeric', 'min:0'],
            'reasons'                 => ['required', 'string', 'min:40', 'max:8000'],
            'objections_considered'   => ['nullable', 'string', 'max:8000'],
        ], [
            'reasons.required' => 'Clause 10(i)(d) requires the fixation of rent to be reasoned. Record the reasons.',
            'reasons.min'      => 'The reasons must be substantive — set out what was considered and why this figure follows.',
        ]);

        $application = $round->application;
        $user = $request->user();

        try {
            DB::transaction(function () use ($round, $data, $user) {
                // A revision supersedes rather than overwrites: the previous
                // determination stays on the record.
                DB::table('assessment_decisions')
                    ->where('assessment_round_id', $round->id)
                    ->where('is_superseded', false)
                    ->update(['is_superseded' => true, 'updated_at' => now()]);

                DB::table('assessment_decisions')->insert([
                    'assessment_round_id'     => $round->id,
                    'determined_monthly_rent' => $data['determined_monthly_rent'],
                    'rate_per_sqft'           => $data['rate_per_sqft'] ?? null,
                    'reasons'                 => $data['reasons'],
                    'objections_considered'   => $data['objections_considered'] ?? null,
                    'decided_by'              => $user->id,
                    'decided_at'              => now(),
                    'is_superseded'           => false,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);

                $round->update([
                    'determined_monthly_rent' => $data['determined_monthly_rent'],
                    'status'                  => 'DECIDED',
                    'completed_at'            => now(),
                    'updated_by'              => $user->id,
                ]);
            });

            $this->rent->generateSchedule($round->id);
            $this->arrears->generate($application->id);

            DB::table('applications')->where('id', $application->id)->update([
                'assessed_monthly_rent' => $data['determined_monthly_rent'],
                'updated_at'            => now(),
            ]);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        // Advance the case if the due-process guards are satisfied.
        $application->refresh();
        $messages = [];

        foreach ([WorkflowService::RENT_FIXED, WorkflowService::ARREARS_COMPUTED] as $target) {
            $check = $this->workflow->check($application->id, $target);
            if (! $check['allowed']) {
                $messages = $check['reasons'];
                break;
            }
            $this->workflow->transition(
                $application->id,
                $target,
                $user->id,
                $user->primaryRole()?->code,
                'Rent determined and ledger generated.',
                $request->ip(),
            );
            $application->refresh();
        }

        $summary = $this->arrears->summary($application->id);

        return redirect()->route('assessment.show', $application)
            ->with('status', sprintf(
                'Rent fixed at Rs. %s per month. Arrears of Rs. %s assessed across %d year(s).',
                number_format((float) $data['determined_monthly_rent'], 2),
                number_format((float) $summary['total_due'], 2),
                DB::table('arrears_ledger')->where('application_id', $application->id)->count(),
            ))
            ->with($messages ? 'warning' : 'ignore', $messages ? implode(' ', $messages) : null);
    }

    /**
     * Project a rent schedule without saving, so the officer can see what a
     * figure implies across the whole arrears period before committing to it.
     */
    public function preview(Request $request, AssessmentRound $round): JsonResponse
    {
        $data = $request->validate([
            'monthly_rent' => ['required', 'numeric', 'min:0'],
        ]);

        $possession = DB::table('possession_details')
            ->where('application_id', $round->application_id)
            ->whereNull('deleted_at')
            ->first();

        if (! $possession) {
            return response()->json(['ok' => false, 'message' => 'Possession details are missing.'], 422);
        }

        $anchorYear = $this->rent->rentYearOf(\Illuminate\Support\Carbon::parse($round->effective_from));
        $startYear  = $this->rent->rentYearOf(\Illuminate\Support\Carbon::parse($possession->arrears_from));
        $endYear    = $this->rent->rentYearOf(\Illuminate\Support\Carbon::today());

        $rows = [];
        $total = '0';

        foreach ($this->settings->milestoneYears() as $year) {
            if ($year < $startYear || $year > $endYear) {
                continue;
            }
            $monthly = $this->rent->rentForYear(
                (string) $data['monthly_rent'], $anchorYear, $year,
                (string) $round->enhancement_rate, $round->enhancement_method,
            );
            $rows[] = ['year' => $year, 'monthly' => number_format((float) $monthly, 2)];
        }

        for ($y = $startYear; $y <= $endYear; $y++) {
            $monthly = $this->rent->rentForYear(
                (string) $data['monthly_rent'], $anchorYear, $y,
                (string) $round->enhancement_rate, $round->enhancement_method,
            );
            $total = bcadd($total, bcmul($monthly, '12', 2), 2);
        }

        return response()->json([
            'ok'          => true,
            'milestones'  => $rows,
            'years'       => $endYear - $startYear + 1,
            'total'       => number_format((float) $total, 2),
            'method'      => $round->enhancement_method,
            'rate'        => $round->enhancement_rate,
            'anchor_year' => $anchorYear,
        ]);
    }

    // ---- helpers ---------------------------------------------------------

    private function refuseIfDetermined(AssessmentRound $round): void
    {
        if ($round->status === 'DECIDED') {
            abort(409, 'This round has already been decided. Open a revision instead of editing the record it rests on.');
        }
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
