<?php

namespace App\Console\Commands;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Mail\BookingPendingTooLong;
use App\Mail\ReviewPendingTooLong;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:notify-directors-about-overdue-pending')]
#[Description('Notify directors about bookings and reviews stuck in pending status for more than a day')]
class NotifyDirectorsAboutOverduePending extends Command
{
    public function handle(): void
    {
        $directors = User::role('director')->get();

        if ($directors->isEmpty()) {
            return;
        }

        $threshold = now()->subDay();

        Booking::where('status', 'pending')
            ->where('created_at', '<=', $threshold)
            ->whereNull('pending_notified_at')
            ->each(function (Booking $booking) use ($directors) {
                foreach ($directors as $director) {
                    Mail::to($director->email)->send(new BookingPendingTooLong($booking));

                    $director->notify(new UserNotification(
                        title: 'Бронирование ожидает обработки больше суток',
                        body: "Бронирование гостя {$booking->user->name} (№{$booking->room->number}) всё ещё не обработано.",
                        icon: 'heroicon-o-clock',
                        color: 'warning',
                        url: BookingResource::getUrl('edit', ['record' => $booking]),
                    ));
                }

                $booking->update(['pending_notified_at' => now()]);
            });

        Review::where('status', 'pending')
            ->where('created_at', '<=', $threshold)
            ->whereNull('pending_notified_at')
            ->each(function (Review $review) use ($directors) {
                foreach ($directors as $director) {
                    Mail::to($director->email)->send(new ReviewPendingTooLong($review));

                    $director->notify(new UserNotification(
                        title: 'Отзыв ожидает обработки больше суток',
                        body: "Отзыв гостя {$review->user->name} всё ещё не обработан.",
                        icon: 'heroicon-o-clock',
                        color: 'warning',
                        url: ReviewResource::getUrl('edit', ['record' => $review]),
                    ));
                }

                $review->update(['pending_notified_at' => now()]);
            });
    }
}
