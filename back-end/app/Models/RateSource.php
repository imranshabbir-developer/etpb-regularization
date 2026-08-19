<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateSource extends Model
{
    protected $table = 'rate_sources';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_operative' => 'boolean',
            'requires_reference_no' => 'boolean',
            'requires_reasons' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
