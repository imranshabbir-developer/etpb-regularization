<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyArea extends Model
{

    use SoftDeletes;
    protected $table = 'property_areas';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'conversion_trace' => 'array',
            'is_current' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
