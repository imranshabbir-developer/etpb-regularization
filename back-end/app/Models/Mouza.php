<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mouza extends Model
{
    protected $table = 'mouzas';

    protected $guarded = ['id'];

    public function tehsil(): BelongsTo
    {
        return $this->belongsTo(Tehsil::class);
    }
}
