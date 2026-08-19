<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalmentSchedule extends Model
{
    protected $table = 'instalment_schedules';

    protected $guarded = ['id'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstalmentPlan::class, 'instalment_plan_id');
    }
}
