<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_rural_agricultural'   => 'boolean',
            'floor_wise_covered_area' => 'array',
        ];
    }

    public function province(): BelongsTo { return $this->belongsTo(Province::class); }
    public function district(): BelongsTo { return $this->belongsTo(District::class); }
    public function tehsil(): BelongsTo   { return $this->belongsTo(Tehsil::class); }
    public function mouza(): BelongsTo    { return $this->belongsTo(Mouza::class); }

    public function areas(): HasMany      { return $this->hasMany(PropertyArea::class); }
    public function geoTags(): HasMany    { return $this->hasMany(PropertyGeoTag::class); }
    public function applications(): HasMany { return $this->hasMany(Application::class); }

    /** The area record currently in force. */
    public function currentArea(): HasOne
    {
        return $this->hasOne(PropertyArea::class)->where('is_current', true);
    }

    public function primaryGeoTag(): HasOne
    {
        return $this->hasOne(PropertyGeoTag::class)->where('is_primary', true);
    }

    /** "Property 14-B, Sub-unit 2" or just "Property 14-B". */
    public function identity(bool $localized = false): string
    {
        if ($localized) {
            $no = $this->property_no ?: __('Unnumbered');

            return $this->sub_unit_no
                ? __('Property :no, Sub-unit :sub', ['no' => $no, 'sub' => $this->sub_unit_no])
                : __('Property :no', ['no' => $no]);
        }

        $no = $this->property_no ?: 'Unnumbered';

        return $this->sub_unit_no
            ? "Property {$no}, Sub-unit {$this->sub_unit_no}"
            : "Property {$no}";
    }

    /** Mouza, City, Tehsil, District, Province — the chain the spec asks for. */
    public function locationChain(): string
    {
        return collect([
            $this->mouza?->name ? __('Mouza :name', ['name' => $this->mouza->name]) : null,
            $this->city,
            $this->tehsil?->name ? __('Tehsil :name', ['name' => __($this->tehsil->name)]) : null,
            $this->district?->name ? __('District :name', ['name' => __($this->district->name)]) : null,
            $this->province?->name ? __($this->province->name) : null,
        ])->filter()->implode(', ');
    }

    public function usageLabel(): string
    {
        return match ($this->usage_type) {
            'OPEN_PLOT'                  => __('Open Plot'),
            'RESIDENTIAL'                => __('Residential'),
            'COMMERCIAL'                 => __('Commercial'),
            'RESIDENTIAL_CUM_COMMERCIAL' => __('Residential-cum-commercial'),
            default                      => __('Others'),
        };
    }

    public function typeLabel(): string
    {
        return match ($this->property_type) {
            'OPEN_PLOT'                  => __('Open Plot'),
            'RESIDENTIAL'                => __('Residential'),
            'COMMERCIAL'                 => __('Commercial'),
            'RESIDENTIAL_CUM_COMMERCIAL' => __('Residential-cum-commercial'),
            default                      => __('Others'),
        };
    }
}
