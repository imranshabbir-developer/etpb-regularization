<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hearing extends Model
{

    use SoftDeletes;
    protected $table = 'hearings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'parties_summoned' => 'array',
            'attendance' => 'array',
            'scheduled_for' => 'datetime',
            'adjourned_to' => 'date',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
