<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NomineeHeir extends Model
{
    protected $table = 'nominee_heirs';

    protected $guarded = ['id'];

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(Nominee::class);
    }
}
