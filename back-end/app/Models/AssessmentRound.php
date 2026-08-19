<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentRound extends Model
{

    use SoftDeletes;
    protected $table = 'assessment_rounds';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'base_date' => 'date',
            'effective_from' => 'date',
            'first_notice_date' => 'date',
            'completion_due_date' => 'date',
            'extended_to' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function rateInputs(): HasMany
    {
        return $this->hasMany(AssessmentRateInput::class);
    }

    public function comparables(): HasMany
    {
        return $this->hasMany(AssessmentComparable::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(RentSchedule::class);
    }
}
