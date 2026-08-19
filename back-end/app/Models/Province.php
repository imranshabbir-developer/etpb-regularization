<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $table = 'provinces';

    protected $guarded = ['id'];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }
}
