<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Models\User;

/**
 * Связывает форму брони с гостем по телефону: подтягивает данные гостя в форму
 * и при сохранении находит/создаёт пользователя, синхронизируя заметки о нём.
 */
trait InteractsWithBookingGuest
{
    /**
     * Заполняет поля формы (телефон, имя, заметки) из связанного пользователя.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function hydrateGuestData(array $data): array
    {
        $user = isset($data['user_id']) ? User::find($data['user_id']) : null;

        $data['guest_phone'] = $user?->phone;
        $data['guest_name'] = $user?->name;
        $data['guest_notes'] = $user?->admin_notes;

        return $data;
    }

    /**
     * Находит или создаёт гостя по телефону, пишет заметки в его карточку и
     * подставляет user_id, убирая вспомогательные поля формы.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveGuestData(array $data): array
    {
        $guest = User::findOrCreatePhoneGuest(
            name: $data['guest_name'],
            phone: $data['guest_phone'],
        );

        $guest->update(['admin_notes' => $data['guest_notes'] ?? null]);

        $data['user_id'] = $guest->id;

        unset($data['guest_phone'], $data['guest_name'], $data['guest_notes']);

        return $data;
    }
}
