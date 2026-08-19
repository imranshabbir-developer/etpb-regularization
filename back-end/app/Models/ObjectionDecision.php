<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjectionDecision extends Model
{

    use SoftDeletes;
    protected $table = 'objection_decisions';

    protected $guarded = ['id'];

    public function objection(): BelongsTo
    {
        return $this->belongsTo(Objection::class);
    }
}
