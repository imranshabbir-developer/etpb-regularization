<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Approval extends Model
{

    use SoftDeletes;
    protected $table = 'approvals';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
            'due_by' => 'date',
            'is_within_sla' => 'boolean',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
