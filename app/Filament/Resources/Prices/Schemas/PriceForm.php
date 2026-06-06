<?php

namespace App\Filament\Resources\Prices\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_type_id')
                    ->relationship('roomType', 'name')
                    ->required(),
                Select::make('season_id')
                    ->relationship('season', 'name')
                    ->required(),
                TextInput::make('price_per_night')
                    ->required()
                    ->numeric(),
            ]);
    }
}
