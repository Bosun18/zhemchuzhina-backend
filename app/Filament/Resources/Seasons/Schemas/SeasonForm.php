<?php

namespace App\Filament\Resources\Seasons\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SeasonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required(),
                DatePicker::make('date_from')
                    ->label('Начало сезона')
                    ->required(),
                DatePicker::make('date_to')
                    ->label('Конец сезона')
                    ->required(),
            ]);
    }
}
