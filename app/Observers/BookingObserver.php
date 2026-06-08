<?php

namespace App\Observers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class BookingObserver
{
    /**
     * Рассылает уведомления гостю и директорам при смене статуса брони.
     * Срабатывает на любом пути: API, действия и форма Filament.
     */
    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        match ($booking->status) {
            'confirmed' => $this->notifyConfirmed($booking),
            'cancelled' => $this->notifyCancelled($booking),
            default => null,
        };
    }

    private function notifyConfirmed(Booking $booking): void
    {
        $booking->loadMissing('user', 'room.roomType');

        Mail::to($booking->user->email)->send(new BookingConfirmed($booking));

        $booking->user->notify(new UserNotification(
            title: 'Бронирование подтверждено',
            body: "Ваше бронирование №{$booking->room->number} на {$booking->check_in->format('d.m.Y')} — {$booking->check_out->format('d.m.Y')} подтверждено.",
            icon: 'heroicon-o-check-circle',
            color: 'success',
        ));
    }

    private function notifyCancelled(Booking $booking): void
    {
        $booking->loadMissing('user', 'room.roomType');

        // Гостю в любом случае уходит подтверждение отмены.
        $this->notifyGuestCancelled($booking);

        // Гость сам отменил свою бронь (сценарий 6) → дополнительно админам и директорам.
        if (auth()->id() === $booking->user_id) {
            $this->notifyStaffAboutSelfCancellation($booking);

            return;
        }

        // Сценарий 4: бронь отклонена сотрудником → директору.
        $directors = User::role('director')->get();

        foreach ($directors as $director) {
            Mail::to($director->email)->send(new BookingCancelled($booking, isDirector: true));
        }

        Notification::send($directors, new UserNotification(
            title: 'Бронирование отменено',
            body: "Бронирование гостя {$booking->user->name} (№{$booking->room->number}) было отменено.",
            icon: 'heroicon-o-x-circle',
            color: 'danger',
            url: BookingResource::getUrl('edit', ['record' => $booking]),
        ));
    }

    private function notifyGuestCancelled(Booking $booking): void
    {
        Mail::to($booking->user->email)->send(new BookingCancelled($booking));

        $booking->user->notify(new UserNotification(
            title: 'Бронирование отменено',
            body: "Ваше бронирование №{$booking->room->number} на {$booking->check_in->format('d.m.Y')} — {$booking->check_out->format('d.m.Y')} отменено.",
            icon: 'heroicon-o-x-circle',
            color: 'danger',
        ));
    }

    /**
     * Гость отменил бронь самостоятельно → уведомляем администраторов и директоров
     * (письмо и пуш) о том, что номер освободился.
     */
    private function notifyStaffAboutSelfCancellation(Booking $booking): void
    {
        $staff = User::role(['admin', 'director'])->get();

        foreach ($staff as $member) {
            Mail::to($member->email)->send(new BookingCancelled($booking, isDirector: true));
        }

        Notification::send($staff, new UserNotification(
            title: 'Гость отменил бронирование',
            body: "Гость {$booking->user->name} отменил бронирование №{$booking->room->number} на {$booking->check_in->format('d.m.Y')} — {$booking->check_out->format('d.m.Y')}.",
            icon: 'heroicon-o-x-circle',
            color: 'danger',
            url: BookingResource::getUrl('edit', ['record' => $booking]),
        ));
    }
}
