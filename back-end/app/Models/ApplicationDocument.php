<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationDocument extends Model
{

    use SoftDeletes;
    protected $table = 'application_documents';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'verified_at' => 'datetime',
            'is_certified_copy' => 'boolean',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
