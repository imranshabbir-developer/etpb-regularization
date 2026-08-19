<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nominee extends Model
{

    use SoftDeletes;
    protected $table = 'nominees';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'form_received_on' => 'date',
            'verified_at' => 'datetime',
            'is_verified' => 'boolean',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function heirs(): HasMany
    {
        return $this->hasMany(NomineeHeir::class);
    }
}
