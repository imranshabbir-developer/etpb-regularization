<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyGeoTag extends Model
{

    use SoftDeletes;
    protected $table = 'property_geo_tags';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'polygon' => 'array',
            'captured_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
