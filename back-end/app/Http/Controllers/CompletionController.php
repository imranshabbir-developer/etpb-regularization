<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Nominee;
use App\Models\RegularizationOrder;
use App\Models\TenancyAgreement;
use App\Services\SettingService;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * The last stage: nomination form, tenancy agreement, regularization order.
 *
 * Clause 3(ii)(b) requires that "a tenancy agreement shall be executed by the
 * concerned District Officer with the occupant", and the proviso to Scheme
 * para 3(iii)(B) is blunt: "the District Officer shall not transfer the tenancy
 * or regularize the possession unless he has obtained the aforesaid nominee
 * form." So the nomination form is a precondition, not paperwork to follow up
 * afterwards, and the workflow refuses execution without it.
 */
class CompletionController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflow,
        private readonly SettingService $settings,
    ) {
    }

    public function index(Request $request, Application $application): View
    {
        $this->authoriseView($request, $application);

        $application->load([
            'applicant', 'property.district', 'property.currentArea',
            'nominees.heirs', 'agreement', 'order', 'approvals',
        ]);

        return view('completion.index', [
            'application' => $application,
            'nominee'     => $application->nominees->first(),
            'agreement'   => $application->agreement,
            'order'       => $application->order,
            'canExecute'  => $this->workflow->check($application->id, WorkflowService::AGREEMENT_EXECUTION),
            'canComplete' => $this->workflow->check($application->id, WorkflowService::REGULARIZED),
        ]);
    }

    /**
     * The nomination form, with the legal heirs it names. Without this the
     * possession cannot be regularized at all.
     */
    public function storeNominee(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseView($request, $application);

        $data = $request->validate([
            'nominee_name'      => ['required', 'string', 'max:150'],
            'nominee_parentage' => ['nullable', 'string', 'max:150'],
            'relationship'      => ['required', 'string', 'max:80'],
            'nominee_cnic'      => ['nullable', 'digits:13'],
            'nominee_contact'   => ['nullable', 'string', 'max:20'],
            'nominee_address'   => ['nullable', 'string', 'max:500'],
            'share_percentage'  => ['nullable', 'numeric', 'between:0,100'],
            'form_received_on'  => ['required', 'date', 'before_or_equal:today'],
            'heirs'             => ['nullable', 'array', 'max:20'],
            'heirs.*.heir_name' => ['nullable', 'string', 'max:150'],
            'heirs.*.relationship' => ['nullable', 'string', 'max:80'],
            'heirs.*.cnic'      => ['nullable', 'digits:13'],
        ]);

        DB::transaction(function () use ($application, $data, $request) {
            $nominee = Nominee::create([
                'application_id'    => $application->id,
                'nominee_name'      => $data['nominee_name'],
                'nominee_parentage' => $data['nominee_parentage'] ?? null,
                'relationship'      => $data['relationship'],
                'nominee_cnic'      => $data['nominee_cnic'] ?? null,
                'nominee_contact'   => $data['nominee_contact'] ?? null,
                'nominee_address'   => $data['nominee_address'] ?? null,
                'share_percentage'  => $data['share_percentage'] ?? null,
                'form_received_on'  => $data['form_received_on'],
                'is_verified'       => true,
                'verified_by'       => $request->user()->id,
                'verified_at'       => now(),
                'created_by'        => $request->user()->id,
            ]);

            $order = 0;
            foreach ($data['heirs'] ?? [] as $heir) {
                if (blank($heir['heir_name'] ?? null)) {
                    continue;
                }
                DB::table('nominee_heirs')->insert([
                    'nominee_id'    => $nominee->id,
                    'heir_name'     => $heir['heir_name'],
                    'relationship'  => $heir['relationship'] ?? '',
                    'cnic'          => $heir['cnic'] ?? null,
                    'display_order' => ++$order,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        });

        return back()->with('status',
            'Nomination form recorded. The tenancy agreement may now be executed.');
    }

    /**
     * Execute the tenancy agreement — the act that Clause 3(ii)(b) requires and
     * that turns an unauthorised occupant into a recorded tenant.
     */
    public function storeAgreement(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseView($request, $application);

        if ($application->agreement) {
            return back()->with('error', 'A tenancy agreement has already been executed for this application.');
        }

        $data = $request->validate([
            'executed_on'       => ['required', 'date', 'before_or_equal:today'],
            'monthly_rent'      => ['required', 'numeric', 'min:0'],
            'security_amount'   => ['nullable', 'numeric', 'min:0'],
            'effective_from'    => ['required', 'date'],
            'valid_till'        => ['nullable', 'date', 'after:effective_from'],
            'stamp_paper_no'    => ['nullable', 'string', 'max:80'],
            'stamp_paper_value' => ['nullable', 'numeric', 'min:0'],
            'stamp_paper_date'  => ['nullable', 'date'],
            'terms'             => ['nullable', 'string', 'max:20000'],
        ]);

        $user = $request->user();

        try {
            DB::transaction(function () use ($application, $data, $user) {
                TenancyAgreement::create($data + [
                    'application_id' => $application->id,
                    'agreement_no'   => $application->application_no . '/TA/01',
                    'executed_by'    => $user->id,
                    'applicant_id'   => $application->applicant_id,
                    'status'         => 'EXECUTED',
                ]);
            });

            $this->workflow->transition(
                $application->id, WorkflowService::AGREEMENT_EXECUTION,
                $user->id, $user->primaryRole()?->code,
                'Tenancy agreement executed by the District Officer under Clause 3(ii)(b).',
                $request->ip(),
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('status', 'Tenancy agreement executed.');
    }

    /**
     * Issue the regularization order and close the case.
     */
    public function storeOrder(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseView($request, $application);

        if ($application->order) {
            return back()->with('error', 'A regularization order has already been issued.');
        }

        $data = $request->validate([
            'order_date' => ['required', 'date', 'before_or_equal:today'],
            'order_text' => ['required', 'string', 'min:60', 'max:20000'],
        ], [
            'order_text.required' => 'The order must set out what is being regularized and on what terms.',
            'order_text.min'      => 'The order text is too short to stand as a reasoned order.',
        ]);

        $user = $request->user();

        try {
            DB::transaction(function () use ($application, $data, $user) {
                RegularizationOrder::create([
                    'application_id'         => $application->id,
                    'order_no'               => $application->application_no . '/ORD/01',
                    'order_date'             => $data['order_date'],
                    'issued_by'              => $user->id,
                    'issued_by_designation'  => $user->designation,
                    'order_text'             => $data['order_text'],
                    'regularized_area_sqft'  => $application->property?->currentArea?->area_sqft,
                    'monthly_rent_fixed'     => $application->assessed_monthly_rent,
                    'status'                 => 'ISSUED',
                ]);
            });

            $this->workflow->transition(
                $application->id, WorkflowService::REGULARIZED,
                $user->id, $user->primaryRole()?->code,
                'Regularization order issued.', $request->ip(),
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('completion.index', $application)
            ->with('status', 'Regularization order issued. The possession is now regularized.');
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
