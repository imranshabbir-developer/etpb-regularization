<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tehsil extends Model
{
    protected $table = 'tehsils';

    protected $guarded = ['id'];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function mouzas(): HasMany
    {
        return $this->hasMany(Mouza::class);
    }
}
