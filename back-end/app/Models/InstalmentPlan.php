<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstalmentPlan extends Model
{

    use SoftDeletes;
    protected $table = 'instalment_plans';

    protected $guarded = ['id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(InstalmentSchedule::class);
    }
}
