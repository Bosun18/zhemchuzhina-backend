<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Гость')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('booking_id')
                    ->label('Бронирование')
                    ->relationship('booking', 'id')
                    ->required(),
                TextInput::make('rating')
                    ->label('Оценка')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                Textarea::make('text')
                    ->label('Текст отзыва')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Статус')
                    ->options(['pending' => 'Ожидает', 'approved' => 'Одобрен', 'rejected' => 'Отклонён'])
                    ->default('pending')
                    ->required(),
            ]);
    }
}
