<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\District;
use App\Services\ArrearsService;
use App\Services\RentAssessmentService;
use App\Services\ReportDataService;
use App\Services\ReportExportService;
use App\Services\SettingService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Head 6 of the requirements, in four shapes:
 *
 *   glimpse    a single page for the Chairman and above — how the scheme is
 *              performing, no case detail
 *   executive  the consolidated / master report for higher authorities
 *   deep       one application, every element, for the case file
 *   registers  routine operational lists, sorted and totalled
 *
 * Each is available on screen and as PDF, MS Word or Excel, because the same
 * report is filed, amended and totalled by three different people.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ArrearsService $arrears,
        private readonly RentAssessmentService $rent,
        private readonly SettingService $settings,
        private readonly ReportDataService $data,
        private readonly ReportExportService $export,
    ) {
    }

    /**
     * The Chairman's glimpse: performance at a glance, deliberately short.
     */
    public function glimpse(Request $request): View|StreamedResponse
    {
        $user = $request->user();
        $districtId = $request->integer('district') ?: null;

        $payload = [
            'headline'     => $this->data->headline($user, $districtId),
            'performance'  => $this->data->performance($user, $districtId),
            'byDistrict'   => $this->data->byDistrict($user, $districtId),
            'monthly'      => $this->data->monthlyIntake($user, $districtId),
            'objections'   => $this->data->objections($districtId),
            'districts'    => District::orderBy('name')->get(['id', 'name']),
            'districtId'   => $districtId,
            'districtName' => $districtId ? District::find($districtId)?->name : null,
            'generatedAt'  => now(),
            'generatedBy'  => $user,
            'reportCode'   => 'PERF',
            'reference'    => 'ETPB/ROP/PERF/' . now()->format('Y') . '/' . now()->format('mdHi'),
            'distribution' => [
                'The Minister-in-charge of the Division concerned.',
                'The Secretary to Government of the Punjab, concerned Department.',
                'The Chairman, Evacuee Trust Property Board, Lahore.',
                'Office record.',
            ],
        ];

        if ($format = $this->requestedFormat($request)) {
            return $this->export->render(
                $format,
                view('reports.print.glimpse', $payload)->render(),
                $this->glimpseSheets($payload),
                'ETPB-performance-glimpse-' . now()->format('Y-m-d'),
                'Performance at a glance',
                'portrait',
                $payload['reference'] ?? '',
            );
        }

        return view('reports.glimpse', $payload);
    }

    /**
     * The consolidated / master report.
     */
    public function executive(Request $request): View|StreamedResponse
    {
        $user = $request->user();
        $districtId = $request->integer('district') ?: null;

        $payload = [
            'headline'     => $this->data->headline($user, $districtId),
            'performance'  => $this->data->performance($user, $districtId),
            'byDistrict'   => $this->data->byDistrict($user, $districtId),
            'monthly'      => $this->data->monthlyIntake($user, $districtId),
            'objections'   => $this->data->objections($districtId),
            'breaches'     => $this->data->breaches($user, $districtId),
            'districts'    => District::orderBy('name')->get(['id', 'name']),
            'districtId'   => $districtId,
            'districtName' => $districtId ? District::find($districtId)?->name : null,
            'labels'       => WorkflowService::LABELS,
            'generatedAt'  => now(),
            'generatedBy'  => $user,
            'reportCode'   => 'CONS',
            'reference'    => 'ETPB/ROP/CONS/' . now()->format('Y') . '/' . now()->format('mdHi'),
            'distribution' => [
                'The Chairman, Evacuee Trust Property Board, Lahore.',
                'All Administrators, Evacuee Trust Property Board.',
                'All District Officers concerned.',
                'The Director (Finance & Accounts), Evacuee Trust Property Board.',
                'Office record.',
            ],
        ];

        if ($format = $this->requestedFormat($request)) {
            return $this->export->render(
                $format,
                view('reports.print.executive', $payload)->render(),
                $this->executiveSheets($payload),
                'ETPB-consolidated-report-' . now()->format('Y-m-d'),
                'Consolidated report',
                'landscape',
                $payload['reference'] ?? '',
            );
        }

        return view('reports.executive', $payload);
    }

    /**
     * The deep report: one application, every element.
     */
    public function deep(Request $request, Application $application): View|StreamedResponse
    {
        $this->authoriseView($request, $application);

        $application->load([
            'applicant.addressDistrict', 'property.province', 'property.district',
            'property.tehsil', 'property.mouza', 'property.currentArea', 'property.geoTags',
            'possession', 'documents.documentType', 'feePayments', 'occupantOffers',
            'litigations', 'objections.notice', 'hearings', 'notices', 'approvals',
            'nominees.heirs', 'agreement', 'order', 'districtOfficer:id,name',
            'administrator:id,name', 'unitProfile',
            'history' => fn ($q) => $q->orderBy('occurred_at'),
        ]);

        $round = $application->rounds()->with(['rateInputs.rateSource', 'comparables'])
            ->orderByDesc('round_no')->first();

        $payload = [
            'application'        => $application,
            'round'              => $round,
            'decisions'          => DB::table('assessment_decisions')
                                      ->where('assessment_round_id', $round?->id ?? 0)
                                      ->whereNull('deleted_at')->orderByDesc('decided_at')->get(),
            'objectionDecisions' => DB::table('objection_decisions')
                                      ->whereIn('objection_id', $application->objections->pluck('id'))
                                      ->whereNull('deleted_at')->get()->keyBy('objection_id'),
            'schedule'           => DB::table('rent_schedules')
                                      ->where('application_id', $application->id)
                                      ->orderBy('year')->get(),
            'milestones'         => $this->rent->milestoneTable($application->id),
            'ledger'             => DB::table('arrears_ledger')
                                      ->where('application_id', $application->id)
                                      ->orderBy('period_year')->get(),
            'arrears'            => $this->arrears->summary($application->id),
            'clearance'          => $this->arrears->clearanceStatus($application->id),
            'receipts'           => $application->receipts()->orderBy('receipt_date')->get(),
            'instalmentPlans'    => DB::table('instalment_plans')
                                      ->where('application_id', $application->id)
                                      ->whereNull('deleted_at')->get(),
            'remissions'         => DB::table('remissions')
                                      ->where('application_id', $application->id)
                                      ->whereNull('deleted_at')->get(),
            'audit'              => DB::table('audit_logs')
                                      ->where('auditable_type', Application::class)
                                      ->where('auditable_id', $application->id)
                                      ->orderBy('created_at')->limit(500)->get(),
            'generatedAt'        => now(),
            'generatedBy'        => $request->user(),
            'reportCode'         => 'CASE',
            'reference'          => $application->application_no,
            'distribution'       => [
                'The Administrator concerned.',
                'The District Officer, ' . ($application->district?->name ?? 'concerned district') . '.',
                'The applicant, ' . ($application->applicant?->full_name ?? '') . '.',
                'Case file.',
            ],
        ];

        if ($format = $this->requestedFormat($request)) {
            return $this->export->render(
                $format,
                view('reports.print.deep', $payload)->render(),
                $this->deepSheets($payload),
                'ETPB-case-' . str_replace('/', '-', $application->application_no),
                'Deep report — ' . $application->application_no,
                'portrait',
                $application->application_no,
            );
        }

        return view('reports.deep', $payload);
    }

    /**
     * Routine operational registers.
     */
    public function register(Request $request, string $register): View|StreamedResponse
    {
        $user = $request->user();
        $allowed = $this->registers();

        abort_unless(isset($allowed[$register]), 404, 'Unknown register.');

        $rows = $this->registerRows($register, $user);

        $payload = [
            'register'    => $register,
            'title'       => $allowed[$register],
            'registers'   => $allowed,
            'rows'        => $rows,
            'generatedAt' => now(),
            'generatedBy' => $user,
            'reportCode'  => 'REG',
            'reference'   => 'ETPB/ROP/REG/' . now()->format('Y') . '/' . now()->format('mdHi'),
            'distribution' => [
                'The Administrator concerned.',
                'The District Officer concerned.',
                'Office record.',
            ],
        ];

        if ($format = $this->requestedFormat($request)) {
            $headings = $rows->isNotEmpty()
                ? array_map(fn ($c) => ucwords(str_replace('_', ' ', $c)), array_keys((array) $rows->first()))
                : [];

            return $this->export->render(
                $format,
                view('reports.print.register', $payload)->render(),
                [$allowed[$register] => ['headings' => $headings, 'rows' => $rows]],
                'ETPB-' . $register . '-register-' . now()->format('Y-m-d'),
                $allowed[$register],
                'landscape',
                $payload['reference'] ?? '',
            );
        }

        return view('reports.register', $payload);
    }

    // ---- registers ---------------------------------------------------------

    /** @return array<string, string> */
    private function registers(): array
    {
        return [
            'applications' => 'Application register',
            'fee'          => 'Fee register',
            'arrears'      => 'Arrears outstanding statement',
            'objections'   => 'Objection register',
            'litigation'   => 'Sub judice register',
            'regularized'  => 'Regularization register',
            'assessment'   => 'Rent assessment register',
        ];
    }

    private function registerRows(string $register, $user)
    {
        $restrict = fn ($q, string $col = 'a.district_id') => $q->when(
            ! $user->hasPermission('applications.view_all'),
            fn ($qq) => $qq->where($col, $user->district_id),
        );

        return match ($register) {
            'fee' => $restrict(DB::table('fee_payments as f')
                ->join('applications as a', 'a.id', '=', 'f.application_id')
                ->join('applicants as ap', 'ap.id', '=', 'a.applicant_id')
                ->leftJoin('districts as d', 'd.id', '=', 'a.district_id')
                ->whereNull('f.deleted_at')->whereNull('a.deleted_at'))
                ->select('a.application_no', 'ap.full_name', 'ap.cnic', 'd.name as district',
                         'f.instrument_type', 'f.instrument_no', 'f.instrument_date', 'f.amount',
                         'f.bank_name', 'f.branch_code', 'f.status as instrument_status',
                         'a.payment_status')
                ->orderByDesc('f.instrument_date')->limit(5000)->get(),

            'arrears' => $restrict(DB::table('applications as a')
                ->join('applicants as ap', 'ap.id', '=', 'a.applicant_id')
                ->leftJoin('districts as d', 'd.id', '=', 'a.district_id')
                ->whereNull('a.deleted_at')->where('a.arrears_balance', '>', 0))
                ->select('a.application_no', 'ap.full_name', 'ap.cnic', 'd.name as district',
                         'a.assessed_monthly_rent', 'a.total_arrears', 'a.arrears_paid',
                         'a.arrears_balance', 'a.status')
                ->orderByDesc('a.arrears_balance')->limit(5000)->get(),

            'objections' => $restrict(DB::table('objections as o')
                ->join('applications as a', 'a.id', '=', 'o.application_id')
                ->leftJoin('districts as d', 'd.id', '=', 'a.district_id')
                ->whereNull('o.deleted_at'))
                ->select('a.application_no', 'd.name as district', 'o.objection_no',
                         'o.objector_name', 'o.objector_cnic', 'o.filed_on',
                         'o.is_within_time', 'o.status')
                ->orderByDesc('o.filed_on')->limit(5000)->get(),

            'litigation' => $restrict(DB::table('litigations as l')
                ->leftJoin('applications as a', 'a.id', '=', 'l.application_id')
                ->leftJoin('districts as d', 'd.id', '=', 'a.district_id')
                ->whereNull('l.deleted_at'))
                ->select('a.application_no', 'd.name as district', 'l.court_name', 'l.case_no',
                         'l.case_type', 'l.is_pending', 'l.has_restraining_order',
                         'l.is_direction_case', 'l.next_hearing_date', 'l.outcome')
                ->orderByDesc('l.is_pending')->limit(5000)->get(),

            'regularized' => $restrict(DB::table('applications as a')
                ->join('applicants as ap', 'ap.id', '=', 'a.applicant_id')
                ->leftJoin('districts as d', 'd.id', '=', 'a.district_id')
                ->leftJoin('tenancy_agreements as t', 't.application_id', '=', 'a.id')
                ->whereNull('a.deleted_at')->where('a.status', WorkflowService::REGULARIZED))
                ->select('a.application_no', 'ap.full_name', 'ap.cnic', 'd.name as district',
                         'a.assessed_monthly_rent', 'a.regularized_at',
                         't.agreement_no', 't.executed_on')
                ->orderByDesc('a.regularized_at')->limit(5000)->get(),

            'assessment' => $restrict(DB::table('assessment_rounds as r')
                ->join('applications as a', 'a.id', '=', 'r.application_id')
                ->join('applicants as ap', 'ap.id', '=', 'a.applicant_id')
                ->leftJoin('districts as d', 'd.id', '=', 'a.district_id')
                ->whereNull('r.deleted_at'))
                ->select('a.application_no', 'ap.full_name', 'd.name as district',
                         'r.round_no', 'r.base_date', 'r.enhancement_rate', 'r.enhancement_method',
                         'r.proposed_monthly_rent', 'r.determined_monthly_rent',
                         'r.first_notice_date', 'r.completion_due_date', 'r.status')
                ->orderByDesc('r.id')->limit(5000)->get(),

            default => $restrict(DB::table('applications as a')
                ->join('applicants as ap', 'ap.id', '=', 'a.applicant_id')
                ->leftJoin('districts as d', 'd.id', '=', 'a.district_id')
                ->whereNull('a.deleted_at'))
                ->select('a.application_no', 'ap.full_name', 'ap.cnic', 'd.name as district',
                         'a.payment_status', 'a.status', 'a.assessed_monthly_rent',
                         'a.arrears_balance', 'a.created_at')
                ->orderByDesc('a.created_at')->limit(5000)->get(),
        };
    }

    // ---- workbook shapes ---------------------------------------------------

    /** @return array<string, array{headings: array<int,string>, rows: iterable}> */
    private function glimpseSheets(array $p): array
    {
        $h = $p['headline'];
        $perf = $p['performance'];

        $summary = [
            ['Applications received', $h['all']],
            ['Open', $h['open']],
            ['Regularized', $h['regularized']],
            ['Rejected', $h['rejected']],
            ['Disposal rate (%)', $h['disposal_rate']],
            ['Awaiting deposit', $h['pending_pay']],
            ['Sub judice', $h['sub_judice']],
            ['Monthly rent secured (Rs.)', $h['monthly_rent']],
            ['Arrears assessed (Rs.)', $h['assessed']],
            ['Arrears recovered (Rs.)', $h['recovered']],
            ['Arrears outstanding (Rs.)', $h['outstanding']],
            ['Recovery rate (%)', $h['recovery_rate']],
            ['Fee collected (Rs.)', $h['fee_total']],
            ['Area regularized (sqft)', $h['area_sqft']],
            ['Assessment on time (%)', $perf['assessment_ontime']],
            ['Approval on time (%)', $perf['approval_ontime']],
            ['Average days to regularize', $perf['avg_days'] ?? 'n/a'],
        ];

        return [
            'Summary' => ['headings' => ['Measure', 'Value'], 'rows' => $summary],
            'By district' => [
                'headings' => ['District', 'Received', 'Paid', 'Regularized', 'Sub judice',
                               'Assessed', 'Recovered', 'Outstanding'],
                'rows' => $p['byDistrict']->map(fn ($d) => [
                    $d->district?->name, $d->total, $d->paid, $d->regularized, $d->sub_judice,
                    $d->assessed, $d->recovered, $d->outstanding,
                ]),
            ],
            'Monthly intake' => [
                'headings' => ['Month', 'Applications'],
                'rows' => $p['monthly']->map(fn ($n, $ym) => [$ym, $n])->values(),
            ],
        ];
    }

    /** @return array<string, array{headings: array<int,string>, rows: iterable}> */
    private function executiveSheets(array $p): array
    {
        $sheets = $this->glimpseSheets($p);

        $sheets['Caseload by stage'] = [
            'headings' => ['Stage', 'Count'],
            'rows' => collect($p['headline']['by_status'])
                ->map(fn ($n, $s) => [WorkflowService::LABELS[$s] ?? $s, $n])->values(),
        ];

        $sheets['Deadline breaches'] = [
            'headings' => ['Type', 'Application', 'Applicant', 'District', 'Due', 'Officer'],
            'rows' => collect($p['breaches']['assessment'])
                ->map(fn ($a) => ['Assessment', $a->application_no, $a->applicant?->full_name,
                                  $a->district?->name,
                                  (string) ($a->assessment_extended_to ?: $a->assessment_due_date),
                                  $a->districtOfficer?->name])
                ->concat(collect($p['breaches']['approval'])
                    ->map(fn ($a) => ['Approval', $a->application_no, $a->applicant?->full_name,
                                      $a->district?->name, (string) $a->admin_approval_due_date,
                                      $a->administrator?->name])),
        ];

        return $sheets;
    }

    /** @return array<string, array{headings: array<int,string>, rows: iterable}> */
    private function deepSheets(array $p): array
    {
        $a = $p['application'];

        return [
            'Case' => [
                'headings' => ['Field', 'Value'],
                'rows' => [
                    ['Application no.', $a->application_no],
                    ['Applicant', $a->applicant?->full_name],
                    ['Parentage', $a->applicant?->parentage_name],
                    ['CNIC', $a->applicant?->cnic],
                    ['Property', $a->property?->identity()],
                    ['Location', $a->property?->locationChain()],
                    ['Area (sqft)', $a->property?->currentArea?->area_sqft],
                    ['Date of possession', (string) ($a->possession?->date_of_possession)],
                    ['Arrears from', (string) ($a->possession?->arrears_from)],
                    ['Payment status', $a->payment_status],
                    ['Stage', $a->statusLabel()],
                    ['Rent fixed (Rs./month)', $a->assessed_monthly_rent],
                    ['Arrears assessed (Rs.)', $p['arrears']['total_due']],
                    ['Arrears balance (Rs.)', $p['arrears']['balance']],
                ],
            ],
            'Rent schedule' => [
                'headings' => ['Year', 'From', 'To', 'Monthly rent', 'Annual rent'],
                'rows' => $p['schedule']->map(fn ($s) => [
                    $s->year, $s->period_from, $s->period_to, $s->monthly_rent, $s->annual_rent,
                ]),
            ],
            'Arrears ledger' => [
                'headings' => ['Year', 'From', 'To', 'Monthly', 'Months', 'Due', 'Paid', 'Remitted', 'Balance'],
                'rows' => $p['ledger']->map(fn ($l) => [
                    $l->period_year, $l->period_from, $l->period_to, $l->monthly_rent,
                    $l->months_applicable, $l->amount_due, $l->amount_paid,
                    $l->remission_amount, $l->balance,
                ]),
            ],
            'Evidence' => [
                'headings' => ['Head', 'Reference', 'Dated', 'Issuing authority', 'Certified', 'Status'],
                'rows' => $a->documents->map(fn ($d) => [
                    $d->documentType?->name, $d->reference_no, (string) $d->document_date,
                    $d->issuing_authority, $d->is_certified_copy ? 'Yes' : 'No', $d->status,
                ]),
            ],
            'Objections' => [
                'headings' => ['Objection', 'Objector', 'CNIC', 'Filed', 'In time', 'Status', 'Plea'],
                'rows' => $a->objections->map(fn ($o) => [
                    $o->objection_no, $o->objector_name, $o->objector_cnic, (string) $o->filed_on,
                    $o->is_within_time ? 'Yes' : 'No', $o->status, $o->plea,
                ]),
            ],
            'Occupant offers' => [
                'headings' => ['Occupant', 'CNIC', 'Portion', 'Area', 'Rent offered', 'Offered on', 'Status'],
                'rows' => $a->occupantOffers->map(fn ($o) => [
                    $o->occupant_name, $o->occupant_cnic, $o->portion_occupied, $o->area_sqft,
                    $o->rent_offered, (string) $o->offer_date, $o->status,
                ]),
            ],
            'Litigation' => [
                'headings' => ['Court', 'Case no.', 'Type', 'Pending', 'Stay', 'Direction', 'Outcome'],
                'rows' => $a->litigations->map(fn ($l) => [
                    $l->court_name, $l->case_no, $l->case_type,
                    $l->is_pending ? 'Yes' : 'No',
                    $l->has_restraining_order ? 'Yes' : 'No',
                    $l->is_direction_case ? 'Yes' : 'No', $l->outcome,
                ]),
            ],
            'History' => [
                'headings' => ['When', 'From', 'To', 'Role', 'Remarks'],
                'rows' => $a->history->map(fn ($h) => [
                    (string) $h->occurred_at, $h->from_status, $h->to_status,
                    $h->actor_role, $h->remarks,
                ]),
            ],
        ];
    }

    // ---- helpers -----------------------------------------------------------

    /** Null when the report should render on screen. */
    private function requestedFormat(Request $request): ?string
    {
        $format = strtolower((string) $request->query('format', ''));

        return in_array($format, ['pdf', 'docx', 'xlsx'], true) ? $format : null;
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
        if ($application->applicant?->user_id === $user->id) {
            return;
        }

        abort(403, 'This application is outside your jurisdiction.');
    }
}
