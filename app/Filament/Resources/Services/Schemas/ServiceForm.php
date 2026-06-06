<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Название')
                    ->required(),
                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label('Цена')
                    ->numeric()
                    ->prefix('₽'),
                FileUpload::make('image')
                    ->label('Изображение')
                    ->image(),
                Toggle::make('is_active')
                    ->label('Активна')
                    ->required(),
            ]);
    }
}
