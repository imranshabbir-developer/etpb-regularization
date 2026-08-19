<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\Property;
use App\Services\AreaConversionService;
use App\Services\ArrearsService;
use App\Services\EligibilityService;
use App\Services\RentAssessmentService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly AreaConversionService $area,
        private readonly EligibilityService $eligibility,
        private readonly WorkflowService $workflow,
        private readonly RentAssessmentService $rent,
        private readonly ArrearsService $arrears,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $applications = Application::query()
            ->visibleTo($user)
            ->with(['applicant:id,full_name,parentage_name,cnic',
                    'property:id,property_no,sub_unit_no',
                    'district:id,name'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim($request->string('q')->toString());
                $q->where(function ($w) use ($term) {
                    $w->where('application_no', 'like', "%{$term}%")
                      ->orWhereHas('applicant', fn ($a) => $a->where('full_name', 'like', "%{$term}%")
                                                             ->orWhere('cnic', 'like', "%{$term}%"))
                      ->orWhereHas('property', fn ($p) => $p->where('property_no', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('district'), fn ($q) => $q->where('district_id', $request->integer('district')))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('applications.index', [
            'applications' => $applications,
            'statuses'     => WorkflowService::LABELS,
            'districts'    => District::orderBy('name')->get(['id', 'name']),
            'filters'      => $request->only('q', 'status', 'district'),
        ]);
    }

    public function create(Request $request): View
    {
        $profileId = $this->area->defaultProfileId();

        return view('applications.create', [
            'districts'  => District::with('province:id,name')->orderBy('name')->get(),
            'profiles'   => $this->area->profiles(),
            'units'      => $this->area->units($profileId),
            'profileId'  => $profileId,
            'cutoff'     => $this->eligibility->cutoffDate(),
            'docTypes'   => DocumentType::where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateIntake($request);

        // Clause 3(ii)(a) — refuse at the door rather than accepting a file
        // that can only ever be rejected.
        if (! $this->eligibility->isWithinCutoff($data['date_of_possession'])) {
            throw ValidationException::withMessages([
                'date_of_possession' => sprintf(
                    'Clause 3(ii)(a) of the Scheme 1977 requires actual physical possession prior to '
                    . '01-01-2010. The cut-off in force is %s.',
                    $this->eligibility->cutoffDate()->format('d-m-Y')
                ),
            ]);
        }

        $district = District::findOrFail($data['district_id']);
        $profileId = $data['unit_profile_id'] ?? $this->area->profileForDistrict($district->id);

        try {
            $converted = $this->area->toSqft($this->areaComponents($data), $profileId);
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['area' => $e->getMessage()]);
        }

        $assessment = $this->eligibility->assess(
            $data['date_of_possession'],
            $data['date_of_judicial_verdict'] ?? null
        );

        $user = $request->user();

        $application = DB::transaction(function () use ($data, $district, $profileId, $converted, $assessment, $user) {
            $applicant = Applicant::create([
                'user_id'             => $user->isApplicant() ? $user->id : null,
                'full_name'           => $data['full_name'],
                'parentage_type'      => $data['parentage_type'],
                'parentage_name'      => $data['parentage_name'],
                'cnic'                => $data['cnic'],
                'contact'             => $data['contact'],
                'email'               => $data['email'] ?? null,
                'postal_address'      => $data['postal_address'],
                'address_district_id' => $data['address_district_id'] ?? null,
                'is_indigent'         => $data['is_indigent'] ?? false,
                'is_widow'            => $data['is_widow'] ?? false,
                'is_orphan'           => $data['is_orphan'] ?? false,
                'created_by'          => $user->id,
            ]);

            $property = Property::create([
                'property_no'   => $data['property_no'],
                'sub_unit_no'   => $data['sub_unit_no'] ?? null,
                'property_type' => $data['property_type'],
                'usage_type'    => $data['usage_type'],
                'address'       => $data['property_address'],
                'province_id'   => $district->province_id,
                'district_id'   => $district->id,
                'tehsil_id'     => $data['tehsil_id'] ?? null,
                'mouza_id'      => $data['mouza_id'] ?? null,
                'city'          => $data['city'] ?? null,
                'khewat_no'     => $data['khewat_no'] ?? null,
                'khatooni_no'   => $data['khatooni_no'] ?? null,
                'khasra_no'     => $data['khasra_no'] ?? null,
                'created_by'    => $user->id,
            ]);

            $property->areas()->create([
                'unit_profile_id'    => $profileId,
                'entry_mode'         => $data['area_mode'],
                'entered_unit_code'  => $data['area_mode'] === 'SINGLE' ? $data['area_unit'] : null,
                'entered_value'      => $data['area_mode'] === 'SINGLE' ? $data['area_value'] : null,
                'acres'              => $data['acres'] ?? null,
                'kanals'             => $data['kanals'] ?? null,
                'marlas'             => $data['marlas'] ?? null,
                'sarsais'            => $data['sarsais'] ?? null,
                'square_yards'       => $data['square_yards'] ?? null,
                'square_feet_direct' => $data['square_feet'] ?? null,
                'area_sqft'          => $converted['sqft'],
                'covered_area_sqft'  => $data['covered_area_sqft'] ?? null,
                'conversion_trace'   => $converted['trace'],
                'is_current'         => true,
                'created_by'         => $user->id,
            ]);

            if (! empty($data['latitude']) && ! empty($data['longitude'])) {
                $property->geoTags()->create([
                    'latitude'    => $data['latitude'],
                    'longitude'   => $data['longitude'],
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
                'created_by'      => $user->id,
            ]);

            $application->possession()->create([
                'application_id'          => $application->id,
                'date_of_possession'      => $data['date_of_possession'],
                'possession_nature'       => $data['possession_nature'],
                'possession_description'  => $data['possession_description'] ?? null,
                'date_of_judicial_verdict' => $data['date_of_judicial_verdict'] ?? null,
                'judicial_reference'      => $data['judicial_reference'] ?? null,
                'arrears_from'            => $assessment['arrears_from'],
                'arrears_from_basis'      => $assessment['arrears_from_basis'],
                'is_eligible'             => $assessment['is_eligible'],
                'eligibility_reason'      => $assessment['reason'],
                'cutoff_applied'          => $assessment['cutoff_applied'],
                'created_by'              => $user->id,
            ]);

            DB::table('application_status_history')->insert([
                'application_id' => $application->id,
                'from_status'    => null,
                'to_status'      => WorkflowService::DRAFT,
                'action'         => 'CREATED',
                'remarks'        => 'Application created.',
                'actor_id'       => $user->id,
                'actor_role'     => $user->primaryRole()?->code,
                'ip_address'     => request()->ip(),
                'occurred_at'    => now(),
            ]);

            return $application;
        });

        return redirect()
            ->route('applications.show', $application)
            ->with('status', "Application {$application->application_no} created. "
                . 'Upload the evidence of possession and record the processing fee to submit it.');
    }

    public function show(Request $request, Application $application): View
    {
        $this->authoriseView($request, $application);

        $application->load([
            'applicant', 'property.district', 'property.province', 'property.tehsil',
            'property.mouza', 'property.currentArea', 'property.primaryGeoTag',
            'possession', 'documents.documentType', 'feePayments', 'litigations',
            'objections', 'approvals', 'nominees', 'history',
            'districtOfficer:id,name', 'administrator:id,name',
        ]);

        $nextStates = collect($this->workflow->allowedFrom($application->status))
            ->map(fn ($to) => [
                'to'    => $to,
                'label' => WorkflowService::LABELS[$to] ?? $to,
                'check' => $this->workflow->check($application->id, $to),
            ])
            ->all();

        return view('applications.show', [
            'application'  => $application,
            'nextStates'   => $nextStates,
            'milestones'   => $this->rent->milestoneTable($application->id),
            'arrears'      => $this->arrears->summary($application->id),
            'clearance'    => $this->arrears->clearanceStatus($application->id),
            'assessmentSla' => $application->assessmentSla(),
            'approvalSla'  => $application->adminApprovalSla(),
            'docTypes'     => DocumentType::where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    public function transition(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseView($request, $application);

        $data = $request->validate([
            'to'      => ['required', 'string', Rule::in(array_keys(WorkflowService::LABELS))],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        try {
            $this->workflow->transition(
                $application->id,
                $data['to'],
                $user->id,
                $user->primaryRole()?->code,
                $data['remarks'] ?? null,
                $request->ip(),
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', sprintf(
            'Application moved to "%s".',
            WorkflowService::LABELS[$data['to']] ?? $data['to']
        ));
    }

    /**
     * Live square-foot preview for the intake form, so the applicant sees the
     * conversion and the factors behind it before committing.
     */
    public function areaPreview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'unit_profile_id' => ['required', 'integer', 'exists:unit_conversion_profiles,id'],
            'components'      => ['required', 'array'],
        ]);

        try {
            $result = $this->area->toSqft($data['components'], (int) $data['unit_profile_id']);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $compound = $this->area->toCompound($result['sqft'], (int) $data['unit_profile_id']);

        return response()->json([
            'ok'         => true,
            'sqft'       => $result['sqft'],
            'sqft_human' => number_format((float) $result['sqft'], 2),
            'compound'   => $compound['label'],
            'trace'      => $result['trace']['components'],
            'profile'    => $result['trace']['profile_name'],
        ]);
    }

    // ---- helpers ---------------------------------------------------------

    /** @return array<string, mixed> */
    private function validateIntake(Request $request): array
    {
        return $request->validate([
            // Applicant
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

            // Property
            'property_no'      => ['required', 'string', 'max:60'],
            'sub_unit_no'      => ['nullable', 'string', 'max:60'],
            'property_type'    => ['required', Rule::in(['HOUSE', 'SHOP', 'BUILDING', 'PLOT', 'AGRI_LAND', 'OTHER'])],
            'usage_type'       => ['required', Rule::in(['RESIDENTIAL', 'COMMERCIAL', 'RESIDENTIAL_CUM_COMMERCIAL', 'OTHER'])],
            'property_address' => ['required', 'string', 'max:500'],
            'district_id'      => ['required', 'integer', 'exists:districts,id'],
            'tehsil_id'        => ['nullable', 'integer', 'exists:tehsils,id'],
            'mouza_id'         => ['nullable', 'integer', 'exists:mouzas,id'],
            'city'             => ['nullable', 'string', 'max:120'],
            'khewat_no'        => ['nullable', 'string', 'max:40'],
            'khatooni_no'      => ['nullable', 'string', 'max:40'],
            'khasra_no'        => ['nullable', 'string', 'max:40'],
            'latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180'],

            // Area
            'unit_profile_id'   => ['nullable', 'integer', 'exists:unit_conversion_profiles,id'],
            'area_mode'         => ['required', Rule::in(['SINGLE', 'COMPOUND'])],
            'area_unit'         => ['required_if:area_mode,SINGLE', 'nullable', 'string', 'max:20'],
            'area_value'        => ['required_if:area_mode,SINGLE', 'nullable', 'numeric', 'min:0'],
            'acres'             => ['nullable', 'numeric', 'min:0'],
            'kanals'            => ['nullable', 'numeric', 'min:0'],
            'marlas'            => ['nullable', 'numeric', 'min:0'],
            'sarsais'           => ['nullable', 'numeric', 'min:0'],
            'square_yards'      => ['nullable', 'numeric', 'min:0'],
            'square_feet'       => ['nullable', 'numeric', 'min:0'],
            'covered_area_sqft' => ['nullable', 'numeric', 'min:0'],

            // Possession
            'date_of_possession'       => ['required', 'date', 'before_or_equal:today'],
            'possession_nature'        => ['required', Rule::in(['SELF', 'INHERITED', 'PURCHASED', 'ALLOTTED', 'OTHER'])],
            'possession_description'   => ['nullable', 'string', 'max:2000'],
            'date_of_judicial_verdict' => ['nullable', 'date', 'before_or_equal:today'],
            'judicial_reference'       => ['nullable', 'string', 'max:150'],
        ], [
            'cnic.digits' => 'The CNIC must be exactly 13 digits, without dashes.',
        ]);
    }

    /** @return array<string, mixed> */
    private function areaComponents(array $data): array
    {
        if ($data['area_mode'] === 'SINGLE') {
            return [$data['area_unit'] => $data['area_value']];
        }

        return array_filter([
            'ACRE'   => $data['acres'] ?? null,
            'KANAL'  => $data['kanals'] ?? null,
            'MARLA'  => $data['marlas'] ?? null,
            'SARSAI' => $data['sarsais'] ?? null,
            'SQYD'   => $data['square_yards'] ?? null,
            'SQFT'   => $data['square_feet'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');
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
