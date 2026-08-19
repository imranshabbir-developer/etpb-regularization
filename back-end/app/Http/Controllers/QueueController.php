<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Officer work queues.
 *
 * Each queue answers one question: what is sitting on my desk right now. They
 * are deliberately separate lists rather than filters on the master list,
 * because an officer's day is organised by the act they are about to perform,
 * not by the state an application happens to be in.
 *
 * Unpaid applications are excluded from every processing queue. The department
 * does not work an application whose fee is still pending, so it should not
 * appear in a work list at all.
 */
class QueueController extends Controller
{
    /** Applications waiting to be scrutinised, and deposits waiting on Accounts. */
    public function scrutiny(Request $request): View
    {
        $user = $request->user();

        $awaitingPayment = Application::query()->visibleTo($user)
            ->with(['applicant:id,full_name', 'district:id,name', 'feePayments'])
            ->where('payment_status', 'PENDING')
            ->whereIn('status', [
                WorkflowService::SUBMITTED,
                WorkflowService::FEE_VERIFICATION,
            ])
            ->orderBy('submitted_at')
            ->get();

        $toScrutinise = Application::query()->visibleTo($user)
            ->with(['applicant:id,full_name', 'property:id,property_no', 'district:id,name'])
            ->where('payment_status', 'PAID')
            ->whereIn('status', [
                WorkflowService::FEE_VERIFICATION,
                WorkflowService::SCRUTINY,
                WorkflowService::SITE_INSPECTION,
            ])
            ->orderBy('submitted_at')
            ->paginate(25);

        return view('queues.scrutiny', [
            'awaitingPayment' => $awaitingPayment,
            'applications'    => $toScrutinise,
        ]);
    }

    /** Assessment work, ordered by how close the 60-day limit is. */
    public function assessment(Request $request): View
    {
        $user = $request->user();

        $applications = Application::query()->visibleTo($user)
            ->with(['applicant:id,full_name', 'property:id,property_no', 'district:id,name'])
            ->where('payment_status', 'PAID')
            ->whereIn('status', [
                WorkflowService::ASSESSMENT_PROPOSED,
                WorkflowService::NOTICE_ISSUED,
                WorkflowService::OBJECTION_WINDOW,
                WorkflowService::HEARING,
                WorkflowService::RENT_FIXED,
            ])
            ->orderByRaw('COALESCE(assessment_extended_to, assessment_due_date) IS NULL')
            ->orderByRaw('COALESCE(assessment_extended_to, assessment_due_date) ASC')
            ->paginate(25);

        return view('queues.assessment', ['applications' => $applications]);
    }

    /** Objections still undecided, oldest first — these hold up the fixation. */
    public function objections(Request $request): View
    {
        $user = $request->user();

        $objections = DB::table('objections as o')
            ->join('applications as a', 'a.id', '=', 'o.application_id')
            ->leftJoin('districts as d', 'd.id', '=', 'a.district_id')
            ->leftJoin('public_notices as n', 'n.id', '=', 'o.public_notice_id')
            ->whereNull('o.deleted_at')->whereNull('a.deleted_at')
            ->whereIn('o.status', ['FILED', 'UNDER_HEARING'])
            ->when(! $user->hasPermission('applications.view_all'),
                   fn ($q) => $q->where('a.district_id', $user->district_id))
            ->select('o.id', 'o.objection_no', 'o.objector_name', 'o.filed_on', 'o.is_within_time',
                     'o.status', 'o.plea', 'a.id as application_id', 'a.application_no',
                     'd.name as district', 'n.objection_deadline')
            ->orderBy('o.filed_on')
            ->paginate(25);

        return view('queues.objections', ['objections' => $objections]);
    }

    /** Where the money is: assessed, recovered, outstanding. */
    public function arrears(Request $request): View
    {
        $user = $request->user();

        $applications = Application::query()->visibleTo($user)
            ->with(['applicant:id,full_name', 'district:id,name'])
            ->where('arrears_balance', '>', 0)
            ->orderByDesc('arrears_balance')
            ->paginate(25);

        $totals = Application::query()->visibleTo($user)
            ->selectRaw('COALESCE(SUM(total_arrears),0) a, COALESCE(SUM(arrears_paid),0) p, '
                      . 'COALESCE(SUM(arrears_balance),0) b')
            ->first();

        return view('queues.arrears', [
            'applications' => $applications,
            'totals'       => $totals,
        ]);
    }

    /** The sub-judice inventory. */
    public function litigation(Request $request): View
    {
        $user = $request->user();

        $litigations = DB::table('litigations as l')
            ->leftJoin('applications as a', 'a.id', '=', 'l.application_id')
            ->leftJoin('districts as d', 'd.id', '=', 'a.district_id')
            ->leftJoin('applicants as ap', 'ap.id', '=', 'a.applicant_id')
            ->whereNull('l.deleted_at')
            ->when(! $user->hasPermission('applications.view_all'),
                   fn ($q) => $q->where('a.district_id', $user->district_id))
            ->select('l.*', 'a.application_no', 'a.id as application_id',
                     'd.name as district', 'ap.full_name as applicant')
            ->orderByDesc('l.is_pending')
            ->orderBy('l.next_hearing_date')
            ->paginate(25);

        return view('queues.litigation', [
            'litigations' => $litigations,
            'today'       => Carbon::today(),
        ]);
    }
}
