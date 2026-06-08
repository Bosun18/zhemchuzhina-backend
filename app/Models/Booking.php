<?php

namespace App\Models;

use App\Observers\BookingObserver;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy(BookingObserver::class)]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['user_id', 'room_id', 'check_in', 'check_out', 'guests_count', 'status', 'comment', 'admin_comment', 'pending_notified_at'];

    protected $casts = ['check_in' => 'date', 'check_out' => 'date', 'pending_notified_at' => 'datetime'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('admin');
    }

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
