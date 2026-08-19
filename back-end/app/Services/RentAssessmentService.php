<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Builds the year-by-year rent schedule that the arrears ledger is drawn from.
 *
 * Statutory basis
 * ---------------
 * Clause 10(i)   assessment / re-assessment is made with effect from 01-07-2006
 * Clause 11(i)   periodical re-assessment after every six years
 * Clause 11(ii)  enhancement in rent at eight per cent per annum
 * Clause 3(ii)(b) arrears run from 01-07-2000, the date of occupation, or the
 *                date of a judicial verdict — whichever is earlier
 *
 * Rent year
 * ---------
 * Both statutory anchors fall on 1 July, so a rent year runs 1 July to 30 June
 * and is labelled by the calendar year it starts in. Year 2000 means
 * 01-07-2000 to 30-06-2001.
 *
 * Anchoring
 * ---------
 * The rent the District Officer determines is the rent as at the round's
 * `effective_from` date (by default the 01-07-2006 base date of Clause 10).
 * Years after that anchor are enhanced at 8% per annum; years before it are
 * back-cast at the same rate, because arrears reach back to 2000 while the
 * assessment itself is anchored in 2006.
 *
 * Simple vs compound
 * ------------------
 * Clause 11(ii) says "eight per cent per annum" without saying whether it
 * compounds. Over 24 years the difference is roughly 6.34x the base against
 * 2.92x. Both are implemented; the method in force is a dated setting and is
 * stamped onto every round and every generated schedule. A written ETPB ruling
 * is still outstanding — see MASTER_PLAN section 14, risk R1.
 */
class RentAssessmentService
{
    /** Money scale. */
    public const MONEY_SCALE = 2;

    /** Internal scale for growth factors, so rounding happens once at the end. */
    private const FACTOR_SCALE = 12;

    public function __construct(
        private readonly SettingService $settings,
    ) {
    }

    /**
     * Rent for a given rent-year, derived from the anchored determined rent.
     *
     * @param  string  $baseRent    monthly rent as at the anchor year
     * @param  int     $anchorYear  rent year the determined rent belongs to
     * @param  int     $targetYear  rent year being computed
     * @param  string  $ratePct     enhancement rate, e.g. '8.00'
     * @param  string  $method      SIMPLE | COMPOUND
     */
    public function rentForYear(
        string $baseRent,
        int $anchorYear,
        int $targetYear,
        string $ratePct = '8.00',
        string $method = 'COMPOUND',
    ): string {
        $n = $targetYear - $anchorYear;

        if ($n === 0) {
            return bcadd($baseRent, '0', self::MONEY_SCALE);
        }

        $rate = bcdiv($ratePct, '100', self::FACTOR_SCALE);   // 0.08
        $method = strtoupper($method);

        if ($method === 'COMPOUND') {
            $factor = bcpow(bcadd('1', $rate, self::FACTOR_SCALE), (string) abs($n), self::FACTOR_SCALE);

            $raw = $n > 0
                ? bcmul($baseRent, $factor, self::FACTOR_SCALE)
                : bcdiv($baseRent, $factor, self::FACTOR_SCALE);

            return $this->money($raw);
        }

        if ($method === 'SIMPLE') {
            $growth = bcmul($rate, (string) abs($n), self::FACTOR_SCALE);
            $factor = bcadd('1', $growth, self::FACTOR_SCALE);

            $raw = $n > 0
                ? bcmul($baseRent, $factor, self::FACTOR_SCALE)
                : bcdiv($baseRent, $factor, self::FACTOR_SCALE);

            return $this->money($raw);
        }

        throw new InvalidArgumentException("Unknown enhancement method [{$method}].");
    }

    /**
     * Generate (and persist) the full rent schedule for an assessment round.
     *
     * Any previous schedule for the round is replaced — a round can be
     * regenerated while it is still open, but a round that has already fed an
     * approved regularization is superseded rather than edited.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generateSchedule(int $roundId): array
    {
        $round = DB::table('assessment_rounds')->where('id', $roundId)->first();
        if (! $round) {
            throw new RuntimeException("Assessment round [{$roundId}] not found.");
        }
        if ($round->determined_monthly_rent === null) {
            throw new RuntimeException(
                'The District Officer must determine the rent before a schedule can be generated.'
            );
        }

        $possession = DB::table('possession_details')
            ->where('application_id', $round->application_id)
            ->whereNull('deleted_at')
            ->first();
        if (! $possession) {
            throw new RuntimeException('Possession details are missing for this application.');
        }

        $arrearsFrom = Carbon::parse($possession->arrears_from);
        $anchorDate  = Carbon::parse($round->effective_from ?: $round->base_date);

        $startYear = $this->rentYearOf($arrearsFrom);
        $endYear   = $this->rentYearOf(Carbon::today());
        $anchorYear = $this->rentYearOf($anchorDate);

        $rate      = (string) $round->enhancement_rate;
        $method    = $round->enhancement_method;
        $baseRent  = (string) $round->determined_monthly_rent;
        $cycle     = max(1, (int) $round->reassessment_cycle_years);
        $milestones = $this->settings->milestoneYears();

        $today = Carbon::today();

        $rows = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $periodStart = Carbon::create($year, 7, 1)->startOfDay();
            $periodEnd   = Carbon::create($year + 1, 6, 30)->startOfDay();

            // The first period may open part-way through a rent year.
            if ($periodStart->lt($arrearsFrom)) {
                $periodStart = $arrearsFrom->copy()->startOfDay();
            }

            // The current rent year has not run its course. Rent for months
            // that have not yet fallen due is not arrears, so the closing
            // period stops at today rather than the following 30 June.
            if ($periodEnd->gt($today)) {
                $periodEnd = $today->copy();
            }

            $months  = $this->monthsInPeriod($year, $periodStart, $periodEnd);
            $monthly = $this->rentForYear($baseRent, $anchorYear, $year, $rate, $method);
            $annual  = $this->money(bcmul($monthly, $months, self::FACTOR_SCALE));

            $elapsed = $year - $anchorYear;
            $rows[] = [
                'assessment_round_id'     => $roundId,
                'application_id'          => $round->application_id,
                'year'                    => $year,
                'period_from'             => $periodStart->toDateString(),
                'period_to'               => $periodEnd->toDateString(),
                'monthly_rent'            => $monthly,
                'annual_rent'             => $annual,
                'enhancement_applied_pct' => $elapsed === 0 ? '0' : $rate,
                'years_elapsed'           => abs($elapsed),
                'is_reassessment_year'    => $elapsed !== 0 && ($elapsed % $cycle === 0),
                'is_milestone_year'       => in_array($year, $milestones, true),
                'computation_note'        => $this->note($baseRent, $anchorYear, $year, $rate, $method, $months),
                'created_at'              => now(),
                'updated_at'              => now(),
            ];
        }

        DB::transaction(function () use ($roundId, $rows) {
            DB::table('rent_schedules')->where('assessment_round_id', $roundId)->delete();
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('rent_schedules')->insert($chunk);
            }
        });

        return $rows;
    }

    /**
     * The milestone grid the requirements ask for (2000, 2004, ... 2024).
     *
     * This is a presentation view over the year-by-year schedule; the six-year
     * statutory cycle of Clause 11(i) still governs the underlying computation.
     *
     * @return array<int, array<string, mixed>>
     */
    public function milestoneTable(int $applicationId): array
    {
        $milestones = $this->settings->milestoneYears();

        $rows = DB::table('rent_schedules')
            ->where('application_id', $applicationId)
            ->whereIn('year', $milestones)
            ->orderBy('year')
            ->get();

        $byYear = $rows->keyBy('year');

        $out = [];
        foreach ($milestones as $year) {
            $r = $byYear->get($year);
            $out[] = [
                'year'         => $year,
                'period'       => sprintf('01-07-%d to 30-06-%d', $year, $year + 1),
                'monthly_rent' => $r?->monthly_rent,
                'annual_rent'  => $r?->annual_rent,
                'in_scope'     => $r !== null,
            ];
        }

        return $out;
    }

    /**
     * Total rent falling due across the whole schedule.
     */
    public function totalAssessed(int $applicationId): string
    {
        $total = DB::table('rent_schedules')
            ->where('application_id', $applicationId)
            ->sum('annual_rent');

        return bcadd((string) ($total ?: '0'), '0', self::MONEY_SCALE);
    }

    /**
     * Round a decimal string to money scale, half away from zero.
     *
     * BCMath truncates, which would shave a fraction off every year of every
     * assessment and systematically under-charge. Rent is a statutory demand,
     * so it is rounded conventionally instead.
     */
    private function money(string $value): string
    {
        $negative = str_starts_with($value, '-');
        $abs = ltrim($value, '-');

        $half = '0.' . str_repeat('0', self::MONEY_SCALE) . '5';
        $rounded = bcadd($abs, $half, self::MONEY_SCALE + 1);
        $rounded = bcadd($rounded, '0', self::MONEY_SCALE);

        return $negative && bccomp($rounded, '0', self::MONEY_SCALE) !== 0
            ? '-' . $rounded
            : $rounded;
    }

    /**
     * The rent year a date falls in. A rent year opens on 1 July.
     */
    public function rentYearOf(Carbon $date): int
    {
        return $date->month >= 7 ? $date->year : $date->year - 1;
    }

    /**
     * Months chargeable in a period, proportioned by actual days so a partial
     * opening year is not over-charged.
     */
    private function monthsInPeriod(int $year, Carbon $from, Carbon $to): string
    {
        // Both bounds are normalised to the start of their day and the day
        // counts cast to int. Carbon returns a float from diffInDays, and
        // comparing a whole-year span against an end-of-day boundary otherwise
        // yields 364.99999 days — enough to make a complete year measure
        // 11.9672 months and quietly under-charge every full year assessed.
        $fullStart = Carbon::create($year, 7, 1)->startOfDay();
        $fullEnd   = Carbon::create($year + 1, 6, 30)->startOfDay();

        $fullDays   = (int) $fullStart->diffInDays($fullEnd) + 1;
        $actualDays = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        if ($actualDays >= $fullDays) {
            return '12.0000';
        }

        return bcdiv(bcmul((string) $actualDays, '12', 8), (string) $fullDays, 4);
    }

    private function note(
        string $base,
        int $anchorYear,
        int $year,
        string $rate,
        string $method,
        string $months,
    ): string {
        $n = $year - $anchorYear;

        if ($n === 0) {
            return sprintf(
                'Rent as determined by the District Officer, anchored at rent year %d. %s months chargeable.',
                $anchorYear,
                rtrim(rtrim($months, '0'), '.')
            );
        }

        $direction = $n > 0 ? 'enhanced' : 'back-cast';
        $formula = strtoupper($method) === 'COMPOUND'
            ? sprintf('%s x (1 + %s%%)^%d', $base, $rate, abs($n))
            : sprintf('%s x (1 + %s%% x %d)', $base, $rate, abs($n));

        return sprintf(
            '%s %d year(s) from anchor year %d at %s%% per annum (%s): %s. %s months chargeable. [Clause 11(ii)]',
            ucfirst($direction),
            abs($n),
            $anchorYear,
            $rate,
            strtolower($method),
            $formula,
            rtrim(rtrim($months, '0'), '.')
        );
    }
}
