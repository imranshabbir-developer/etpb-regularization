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
            'is_rural_agricultural' => 'boolean',
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
    public function identity(): string
    {
        return $this->sub_unit_no
            ? "Property {$this->property_no}, Sub-unit {$this->sub_unit_no}"
            : "Property {$this->property_no}";
    }

    /** Mouza, City, Tehsil, District, Province — the chain the spec asks for. */
    public function locationChain(): string
    {
        return collect([
            $this->mouza?->name ? 'Mouza ' . $this->mouza->name : null,
            $this->city,
            $this->tehsil?->name ? 'Tehsil ' . $this->tehsil->name : null,
            $this->district?->name ? 'District ' . $this->district->name : null,
            $this->province?->name,
        ])->filter()->implode(', ');
    }

    public function usageLabel(): string
    {
        return match ($this->usage_type) {
            'RESIDENTIAL'                => 'Residential',
            'COMMERCIAL'                 => 'Commercial',
            'RESIDENTIAL_CUM_COMMERCIAL' => 'Residential-cum-Commercial',
            default                      => 'Other',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->property_type) {
            'HOUSE'     => 'House',
            'SHOP'      => 'Shop',
            'BUILDING'  => 'Building',
            'PLOT'      => 'Plot',
            'AGRI_LAND' => 'Agricultural Land',
            default     => 'Other',
        };
    }
}
