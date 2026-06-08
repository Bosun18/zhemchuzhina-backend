<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('guest_phone')
                    ->label('Телефон гостя')
                    ->required()
                    ->maxLength(20)
                    ->live(onBlur: true)
                    ->helperText('Если номер уже есть в базе — имя и заметки подставятся автоматически.')
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $user = User::findByPhone((string) $state);

                        if ($user) {
                            $set('guest_name', $user->name);
                            $set('guest_notes', $user->admin_notes);
                        }
                    }),
                TextInput::make('guest_name')
                    ->label('Имя гостя')
                    ->required(),
                Select::make('room_id')
                    ->label('Номер комнаты')
                    ->relationship('room', 'number')
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
                    ->placeholder('Просьбы гостя: например, дополнительное одеяло или заказ трансфера')
                    ->columnSpanFull(),
                Textarea::make('guest_notes')
                    ->label('Заметки о госте')
                    ->placeholder('Видно только персоналу: например, испортил номер или, наоборот, постоянный гость со скидкой')
                    ->helperText('Сохраняются в карточке гостя и видны при его следующих бронированиях. Гостю не отправляются.')
                    ->columnSpanFull(),
            ]);
    }
}
