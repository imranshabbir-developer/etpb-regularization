<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $table = 'districts';

    protected $guarded = ['id'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function unitProfile(): BelongsTo
    {
        return $this->belongsTo(UnitConversionProfile::class, 'unit_profile_id');
    }

    public function tehsils(): HasMany
    {
        return $this->hasMany(Tehsil::class);
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }
}
