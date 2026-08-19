<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentRateInput extends Model
{

    use SoftDeletes;
    protected $table = 'assessment_rate_inputs';

    protected $guarded = ['id'];

    public function round(): BelongsTo
    {
        return $this->belongsTo(AssessmentRound::class, 'assessment_round_id');
    }

    public function rateSource(): BelongsTo
    {
        return $this->belongsTo(RateSource::class, 'rate_source_id');
    }
}
