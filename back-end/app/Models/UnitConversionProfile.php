<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitConversionProfile extends Model
{
    protected $table = 'unit_conversion_profiles';

    protected $guarded = ['id'];

    public function factors(): HasMany
    {
        return $this->hasMany(UnitConversionFactor::class);
    }
}
