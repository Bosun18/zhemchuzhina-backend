<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()->hasAnyRole(['director', 'developer'])),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['password']) && password_get_info($data['password'])['algo'] !== null) {
            unset($data['password']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        $url = $this->getResource()::getUrl('index');

        return parse_url($url, PHP_URL_PATH);
    }
}
