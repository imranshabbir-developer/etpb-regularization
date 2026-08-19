<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentComparable extends Model
{

    use SoftDeletes;
    protected $table = 'assessment_comparables';

    protected $guarded = ['id'];

    public function round(): BelongsTo
    {
        return $this->belongsTo(AssessmentRound::class, 'assessment_round_id');
    }
}
