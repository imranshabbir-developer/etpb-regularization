<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\Litigation;
use App\Models\OccupantOffer;
use App\Models\Property;
use App\Services\AreaConversionService;
use App\Services\EligibilityService;
use App\Services\SettingService;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * The public applicant's guided form.
 *
 * The scheme is addressed to the general public, most of whom will use this
 * once in their lives and many of whom are filling it in on a phone. So the
 * six heads of the requirements are presented as six short steps rather than
 * one long form, each step is saved before the next is shown, and the
 * application can be left and resumed.
 *
 * Steps 1 to 3 are held in the session because there is nothing to attach a
 * document to until the applicant record exists. The application is created at
 * the end of step 3; steps 4 to 6 then work on the saved record, which is why a
 * half-finished application is never orphaned in the database.
 */
class ApplyController extends Controller
{
    private const SESSION_KEY = 'apply.draft';

    /** The six heads, in the order the requirements set them out. */
    public const STEPS = [
        1 => ['key' => 'applicant',  'title' => 'About you',            'head' => 'Head 1'],
        2 => ['key' => 'property',   'title' => 'The property',         'head' => 'Head 1'],
        3 => ['key' => 'possession', 'title' => 'Your possession',      'head' => 'Head 1'],
        4 => ['key' => 'evidence',   'title' => 'Evidence',             'head' => 'Head 2'],
        5 => ['key' => 'occupants',  'title' => 'Others and courts',    'head' => 'Head 4'],
        6 => ['key' => 'fee',        'title' => 'Rs. 5,000 deposit',    'head' => 'Head 5'],
    ];

    public function __construct(
        private readonly AreaConversionService $area,
        private readonly EligibilityService $eligibility,
        private readonly WorkflowService $workflow,
        private readonly SettingService $settings,
    ) {
    }

    /** Landing: what the applicant is about to do, and what they will need. */
    public function start(Request $request): View
    {
        return view('apply.start', [
            'cutoff'    => $this->eligibility->cutoffDate(),
            'cutoffStated' => $this->eligibility->cutoffStatedAs(),
            'fee'       => $this->settings->decimal('processing_fee', '5000.00'),
            'docTypes'  => DocumentType::where('is_active', true)->where('is_mandatory', true)
                             ->orderBy('display_order')->get(),
            'inProgress' => $this->myApplications($request)->where('status', WorkflowService::DRAFT),
        ]);
    }

    /** The applicant's own list — every application they have filed. */
    public function mine(Request $request): View
    {
        return view('apply.mine', [
            'applications' => $this->myApplications($request),
            'fee'          => $this->settings->decimal('processing_fee', '5000.00'),
        ]);
    }

    // ---- Step 1: about you -------------------------------------------------

    public function applicant(Request $request): View
    {
        return view('apply.applicant', $this->chrome(1, [
            'draft'     => $this->draft($request),
            'districts' => District::orderBy('name')->get(['id', 'name']),
        ]));
    }

    public function storeApplicant(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name'           => ['required', 'string', 'max:150'],
            'parentage_type'      => ['required', Rule::in(['FATHER', 'HUSBAND'])],
            'parentage_name'      => ['required', 'string', 'max:150'],
            'cnic'                => ['required', 'digits:13'],
            'contact'             => ['required', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:150'],
            'postal_address'      => ['required', 'string', 'max:500'],
            'address_district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'is_indigent'         => ['nullable', 'boolean'],
            'is_widow'            => ['nullable', 'boolean'],
            'is_orphan'           => ['nullable', 'boolean'],
        ], [
            'cnic.digits' => 'Your CNIC must be exactly 13 digits, without dashes.',
        ]);

        $this->mergeDraft($request, ['applicant' => $data]);

        return redirect()->route('apply.property');
    }

    // ---- Step 2: the property ---------------------------------------------

    public function property(Request $request): View
    {
        $draft = $this->draft($request);
        $this->requireStep($draft, 'applicant', 'apply.applicant');

        $profileId = $this->area->defaultProfileId();

        return view('apply.property', $this->chrome(2, [
            'draft'     => $draft,
            'districts' => District::with('province:id,name')->orderBy('name')->get(),
            'profiles'  => $this->area->profiles(),
            'units'     => $this->area->units($profileId),
            'profileId' => $profileId,
        ]));
    }

    public function storeProperty(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'property_no'       => ['required', 'string', 'max:60'],
            'sub_unit_no'       => ['nullable', 'string', 'max:60'],
            'property_type'     => ['required', Rule::in(['HOUSE', 'SHOP', 'BUILDING', 'PLOT', 'AGRI_LAND', 'OTHER'])],
            'usage_type'        => ['required', Rule::in(['RESIDENTIAL', 'COMMERCIAL', 'RESIDENTIAL_CUM_COMMERCIAL', 'OTHER'])],
            'property_address'  => ['required', 'string', 'max:500'],
            'district_id'       => ['required', 'integer', 'exists:districts,id'],
            'tehsil_id'         => ['nullable', 'integer', 'exists:tehsils,id'],
            'mouza_name'        => ['nullable', 'string', 'max:150'],
            'city'              => ['nullable', 'string', 'max:120'],
            'khewat_no'         => ['nullable', 'string', 'max:40'],
            'khatooni_no'       => ['nullable', 'string', 'max:40'],
            'khasra_no'         => ['nullable', 'string', 'max:40'],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'unit_profile_id'   => ['nullable', 'integer', 'exists:unit_conversion_profiles,id'],
            'area_mode'         => ['required', Rule::in(['SINGLE', 'COMPOUND'])],
            'area_unit'         => ['required_if:area_mode,SINGLE', 'nullable', 'string', 'max:20'],
            'area_value'        => ['required_if:area_mode,SINGLE', 'nullable', 'numeric', 'min:0'],
            'acres'             => ['nullable', 'numeric', 'min:0'],
            'kanals'            => ['nullable', 'numeric', 'min:0'],
            'marlas'            => ['nullable', 'numeric', 'min:0'],
            'sarsais'           => ['nullable', 'numeric', 'min:0'],
            'covered_area_sqft' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Fail here rather than at the end, while the applicant is still on the
        // screen that owns the mistake.
        try {
            $this->area->toSqft($this->areaComponents($data),
                $data['unit_profile_id'] ?? $this->area->defaultProfileId());
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['area_value' => $e->getMessage()]);
        }

        $this->mergeDraft($request, ['property' => $data]);

        return redirect()->route('apply.possession');
    }

    // ---- Step 3: possession, and creation ---------------------------------

    public function possession(Request $request): View
    {
        $draft = $this->draft($request);
        $this->requireStep($draft, 'property', 'apply.property');

        return view('apply.possession', $this->chrome(3, [
            'draft'  => $draft,
            'cutoff' => $this->eligibility->cutoffDate(),
            'cutoffStated' => $this->eligibility->cutoffStatedAs(),
        ]));
    }

    public function storePossession(Request $request): RedirectResponse
    {
        $draft = $this->draft($request);
        $this->requireStep($draft, 'property', 'apply.property');

        $data = $request->validate([
            'date_of_possession'       => ['required', 'date', 'before_or_equal:today'],
            'possession_nature'        => ['required', Rule::in(['SELF', 'INHERITED', 'PURCHASED', 'ALLOTTED', 'OTHER'])],
            'possession_description'   => ['nullable', 'string', 'max:2000'],
            'date_of_judicial_verdict' => ['nullable', 'date', 'before_or_equal:today'],
            'judicial_reference'       => ['nullable', 'string', 'max:150'],
            'declaration'              => ['accepted'],
        ], [
            'declaration.accepted' => 'You must confirm that the information you have given is true.',
        ]);

        if (! $this->eligibility->isWithinCutoff($data['date_of_possession'])) {
            throw ValidationException::withMessages([
                'date_of_possession' => sprintf(
                    'The scheme is only open to occupants in actual physical possession '
                    . 'prior to %s. Clause 3(ii)(a) of the Scheme 1977 does not allow a '
                    . 'later date.',
                    $this->eligibility->cutoffStatedAs()->format('j F Y'),
                ),
            ]);
        }

        $application = $this->createApplication($request, $draft, $data);

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('apply.evidence', $application)->with(
            'status',
            'Application ' . $application->application_no . ' saved. '
            . 'Now attach your evidence of possession.',
        );
    }

    // ---- Step 4: evidence --------------------------------------------------

    public function evidence(Request $request, Application $application): View
    {
        $this->authoriseOwn($request, $application);

        return view('apply.evidence', $this->chrome(4, [
            'application' => $application->load('documents.documentType'),
            'types'       => DocumentType::where('is_active', true)->orderBy('display_order')->get(),
            'uploaded'    => $application->documents->groupBy('document_type_id'),
        ]));
    }

    // ---- Step 5: other occupants and courts --------------------------------

    public function occupants(Request $request, Application $application): View
    {
        $this->authoriseOwn($request, $application);

        return view('apply.occupants', $this->chrome(5, [
            'application' => $application->load(['occupantOffers', 'litigations']),
        ]));
    }

    /**
     * The applicant declares what they know: anyone else in occupation, and
     * whether a court is involved. Officers verify and manage it afterwards.
     */
    public function storeOccupants(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseOwn($request, $application);

        $data = $request->validate([
            'has_other_occupants' => ['required', Rule::in(['yes', 'no'])],
            'occupant_name'       => ['nullable', 'required_if:has_other_occupants,yes', 'string', 'max:150'],
            'occupant_cnic'       => ['nullable', 'digits:13'],
            'occupant_contact'    => ['nullable', 'string', 'max:20'],
            'portion_occupied'    => ['nullable', 'string', 'max:200'],
            'rent_offered'        => ['nullable', 'numeric', 'min:0'],

            'has_court_case'      => ['required', Rule::in(['yes', 'no'])],
            'court_name'          => ['nullable', 'required_if:has_court_case,yes', 'string', 'max:200'],
            'case_no'             => ['nullable', 'required_if:has_court_case,yes', 'string', 'max:80'],
            'case_type'           => ['nullable', Rule::in(['CIVIL_SUIT', 'WRIT_PETITION', 'APPEAL', 'REVISION',
                                                            'EXECUTION', 'CONTEMPT', 'DIRECTION_CASE', 'OTHER'])],
            'has_restraining_order' => ['nullable', 'boolean'],
            'is_direction_case'     => ['nullable', 'boolean'],
            'case_remarks'          => ['nullable', 'string', 'max:2000'],
        ], [
            'occupant_name.required_if' => 'Give the name of the other occupant, or answer No.',
            'court_name.required_if'    => 'Give the name of the court, or answer No.',
            'case_no.required_if'       => 'Give the case number, or answer No.',
        ]);

        DB::transaction(function () use ($application, $data, $request) {
            if ($data['has_other_occupants'] === 'yes') {
                OccupantOffer::create([
                    'application_id'   => $application->id,
                    'occupant_name'    => $data['occupant_name'],
                    'occupant_cnic'    => $data['occupant_cnic'] ?? null,
                    'occupant_contact' => $data['occupant_contact'] ?? null,
                    'portion_occupied' => $data['portion_occupied'] ?? null,
                    'rent_offered'     => $data['rent_offered'] ?? 0,
                    'offer_date'       => now()->toDateString(),
                    'status'           => 'RECORDED',
                    'remarks'          => 'Declared by the applicant at the time of filing.',
                    'created_by'       => $request->user()->id,
                ]);
            }

            if ($data['has_court_case'] === 'yes') {
                Litigation::create([
                    'application_id'        => $application->id,
                    'property_id'           => $application->property_id,
                    'court_name'            => $data['court_name'],
                    'case_no'               => $data['case_no'],
                    'case_type'             => $data['case_type'] ?? 'OTHER',
                    'is_pending'            => true,
                    'has_restraining_order' => $request->boolean('has_restraining_order'),
                    'is_direction_case'     => $request->boolean('is_direction_case'),
                    'outcome'               => 'PENDING',
                    'remarks'               => trim('Declared by the applicant at the time of filing. '
                                               . ($data['case_remarks'] ?? '')),
                    'created_by'            => $request->user()->id,
                ]);

                $application->forceFill(['is_sub_judice' => true])->save();
            }
        });

        return redirect()->route('apply.fee', $application);
    }

    // ---- Step 6: the deposit, and submission -------------------------------

    public function fee(Request $request, Application $application): View
    {
        $this->authoriseOwn($request, $application);

        return view('apply.fee', $this->chrome(6, [
            'application' => $application->load(['feePayments', 'documents', 'applicant']),
            'feeAmount'   => $this->settings->decimal('processing_fee', '5000.00'),
            'districts'   => District::orderBy('name')->get(['id', 'name']),
            'readiness'   => $this->workflow->check($application->id, WorkflowService::SUBMITTED),
        ]));
    }

    public function submit(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseOwn($request, $application);

        $user = $request->user();

        try {
            $this->workflow->transition(
                $application->id, WorkflowService::SUBMITTED,
                $user->id, $user->primaryRole()?->code,
                'Submitted by the applicant through the portal.', $request->ip(),
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('apply.done', $application);
    }

    public function done(Request $request, Application $application): View
    {
        $this->authoriseOwn($request, $application);

        return view('apply.done', [
            'application' => $application->load('feePayments'),
            'fee'         => $this->settings->decimal('processing_fee', '5000.00'),
        ]);
    }

    // ---- helpers -----------------------------------------------------------

    private function createApplication(Request $request, array $draft, array $possession): Application
    {
        $user = $request->user();
        $ap = $draft['applicant'];
        $pr = $draft['property'];

        $district = District::findOrFail($pr['district_id']);
        $profileId = $pr['unit_profile_id'] ?? $this->area->profileForDistrict($district->id);
        $converted = $this->area->toSqft($this->areaComponents($pr), $profileId);
        $assessment = $this->eligibility->assess(
            $possession['date_of_possession'],
            $possession['date_of_judicial_verdict'] ?? null,
        );

        return DB::transaction(function () use ($ap, $pr, $possession, $district, $profileId, $converted, $assessment, $user, $request) {
            $applicant = Applicant::create([
                'user_id'             => $user->id,
                'full_name'           => $ap['full_name'],
                'parentage_type'      => $ap['parentage_type'],
                'parentage_name'      => $ap['parentage_name'],
                'cnic'                => $ap['cnic'],
                'contact'             => $ap['contact'],
                'email'               => $ap['email'] ?? $user->email,
                'postal_address'      => $ap['postal_address'],
                'address_district_id' => $ap['address_district_id'] ?? null,
                'is_indigent'         => $ap['is_indigent'] ?? false,
                'is_widow'            => $ap['is_widow'] ?? false,
                'is_orphan'           => $ap['is_orphan'] ?? false,
                'created_by'          => $user->id,
            ]);

            // A mouza typed by the applicant is kept as free text against the
            // property rather than silently creating a revenue master record.
            $property = Property::create([
                'property_no'   => $pr['property_no'],
                'sub_unit_no'   => $pr['sub_unit_no'] ?? null,
                'property_type' => $pr['property_type'],
                'usage_type'    => $pr['usage_type'],
                'address'       => trim($pr['property_address']
                                   . (! empty($pr['mouza_name']) ? ' (Mouza ' . $pr['mouza_name'] . ')' : '')),
                'province_id'   => $district->province_id,
                'district_id'   => $district->id,
                'tehsil_id'     => $pr['tehsil_id'] ?? null,
                'city'          => $pr['city'] ?? null,
                'khewat_no'     => $pr['khewat_no'] ?? null,
                'khatooni_no'   => $pr['khatooni_no'] ?? null,
                'khasra_no'     => $pr['khasra_no'] ?? null,
                'created_by'    => $user->id,
            ]);

            $property->areas()->create([
                'unit_profile_id'    => $profileId,
                'entry_mode'         => $pr['area_mode'],
                'entered_unit_code'  => $pr['area_mode'] === 'SINGLE' ? ($pr['area_unit'] ?? null) : null,
                'entered_value'      => $pr['area_mode'] === 'SINGLE' ? ($pr['area_value'] ?? null) : null,
                'acres'              => $pr['acres'] ?? null,
                'kanals'             => $pr['kanals'] ?? null,
                'marlas'             => $pr['marlas'] ?? null,
                'sarsais'            => $pr['sarsais'] ?? null,
                'area_sqft'          => $converted['sqft'],
                'covered_area_sqft'  => $pr['covered_area_sqft'] ?? null,
                'conversion_trace'   => $converted['trace'],
                'is_current'         => true,
                'created_by'         => $user->id,
            ]);

            if (! empty($pr['latitude']) && ! empty($pr['longitude'])) {
                $property->geoTags()->create([
                    'latitude'    => $pr['latitude'],
                    'longitude'   => $pr['longitude'],
                    'source'      => 'MANUAL',
                    'captured_at' => now(),
                    'captured_by' => $user->id,
                    'is_primary'  => true,
                ]);
            }

            $application = Application::create([
                'application_no'  => Application::nextApplicationNo($district->id),
                'applicant_id'    => $applicant->id,
                'property_id'     => $property->id,
                'district_id'     => $district->id,
                'unit_profile_id' => $profileId,
                'status'          => WorkflowService::DRAFT,
                'payment_status'  => 'PENDING',
                'created_by'      => $user->id,
            ]);

            $application->possession()->create([
                'application_id'           => $application->id,
                'date_of_possession'       => $possession['date_of_possession'],
                'possession_nature'        => $possession['possession_nature'],
                'possession_description'   => $possession['possession_description'] ?? null,
                'date_of_judicial_verdict' => $possession['date_of_judicial_verdict'] ?? null,
                'judicial_reference'       => $possession['judicial_reference'] ?? null,
                'arrears_from'             => $assessment['arrears_from'],
                'arrears_from_basis'       => $assessment['arrears_from_basis'],
                'is_eligible'              => $assessment['is_eligible'],
                'eligibility_reason'       => $assessment['reason'],
                'cutoff_applied'           => $assessment['cutoff_applied'],
                'created_by'               => $user->id,
            ]);

            DB::table('application_status_history')->insert([
                'application_id' => $application->id,
                'from_status'    => null,
                'to_status'      => WorkflowService::DRAFT,
                'action'         => 'CREATED',
                'remarks'        => 'Filed through the public portal.',
                'actor_id'       => $user->id,
                'actor_role'     => $user->primaryRole()?->code,
                'ip_address'     => $request->ip(),
                'occurred_at'    => now(),
            ]);

            return $application;
        });
    }

    /** @return array<string, mixed> */
    private function areaComponents(array $data): array
    {
        if (($data['area_mode'] ?? 'SINGLE') === 'SINGLE') {
            return [$data['area_unit'] ?? 'SQFT' => $data['area_value'] ?? null];
        }

        return array_filter([
            'ACRE'   => $data['acres'] ?? null,
            'KANAL'  => $data['kanals'] ?? null,
            'MARLA'  => $data['marlas'] ?? null,
            'SARSAI' => $data['sarsais'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    /** @return array<string, mixed> */
    private function chrome(int $step, array $data): array
    {
        return $data + [
            'steps'       => self::STEPS,
            'currentStep' => $step,
        ];
    }

    /** @return array<string, mixed> */
    private function draft(Request $request): array
    {
        return $request->session()->get(self::SESSION_KEY, []);
    }

    private function mergeDraft(Request $request, array $values): void
    {
        $request->session()->put(
            self::SESSION_KEY,
            array_merge($this->draft($request), $values),
        );
    }

    private function requireStep(array $draft, string $key, string $route): void
    {
        if (! isset($draft[$key])) {
            abort(redirect()->route($route)->with('warning', 'Please complete the earlier step first.'));
        }
    }

    private function myApplications(Request $request)
    {
        return Application::query()
            ->with(['property:id,property_no,sub_unit_no', 'district:id,name', 'possession'])
            ->whereHas('applicant', fn ($q) => $q->where('user_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->get();
    }

    private function authoriseOwn(Request $request, Application $application): void
    {
        $user = $request->user();

        if ($application->applicant?->user_id === $user->id) {
            return;
        }
        // A dealing assistant files on behalf of walk-in applicants.
        if ($user->hasPermission('applications.create')
            && $user->hasPermission('applications.view_district')
            && (int) $application->district_id === (int) $user->district_id) {
            return;
        }

        abort(403, 'This application is not yours.');
    }
}
