<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversionFactor extends Model
{
    protected $table = 'unit_conversion_factors';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_compound_component' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UnitConversionProfile::class, 'unit_profile_id');
    }
}
