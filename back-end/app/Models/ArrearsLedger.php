<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArrearsLedger extends Model
{
    protected $table = 'arrears_ledger';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
