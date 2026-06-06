<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label('Номер')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Select::make('floor')
                    ->label('Этаж')
                    ->options([1 => '1 этаж', 2 => '2 этаж'])
                    ->required(),
                Select::make('room_type_id')
                    ->label('Тип номера')
                    ->relationship('roomType', 'name')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->required(),
            ]);
    }
}
