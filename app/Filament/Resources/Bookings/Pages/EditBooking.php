<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    use InteractsWithBookingGuest;

    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()->hasAnyRole(['director', 'developer'])),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->hydrateGuestData($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->resolveGuestData($data);
    }
}
