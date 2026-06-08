<?php

namespace App\Observers;

use App\Filament\Resources\Reviews\ReviewResource;
use App\Mail\ReviewApproved;
use App\Mail\ReviewRejected;
use App\Models\Review;
use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class ReviewObserver
{
    /**
     * Рассылает уведомления гостю и директорам при смене статуса отзыва.
     * Срабатывает на любом пути модерации (действия Filament и т.п.).
     */
    public function updated(Review $review): void
    {
        if (! $review->wasChanged('status')) {
            return;
        }

        match ($review->status) {
            'approved' => $this->notifyApproved($review),
            'rejected' => $this->notifyRejected($review),
            default => null,
        };
    }

    private function notifyApproved(Review $review): void
    {
        $review->loadMissing('user');

        Mail::to($review->user->email)->send(new ReviewApproved($review));

        $review->user->notify(new UserNotification(
            title: 'Отзыв опубликован',
            body: 'Ваш отзыв прошёл модерацию и опубликован на сайте.',
            icon: 'heroicon-o-check-circle',
            color: 'success',
        ));
    }

    private function notifyRejected(Review $review): void
    {
        $review->loadMissing('user');

        Mail::to($review->user->email)->send(new ReviewRejected($review));

        $review->user->notify(new UserNotification(
            title: 'Отзыв отклонён',
            body: 'Ваш отзыв не прошёл модерацию. Подробности — в письме на почту.',
            icon: 'heroicon-o-x-circle',
            color: 'danger',
        ));

        $directors = User::role('director')->get();

        foreach ($directors as $director) {
            Mail::to($director->email)->send(new ReviewRejected($review, isDirector: true));
        }

        Notification::send($directors, new UserNotification(
            title: 'Отзыв отклонён',
            body: "Отзыв гостя {$review->user->name} был отклонён.",
            icon: 'heroicon-o-x-circle',
            color: 'danger',
            url: ReviewResource::getUrl('edit', ['record' => $review]),
        ));
    }
}
