<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Litigation extends Model
{

    use SoftDeletes;
    protected $table = 'litigations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_pending' => 'boolean',
            'has_restraining_order' => 'boolean',
            'is_direction_case' => 'boolean',
            'filed_on' => 'date',
            'restraining_order_date' => 'date',
            'next_hearing_date' => 'date',
            'last_order_date' => 'date',
            'disposal_date' => 'date',
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
}
