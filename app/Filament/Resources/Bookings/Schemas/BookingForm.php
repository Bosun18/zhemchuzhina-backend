<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Гость')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('room_id')
                    ->label('Номер')
                    ->relationship('room', 'id')
                    ->required(),
                DatePicker::make('check_in')
                    ->label('Дата заезда')
                    ->required(),
                DatePicker::make('check_out')
                    ->label('Дата выезда')
                    ->required(),
                TextInput::make('guests_count')
                    ->label('Кол-во гостей')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Select::make('status')
                    ->label('Статус')
                    ->options(['pending' => 'Ожидает', 'confirmed' => 'Подтверждено', 'cancelled' => 'Отменено'])
                    ->default('pending')
                    ->required(),
                Textarea::make('comment')
                    ->label('Комментарий')
                    ->columnSpanFull(),
                Textarea::make('admin_comment')
                    ->label('Комментарий администратора')
                    ->columnSpanFull(),
            ]);
    }
}
