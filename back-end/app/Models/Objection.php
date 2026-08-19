<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Objection extends Model
{

    use SoftDeletes;
    protected $table = 'objections';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'filed_on' => 'date',
            'is_within_time' => 'boolean',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(PublicNotice::class, 'public_notice_id');
    }
}
