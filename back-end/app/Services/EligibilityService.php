<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * The two date tests at the front of every application.
 *
 * Clause 3(ii)(a) — the occupant must be in actual physical possession prior to
 * the 1st day of January, 2010, "or from such date... as shall be determined
 * and notified by the Board from time to time". The cut-off is therefore read
 * from a dated setting rather than hard-coded.
 *
 * Clause 3(ii)(b) — arrears run from the 1st day of July, 2000, or the date of
 * actual physical occupation, or the date of a judicial verdict or declaration
 * by any court or authority, whichever is earlier.
 */
class EligibilityService
{
    public function __construct(
        private readonly SettingService $settings,
    ) {
    }

    /**
     * @return array{
     *   is_eligible: bool,
     *   reason: string,
     *   cutoff_applied: string,
     *   arrears_from: string,
     *   arrears_from_basis: string,
     *   candidates: array<string, string|null>
     * }
     */
    public function assess(string $dateOfPossession, ?string $judicialVerdictDate = null): array
    {
        $possession = Carbon::parse($dateOfPossession)->startOfDay();
        $cutoff     = $this->cutoffDate();

        $eligible = $possession->lte($cutoff);

        $reason = $eligible
            ? sprintf(
                'Possession dated %s is on or before the cut-off of %s, so the occupant may be '
                . 'treated as a tenant under Clause 3(ii)(a) of the Scheme 1977.',
                $possession->format('d-m-Y'),
                $cutoff->format('d-m-Y')
            )
            : sprintf(
                'Possession dated %s falls after the cut-off of %s. Clause 3(ii)(a) of the '
                . 'Scheme 1977 requires actual physical possession prior to 01-01-2010, so the '
                . 'application is not eligible for regularization.',
                $possession->format('d-m-Y'),
                $cutoff->format('d-m-Y')
            );

        $arrears = $this->arrearsFrom($dateOfPossession, $judicialVerdictDate);

        return [
            'is_eligible'        => $eligible,
            'reason'             => $reason,
            'cutoff_applied'     => $cutoff->toDateString(),
            'arrears_from'       => $arrears['date'],
            'arrears_from_basis' => $arrears['basis'],
            'candidates'         => $arrears['candidates'],
        ];
    }

    /**
     * The earliest of the three dates named in Clause 3(ii)(b).
     *
     * All three candidates are returned so the officer — and the case file —
     * can see which one governed and why.
     *
     * @return array{date: string, basis: string, candidates: array<string, string|null>}
     */
    public function arrearsFrom(string $dateOfPossession, ?string $judicialVerdictDate = null): array
    {
        $statutory  = $this->settings->date('arrears_base_date', '2000-07-01');
        $occupation = Carbon::parse($dateOfPossession)->startOfDay();
        $judicial   = $judicialVerdictDate ? Carbon::parse($judicialVerdictDate)->startOfDay() : null;

        $candidates = [
            'STATUTORY_2000'    => $statutory,
            'DATE_OF_OCCUPATION' => $occupation,
            'JUDICIAL_VERDICT'  => $judicial,
        ];

        $basis = 'STATUTORY_2000';
        $earliest = $statutory;

        foreach ($candidates as $key => $date) {
            if ($date !== null && $date->lt($earliest)) {
                $earliest = $date;
                $basis = $key;
            }
        }

        return [
            'date'  => $earliest->toDateString(),
            'basis' => $basis,
            'candidates' => [
                'STATUTORY_2000'     => $statutory->toDateString(),
                'DATE_OF_OCCUPATION' => $occupation->toDateString(),
                'JUDICIAL_VERDICT'   => $judicial?->toDateString(),
            ],
        ];
    }

    public function cutoffDate(): Carbon
    {
        return $this->settings->date('possession_cutoff_date', '2009-12-31');
    }

    /**
     * Whether a possession date passes the cut-off, without building the
     * full assessment — for inline form validation.
     */
    public function isWithinCutoff(string $dateOfPossession): bool
    {
        return Carbon::parse($dateOfPossession)->startOfDay()->lte($this->cutoffDate());
    }
}
