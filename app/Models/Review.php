<?php

namespace App\Models;

use App\Observers\ReviewObserver;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy(ReviewObserver::class)]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['user_id', 'booking_id', 'rating', 'text', 'status', 'admin_comment', 'pending_notified_at'];

    protected $casts = ['pending_notified_at' => 'datetime'];

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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
