<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_history';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
