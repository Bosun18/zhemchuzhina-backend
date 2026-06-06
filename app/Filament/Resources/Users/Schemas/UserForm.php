<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Имя')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                TextInput::make('city')
                    ->label('Город'),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->afterStateHydrated(fn ($component) => $component->state(null))
                    ->dehydrateStateUsing(fn (string $state) => bcrypt($state))
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('roles')
                    ->label('Роль')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => match ($record->name) {
                        'guest' => 'Гость',
                        'admin' => 'Администратор',
                        'director' => 'Директор',
                        'developer' => 'Разработчик',
                        default => $record->name,
                    })
                    ->multiple()
                    ->preload(),
                Textarea::make('admin_notes')
                    ->label('Заметки администратора')
                    ->placeholder('Например: сильно храпит, поселить в крайний номер')
                    ->columnSpanFull(),
            ]);
    }
}
