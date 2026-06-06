<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $fillable = ['name', 'date_from', 'date_to'];

    protected $casts = ['date_from' => 'date', 'date_to' => 'date'];

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
