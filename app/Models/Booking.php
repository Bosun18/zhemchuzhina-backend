<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = ['user_id', 'room_id', 'check_in', 'check_out', 'guests_count', 'status', 'comment', 'admin_comment'];

    protected $casts = ['check_in' => 'date', 'check_out' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
