<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicNotice extends Model
{

    use SoftDeletes;
    protected $table = 'public_notices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'served_on' => 'date',
            'published_on' => 'date',
            'objection_deadline' => 'date',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(AssessmentRound::class, 'assessment_round_id');
    }

    public function objections(): HasMany
    {
        return $this->hasMany(Objection::class);
    }
}
