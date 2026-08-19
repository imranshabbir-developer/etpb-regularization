<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentReceipt extends Model
{

    use SoftDeletes;
    protected $table = 'payment_receipts';

    protected $guarded = ['id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
