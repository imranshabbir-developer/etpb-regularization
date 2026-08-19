<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = 'document_types';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_certified_copy_required' => 'boolean',
            'is_mandatory' => 'boolean',
            'is_waivable' => 'boolean',
            'proves_possession_date' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
