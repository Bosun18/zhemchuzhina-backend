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
                    ->label('Тип номера')
                    ->relationship('roomType', 'name')
                    ->required(),
                Select::make('season_id')
                    ->label('Сезон')
                    ->relationship('season', 'name')
                    ->required(),
                TextInput::make('price_per_night')
                    ->label('Цена за ночь')
                    ->required()
                    ->numeric(),
            ]);
    }
}
