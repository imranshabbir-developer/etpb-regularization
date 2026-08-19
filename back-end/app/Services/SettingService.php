<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reads statutory constants from the `settings` table.
 *
 * Settings are dated. Asking for a value "as at" a date returns the row that
 * was in force on that date, which is what lets a historic assessment be
 * recomputed under the rules that actually applied to it — the Scheme has been
 * amended by SRO in 2000, 2001, 2006 and 2024.
 */
class SettingService
{
    private const CACHE_TTL = 600;

    /**
     * The value of $key in force on $asAt (default: today).
     */
    public function get(string $key, mixed $default = null, ?string $asAt = null): mixed
    {
        $date = $asAt ?: Carbon::today()->toDateString();

        // Cached as a plain array: cache drivers restrict which classes they
        // will unserialize, and a half-restored row here would silently change
        // a statutory constant.
        $row = Cache::remember(
            "setting:{$key}:{$date}",
            self::CACHE_TTL,
            function () use ($key, $date) {
                $found = DB::table('settings')
                    ->where('key', $key)
                    ->whereNull('deleted_at')
                    ->whereDate('effective_from', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
                    })
                    ->orderByDesc('effective_from')
                    ->first();

                return $found ? (array) $found : null;
            }
        );

        if (! $row) {
            return $default;
        }

        return $this->cast((string) $row['value'], (string) $row['value_type']);
    }

    public function string(string $key, string $default = '', ?string $asAt = null): string
    {
        return (string) $this->get($key, $default, $asAt);
    }

    public function int(string $key, int $default = 0, ?string $asAt = null): int
    {
        return (int) $this->get($key, $default, $asAt);
    }

    /** Returned as a string so it can be fed straight into BCMath. */
    public function decimal(string $key, string $default = '0', ?string $asAt = null): string
    {
        return (string) $this->get($key, $default, $asAt);
    }

    public function date(string $key, ?string $default = null, ?string $asAt = null): ?Carbon
    {
        $v = $this->get($key, $default, $asAt);

        return $v ? Carbon::parse((string) $v) : null;
    }

    public function bool(string $key, bool $default = false, ?string $asAt = null): bool
    {
        return (bool) $this->get($key, $default, $asAt);
    }

    /** @return array<int, int> */
    public function milestoneYears(?string $asAt = null): array
    {
        $raw = $this->string('milestone_years', '2000,2004,2008,2012,2016,2020,2024', $asAt);

        return array_values(array_filter(array_map(
            static fn ($y) => (int) trim($y),
            explode(',', $raw)
        )));
    }

    /**
     * Every setting in a group, keyed by setting key — for the settings screen.
     *
     * @return array<string, object>
     */
    public function group(string $group, ?string $asAt = null): array
    {
        $date = $asAt ?: Carbon::today()->toDateString();

        $rows = DB::table('settings')
            ->where('group', $group)
            ->whereNull('deleted_at')
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderBy('key')
            ->orderByDesc('effective_from')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            // The first row per key wins — they arrive newest-effective first.
            $out[$r->key] ??= $r;
        }

        return $out;
    }

    /**
     * Supersede a setting from a given date, preserving the previous row.
     * A statutory change is never an UPDATE; it is a new dated row.
     */
    public function supersede(string $key, string $value, string $effectiveFrom, ?int $userId = null): void
    {
        DB::transaction(function () use ($key, $value, $effectiveFrom, $userId) {
            $current = DB::table('settings')
                ->where('key', $key)
                ->whereNull('deleted_at')
                ->whereNull('effective_to')
                ->orderByDesc('effective_from')
                ->first();

            if (! $current) {
                throw new \RuntimeException("Unknown setting [{$key}].");
            }
            if (! $current->is_editable) {
                throw new \RuntimeException(
                    "Setting [{$key}] is fixed by statute and cannot be edited: {$current->legal_reference}"
                );
            }

            DB::table('settings')->where('id', $current->id)->update([
                'effective_to' => Carbon::parse($effectiveFrom)->subDay()->toDateString(),
                'updated_by'   => $userId,
                'updated_at'   => now(),
            ]);

            DB::table('settings')->insert([
                'key'             => $key,
                'value'           => $value,
                'value_type'      => $current->value_type,
                'group'           => $current->group,
                'label'           => $current->label,
                'description'     => $current->description,
                'legal_reference' => $current->legal_reference,
                'effective_from'  => $effectiveFrom,
                'is_editable'     => $current->is_editable,
                'created_by'      => $userId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        });

        Cache::flush();
    }

    private function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'INT'     => (int) $value,
            'DECIMAL' => $value,          // string, for BCMath
            'BOOL'    => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'JSON'    => json_decode($value, true),
            default   => $value,
        };
    }
}
