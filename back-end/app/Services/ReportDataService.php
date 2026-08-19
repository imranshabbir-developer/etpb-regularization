<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Gathers the figures behind the reports.
 *
 * One source of truth for every format: the Chairman's one-page glimpse, the
 * Administrator's full consolidated report, and the Excel export all read from
 * here, so the same question never gets two different answers depending on
 * which button was pressed.
 */
class ReportDataService
{
    /**
     * Headline figures, scoped to what the user may see and optionally to one
     * district.
     *
     * @return array<string, mixed>
     */
    public function headline(User $user, ?int $districtId = null): array
    {
        $scope = fn () => Application::query()
            ->visibleTo($user)
            ->when($districtId, fn ($q) => $q->where('district_id', $districtId));

        $byStatus = $scope()->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        $all         = (int) $scope()->count();
        $regularized = (int) ($byStatus[WorkflowService::REGULARIZED] ?? 0);
        $rejected    = (int) ($byStatus[WorkflowService::REJECTED] ?? 0)
                     + (int) ($byStatus[WorkflowService::REJECTED_INELIGIBLE] ?? 0);
        $disposed    = $regularized + $rejected;

        $fee = DB::table('fee_payments as f')
            ->join('applications as a', 'a.id', '=', 'f.application_id')
            ->where('f.status', 'VERIFIED')->whereNull('f.deleted_at')->whereNull('a.deleted_at')
            ->when($districtId, fn ($q) => $q->where('a.district_id', $districtId))
            ->selectRaw('COUNT(*) n, COALESCE(SUM(f.amount),0) total')->first();

        $area = DB::table('applications as a')
            ->join('property_areas as pa', function ($j) {
                $j->on('pa.property_id', '=', 'a.property_id')->where('pa.is_current', true);
            })
            ->where('a.status', WorkflowService::REGULARIZED)->whereNull('a.deleted_at')
            ->when($districtId, fn ($q) => $q->where('a.district_id', $districtId))
            ->sum('pa.area_sqft');

        $assessed  = (string) ($scope()->sum('total_arrears') ?: '0');
        $recovered = (string) ($scope()->sum('arrears_paid') ?: '0');

        return [
            'all'           => $all,
            'open'          => (int) $scope()->open()->count(),
            'regularized'   => $regularized,
            'rejected'      => $rejected,
            'disposed'      => $disposed,
            'disposal_rate' => $all > 0 ? round($disposed / $all * 100) : 0,
            'pending_pay'   => (int) $scope()->where('payment_status', 'PENDING')->count(),
            'paid'          => (int) $scope()->where('payment_status', 'PAID')->count(),
            'sub_judice'    => (int) $scope()->where('is_sub_judice', true)->count(),
            'assessed'      => $assessed,
            'recovered'     => $recovered,
            'outstanding'   => (string) ($scope()->sum('arrears_balance') ?: '0'),
            'recovery_rate' => (float) $assessed > 0 ? round((float) $recovered / (float) $assessed * 100) : 0,
            'monthly_rent'  => (string) ($scope()->sum('assessed_monthly_rent') ?: '0'),
            'area_sqft'     => (string) ($area ?: '0'),
            'fee_count'     => (int) ($fee->n ?? 0),
            'fee_total'     => (string) ($fee->total ?? '0'),
            'by_status'     => $byStatus,
        ];
    }

    /**
     * How the office is performing against the two statutory clocks.
     *
     * @return array<string, mixed>
     */
    public function performance(User $user, ?int $districtId = null): array
    {
        $today = Carbon::today()->toDateString();

        $scope = fn () => Application::query()
            ->visibleTo($user)
            ->when($districtId, fn ($q) => $q->where('district_id', $districtId));

        $assessmentOverdue = (int) $scope()->open()
            ->whereNotNull('assessment_due_date')->whereNull('rent_fixed_at')
            ->whereDate(DB::raw('COALESCE(assessment_extended_to, assessment_due_date)'), '<', $today)
            ->count();

        $assessmentLive = (int) $scope()->open()
            ->whereNotNull('assessment_due_date')->whereNull('rent_fixed_at')->count();

        $approvalOverdue = (int) $scope()
            ->where('status', WorkflowService::PENDING_ADMIN_APPROVAL)
            ->whereDate('admin_approval_due_date', '<', $today)->count();

        $approvalLive = (int) $scope()
            ->where('status', WorkflowService::PENDING_ADMIN_APPROVAL)->count();

        // Average days from submission to regularization, for cases that got there.
        $avgDays = DB::table('applications')
            ->whereNull('deleted_at')
            ->whereNotNull('submitted_at')->whereNotNull('regularized_at')
            ->when($districtId, fn ($q) => $q->where('district_id', $districtId))
            ->selectRaw('AVG(DATEDIFF(regularized_at, submitted_at)) d')->value('d');

        $approvalsWithinSla = DB::table('approvals as ap')
            ->join('applications as a', 'a.id', '=', 'ap.application_id')
            ->where('ap.level', 'ADMINISTRATOR')->whereNull('ap.deleted_at')
            ->when($districtId, fn ($q) => $q->where('a.district_id', $districtId))
            ->selectRaw('COUNT(*) n, SUM(ap.is_within_sla) ok')->first();

        return [
            'assessment_overdue' => $assessmentOverdue,
            'assessment_live'    => $assessmentLive,
            'assessment_ontime'  => $assessmentLive > 0
                ? round(($assessmentLive - $assessmentOverdue) / $assessmentLive * 100) : 100,
            'approval_overdue'   => $approvalOverdue,
            'approval_live'      => $approvalLive,
            'approval_ontime'    => $approvalLive > 0
                ? round(($approvalLive - $approvalOverdue) / $approvalLive * 100) : 100,
            'avg_days'           => $avgDays !== null ? round((float) $avgDays) : null,
            'approvals_total'    => (int) ($approvalsWithinSla->n ?? 0),
            'approvals_in_time'  => (int) ($approvalsWithinSla->ok ?? 0),
            'approvals_pct'      => ($approvalsWithinSla->n ?? 0) > 0
                ? round((int) $approvalsWithinSla->ok / (int) $approvalsWithinSla->n * 100) : 100,
        ];
    }

    /** District league table. */
    public function byDistrict(User $user, ?int $districtId = null): Collection
    {
        return Application::query()
            ->visibleTo($user)
            ->when($districtId, fn ($q) => $q->where('district_id', $districtId))
            ->select('district_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(status = 'REGULARIZED') as regularized"),
                DB::raw("SUM(payment_status = 'PAID') as paid"),
                DB::raw("SUM(is_sub_judice = 1) as sub_judice"),
                DB::raw('SUM(total_arrears) as assessed'),
                DB::raw('SUM(arrears_paid) as recovered'),
                DB::raw('SUM(arrears_balance) as outstanding'))
            ->groupBy('district_id')
            ->with('district:id,name')
            ->get()
            ->sortByDesc('total')
            ->values();
    }

    /** Applications submitted per month, last twelve. */
    public function monthlyIntake(User $user, ?int $districtId = null): Collection
    {
        $rows = Application::query()
            ->visibleTo($user)
            ->when($districtId, fn ($q) => $q->where('district_id', $districtId))
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', Carbon::today()->subMonths(11)->startOfMonth())
            ->select(DB::raw("DATE_FORMAT(submitted_at, '%Y-%m') ym"), DB::raw('COUNT(*) n'))
            ->groupBy('ym')->orderBy('ym')->pluck('n', 'ym');

        // Fill the gaps so a quiet month reads as zero rather than vanishing.
        $out = collect();
        for ($i = 11; $i >= 0; $i--) {
            $key = Carbon::today()->subMonths($i)->format('Y-m');
            $out->put($key, (int) ($rows[$key] ?? 0));
        }

        return $out;
    }

    /** Statutory deadline breaches, named by the officer answerable. */
    public function breaches(User $user, ?int $districtId = null): array
    {
        $today = Carbon::today()->toDateString();

        $scope = fn () => Application::query()
            ->visibleTo($user)
            ->when($districtId, fn ($q) => $q->where('district_id', $districtId));

        return [
            'assessment' => $scope()->open()
                ->whereNotNull('assessment_due_date')->whereNull('rent_fixed_at')
                ->whereDate(DB::raw('COALESCE(assessment_extended_to, assessment_due_date)'), '<', $today)
                ->with(['districtOfficer:id,name', 'district:id,name', 'applicant:id,full_name'])
                ->orderBy('assessment_due_date')->get(),

            'approval' => $scope()
                ->where('status', WorkflowService::PENDING_ADMIN_APPROVAL)
                ->whereDate('admin_approval_due_date', '<', $today)
                ->with(['administrator:id,name', 'district:id,name', 'applicant:id,full_name'])
                ->orderBy('admin_approval_due_date')->get(),
        ];
    }

    /** Objection counts and outcomes. */
    public function objections(?int $districtId = null): array
    {
        $counts = DB::table('objections as o')
            ->join('applications as a', 'a.id', '=', 'o.application_id')
            ->whereNull('o.deleted_at')->whereNull('a.deleted_at')
            ->when($districtId, fn ($q) => $q->where('a.district_id', $districtId))
            ->selectRaw("COUNT(*) filed, SUM(o.status = 'DECIDED') decided,
                         SUM(o.is_within_time = 0) late")->first();

        $outcomes = DB::table('objection_decisions as d')
            ->join('objections as o', 'o.id', '=', 'd.objection_id')
            ->join('applications as a', 'a.id', '=', 'o.application_id')
            ->whereNull('d.deleted_at')
            ->when($districtId, fn ($q) => $q->where('a.district_id', $districtId))
            ->select('d.decision', DB::raw('COUNT(*) n'))
            ->groupBy('d.decision')->pluck('n', 'decision');

        return [
            'filed'    => (int) ($counts->filed ?? 0),
            'decided'  => (int) ($counts->decided ?? 0),
            'open'     => (int) ($counts->filed ?? 0) - (int) ($counts->decided ?? 0),
            'late'     => (int) ($counts->late ?? 0),
            'outcomes' => $outcomes,
        ];
    }
}
