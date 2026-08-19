<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentSchedule extends Model
{
    protected $table = 'rent_schedules';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'is_reassessment_year' => 'boolean',
            'is_milestone_year' => 'boolean',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(AssessmentRound::class, 'assessment_round_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
