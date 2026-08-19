<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Converts Pakistani land measurements to square feet.
 *
 * The applicant may enter an area as a single figure in any unit, or as a
 * compound expression such as "2 Kanal 7 Marla 3 Sarsai". Everything is
 * canonicalised to square feet, because every downstream rent computation is
 * per square foot.
 *
 * Two Marla standards are in use and they differ by 21%:
 *
 *   REVENUE  1 Marla = 272.25 sqft, 1 Kanal = 20 Marla = 5,445 sqft
 *   URBAN    1 Marla = 225.00 sqft, 1 Kanal = 20 Marla = 4,500 sqft
 *
 * Which one applies is a per-district (or per-application) decision, so the
 * factors are data. Every conversion returns a trace that is stored alongside
 * the result — a later edit to a factor must never silently restate a rent
 * that has already been assessed.
 *
 * All arithmetic is BCMath on decimal strings. Floats are never used: an area
 * feeds a money figure, and 272.25 * 3 must be exactly 816.75.
 */
class AreaConversionService
{
    /** Working precision. Areas are stored as DECIMAL(18,4). */
    public const SCALE = 4;

    private const CACHE_TTL = 3600;

    /**
     * Convert a set of unit components to square feet.
     *
     * @param  array<string, string|float|int|null>  $components  e.g. ['KANAL' => 2, 'MARLA' => 7]
     * @param  int|string  $profile  profile id, or profile code such as 'REVENUE'
     * @return array{sqft: string, trace: array<string, mixed>}
     */
    public function toSqft(array $components, int|string $profile): array
    {
        $factors = $this->factors($profile);
        $profileMeta = $this->profile($profile);

        $total = '0';
        $lines = [];

        foreach ($components as $unitCode => $value) {
            $unitCode = strtoupper(trim((string) $unitCode));

            if ($value === null || $value === '' || $this->isZero((string) $value)) {
                continue;
            }
            if (! isset($factors[$unitCode])) {
                throw new InvalidArgumentException(
                    "Unit [{$unitCode}] is not defined in conversion profile [{$profileMeta->code}]."
                );
            }

            $qty = $this->normalise((string) $value);
            if (bccomp($qty, '0', self::SCALE) < 0) {
                throw new InvalidArgumentException("Area component [{$unitCode}] cannot be negative.");
            }

            $factor = $factors[$unitCode]->sqft_per_unit;
            $sub = bcmul($qty, $factor, self::SCALE);
            $total = bcadd($total, $sub, self::SCALE);

            $lines[] = [
                'unit_code'     => $unitCode,
                'unit_name'     => $factors[$unitCode]->unit_name,
                'quantity'      => $qty,
                'sqft_per_unit' => $factor,
                'subtotal_sqft' => $sub,
                'expression'    => "{$qty} {$factors[$unitCode]->unit_name} x {$factor} = {$sub} sqft",
            ];
        }

        if ($lines === []) {
            throw new InvalidArgumentException('At least one area component must be greater than zero.');
        }

        return [
            'sqft'  => $total,
            'trace' => [
                'profile_id'   => $profileMeta->id,
                'profile_code' => $profileMeta->code,
                'profile_name' => $profileMeta->name,
                'components'   => $lines,
                'total_sqft'   => $total,
                'computed_at'  => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Convert square feet into a single unit.
     */
    public function fromSqft(string $sqft, string $unitCode, int|string $profile): string
    {
        $factors = $this->factors($profile);
        $unitCode = strtoupper($unitCode);

        if (! isset($factors[$unitCode])) {
            throw new InvalidArgumentException("Unit [{$unitCode}] is not defined in this profile.");
        }

        return bcdiv($this->normalise($sqft), $factors[$unitCode]->sqft_per_unit, self::SCALE);
    }

    /**
     * Break square feet down into the profile's compound units, largest first —
     * the form a revenue record actually uses, e.g. "2 Kanal 7 Marla 3 Sarsai".
     *
     * @return array{parts: array<int, array{unit_code:string,unit_name:string,quantity:string}>,
     *               remainder_sqft: string, label: string}
     */
    public function toCompound(string $sqft, int|string $profile, bool $includeAcre = false): array
    {
        $remaining = $this->normalise($sqft);

        $units = collect($this->factors($profile))
            ->filter(fn ($f) => (bool) $f->is_compound_component)
            ->when(! $includeAcre, fn ($c) => $c->reject(fn ($f) => in_array($f->unit_code, ['ACRE', 'MURABBA'], true)))
            ->sortByDesc(fn ($f) => (float) $f->sqft_per_unit)
            ->values();

        $parts = [];
        foreach ($units as $unit) {
            $whole = bcdiv($remaining, $unit->sqft_per_unit, 0);
            if (bccomp($whole, '0', 0) > 0) {
                $parts[] = [
                    'unit_code' => $unit->unit_code,
                    'unit_name' => $unit->unit_name,
                    'quantity'  => $whole,
                ];
                $remaining = bcsub($remaining, bcmul($whole, $unit->sqft_per_unit, self::SCALE), self::SCALE);
            }
        }

        $label = $parts === []
            ? '0 ' . ($units->first()->unit_name ?? 'sqft')
            : implode(' ', array_map(fn ($p) => "{$p['quantity']} {$p['unit_name']}", $parts));

        if (bccomp($remaining, '0', self::SCALE) > 0) {
            $label .= ' ' . rtrim(rtrim($remaining, '0'), '.') . ' sqft';
        }

        return [
            'parts'          => $parts,
            'remainder_sqft' => $remaining,
            'label'          => $label,
        ];
    }

    /**
     * Units available for data entry under a profile, in display order.
     *
     * @return array<int, object>
     */
    public function units(int|string $profile): array
    {
        return array_values($this->factors($profile));
    }

    /** @return array<int, object> */
    public function profiles(): array
    {
        $rows = Cache::remember('unit_profiles:all', self::CACHE_TTL, fn () => DB::table('unit_conversion_profiles')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all());

        return array_map(static fn (array $r) => (object) $r, $rows);
    }

    public function defaultProfileId(): int
    {
        $row = DB::table('unit_conversion_profiles')->where('is_default', true)->first();

        if (! $row) {
            throw new \RuntimeException('No default area conversion profile is configured.');
        }

        return (int) $row->id;
    }

    /**
     * The profile a district uses, falling back to the system default.
     */
    public function profileForDistrict(?int $districtId): int
    {
        if ($districtId) {
            $id = DB::table('districts')->where('id', $districtId)->value('unit_profile_id');
            if ($id) {
                return (int) $id;
            }
        }

        return $this->defaultProfileId();
    }

    /** @return array<string, object> keyed by unit code */
    private function factors(int|string $profile): array
    {
        $meta = $this->profile($profile);

        // Cache plain arrays, not DB row objects: cache drivers restrict which
        // classes they will unserialize, and a half-restored row is worse than
        // a cache miss when the value feeds a rent computation.
        $rows = Cache::remember(
            "unit_factors:{$meta->id}",
            self::CACHE_TTL,
            fn () => DB::table('unit_conversion_factors')
                ->where('unit_profile_id', $meta->id)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get()
                ->keyBy('unit_code')
                ->map(fn ($r) => (array) $r)
                ->all()
        );

        return array_map(static fn (array $r) => (object) $r, $rows);
    }

    private function profile(int|string $profile): object
    {
        $key = 'unit_profile:' . $profile;

        $row = Cache::remember($key, self::CACHE_TTL, function () use ($profile) {
            $q = DB::table('unit_conversion_profiles');

            $found = is_int($profile) || ctype_digit((string) $profile)
                ? $q->where('id', (int) $profile)->first()
                : $q->where('code', strtoupper((string) $profile))->first();

            return $found ? (array) $found : null;
        });

        if (! $row) {
            throw new InvalidArgumentException("Unknown area conversion profile [{$profile}].");
        }

        return (object) $row;
    }

    /** Strip thousands separators and normalise to the working scale. */
    private function normalise(string $value): string
    {
        $clean = str_replace([',', ' ', '_'], '', trim($value));

        if ($clean === '' || ! is_numeric($clean)) {
            throw new InvalidArgumentException("Value [{$value}] is not a valid number.");
        }

        return bcadd($clean, '0', self::SCALE);
    }

    private function isZero(string $value): bool
    {
        $clean = str_replace([',', ' ', '_'], '', trim($value));

        return is_numeric($clean) && bccomp(bcadd($clean, '0', self::SCALE), '0', self::SCALE) === 0;
    }
}
