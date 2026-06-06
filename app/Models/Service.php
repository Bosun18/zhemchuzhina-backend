<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['title', 'description', 'price', 'image', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'price' => 'decimal:2'];
}
