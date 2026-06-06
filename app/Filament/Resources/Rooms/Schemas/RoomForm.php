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
                    ->required()
                    ->numeric(),
                TextInput::make('floor')
                    ->required()
                    ->numeric(),
                Select::make('room_type_id')
                    ->relationship('roomType', 'name')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
