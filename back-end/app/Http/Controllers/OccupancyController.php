<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Litigation;
use App\Models\OccupantOffer;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Head 4 — "Rent offered by the illegal Occupants" in tabular form, together
 * with the three litigation questions the requirements ask alongside it:
 * whether the matter is pending before any court of law, whether any
 * restraining order exists, and whether it is a direction case.
 *
 * These belong on one screen because they answer the same question: is anyone
 * else claiming this property, and is a court already seized of it. A pending
 * case or a subsisting stay parks the application; it does not merely annotate it.
 */
class OccupancyController extends Controller
{
    public function index(Request $request, Application $application): View
    {
        $this->authoriseView($request, $application);

        $application->load(['applicant', 'property.currentArea', 'occupantOffers', 'litigations']);

        return view('occupancy.index', [
            'application' => $application,
            'offers'      => $application->occupantOffers()->orderByDesc('rent_offered')->get(),
            'litigations' => $application->litigations()->orderByDesc('is_pending')->orderByDesc('filed_on')->get(),
            'assessed'    => $application->assessed_monthly_rent,
        ]);
    }

    public function storeOffer(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseView($request, $application);

        $data = $request->validate([
            'occupant_name'      => ['required', 'string', 'max:150'],
            'occupant_parentage' => ['nullable', 'string', 'max:150'],
            'occupant_cnic'      => ['nullable', 'digits:13'],
            'occupant_contact'   => ['nullable', 'string', 'max:20'],
            'occupant_address'   => ['nullable', 'string', 'max:500'],
            'portion_occupied'   => ['nullable', 'string', 'max:200'],
            'area_sqft'          => ['nullable', 'numeric', 'min:0'],
            'rent_offered'       => ['required', 'numeric', 'min:0'],
            'offer_date'         => ['required', 'date', 'before_or_equal:today'],
            'possession_since'   => ['nullable', 'date', 'before_or_equal:today'],
            'terms_offered'      => ['nullable', 'string', 'max:2000'],
            'remarks'            => ['nullable', 'string', 'max:2000'],
        ]);

        OccupantOffer::create($data + [
            'application_id' => $application->id,
            'status'         => 'RECORDED',
            'created_by'     => $request->user()->id,
        ]);

        return back()->with('status', 'Offer recorded.');
    }

    public function decideOffer(Request $request, OccupantOffer $offer): RedirectResponse
    {
        $this->authoriseView($request, $offer->application);

        $data = $request->validate([
            'status'  => ['required', Rule::in(['UNDER_CONSIDERATION', 'ACCEPTED', 'REJECTED', 'WITHDRAWN'])],
            'remarks' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'remarks.required' => 'Record why the offer is accepted or rejected — a competing occupant is entitled to know.',
        ]);

        $offer->update($data);

        return back()->with('status', 'Offer marked ' . strtolower(str_replace('_', ' ', $data['status'])) . '.');
    }

    /**
     * Record litigation. A pending case or a subsisting restraining order parks
     * the application at SUB_JUDICE — the department cannot fix rent over a
     * property a court has been asked to rule on.
     */
    public function storeLitigation(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseView($request, $application);

        $data = $request->validate([
            'court_name'             => ['required', 'string', 'max:200'],
            'case_no'                => ['required', 'string', 'max:80'],
            'case_title'             => ['nullable', 'string', 'max:255'],
            'case_type'              => ['required', Rule::in([
                'CIVIL_SUIT', 'WRIT_PETITION', 'APPEAL', 'REVISION',
                'EXECUTION', 'CONTEMPT', 'DIRECTION_CASE', 'OTHER',
            ])],
            'filed_on'               => ['nullable', 'date'],
            'petitioner'             => ['nullable', 'string', 'max:255'],
            'respondent'             => ['nullable', 'string', 'max:255'],
            'is_pending'             => ['nullable', 'boolean'],
            'has_restraining_order'  => ['nullable', 'boolean'],
            'restraining_order_date' => ['nullable', 'date'],
            'restraining_order_text' => ['nullable', 'string', 'max:4000'],
            'is_direction_case'      => ['nullable', 'boolean'],
            'direction_summary'      => ['nullable', 'string', 'max:4000'],
            'next_hearing_date'      => ['nullable', 'date'],
            'counsel_name'           => ['nullable', 'string', 'max:150'],
            'remarks'                => ['nullable', 'string', 'max:2000'],
        ]);

        $litigation = Litigation::create($data + [
            'application_id' => $application->id,
            'property_id'    => $application->property_id,
            'is_pending'     => $request->boolean('is_pending'),
            'has_restraining_order' => $request->boolean('has_restraining_order'),
            'is_direction_case'     => $request->boolean('is_direction_case'),
            'outcome'        => 'PENDING',
            'created_by'     => $request->user()->id,
        ]);

        $this->syncSubJudice($application, $request);

        return back()->with('status', $litigation->is_pending || $litigation->has_restraining_order
            ? 'Litigation recorded. The application is now parked as sub judice and cannot proceed.'
            : 'Litigation recorded.');
    }

    /**
     * Update a case — typically its disposal, which is what releases the
     * application from SUB_JUDICE.
     */
    public function updateLitigation(Request $request, Litigation $litigation): RedirectResponse
    {
        $application = $litigation->application;
        $this->authoriseView($request, $application);

        $data = $request->validate([
            'is_pending'            => ['nullable', 'boolean'],
            'has_restraining_order' => ['nullable', 'boolean'],
            'next_hearing_date'     => ['nullable', 'date'],
            'last_order_date'       => ['nullable', 'date'],
            'last_order_summary'    => ['nullable', 'string', 'max:4000'],
            'disposal_date'         => ['nullable', 'date'],
            'outcome'               => ['required', Rule::in([
                'ALLOWED', 'DISMISSED', 'WITHDRAWN', 'COMPROMISED', 'REMANDED', 'ABATED', 'PENDING',
            ])],
            'outcome_detail'        => ['nullable', 'string', 'max:4000'],
        ]);

        $litigation->update($data + [
            'is_pending'            => $request->boolean('is_pending'),
            'has_restraining_order' => $request->boolean('has_restraining_order'),
        ]);

        $this->syncSubJudice($application, $request);
        $application->refresh();

        return back()->with('status', $application->is_sub_judice
            ? 'Case updated. The application remains sub judice.'
            : 'Case updated. No case is now pending and no stay subsists, so the application may proceed.');
    }

    /**
     * Keep the application's sub-judice flag true to the litigation register,
     * and move it in or out of the parked state.
     */
    private function syncSubJudice(Application $application, Request $request): void
    {
        $blocked = $application->litigations()
            ->where(fn ($q) => $q->where('is_pending', true)->orWhere('has_restraining_order', true))
            ->exists();

        $application->forceFill(['is_sub_judice' => $blocked])->save();

        $workflow = app(WorkflowService::class);
        $user = $request->user();

        if ($blocked && $application->status === WorkflowService::SITE_INSPECTION) {
            $workflow->transition(
                $application->id, WorkflowService::SUB_JUDICE,
                $user->id, $user->primaryRole()?->code,
                'Parked: a case is pending or a restraining order subsists.', $request->ip(),
            );
        }

        if (! $blocked && $application->status === WorkflowService::SUB_JUDICE) {
            $workflow->transition(
                $application->id, WorkflowService::SITE_INSPECTION,
                $user->id, $user->primaryRole()?->code,
                'Released: no case pending and no stay subsisting.', $request->ip(),
            );
        }
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
