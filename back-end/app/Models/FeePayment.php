<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeePayment extends Model
{

    use SoftDeletes;
    protected $table = 'fee_payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'instrument_date' => 'date',
            'submission_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
