<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PossessionDetail extends Model
{

    use SoftDeletes;
    protected $table = 'possession_details';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_of_possession' => 'date',
            'date_of_judicial_verdict' => 'date',
            'arrears_from' => 'date',
            'cutoff_applied' => 'date',
            'is_eligible' => 'boolean',
            'is_pre_independence_plot' => 'boolean',
            'is_colony_cluster' => 'boolean',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
