<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

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
                    ->autocomplete('off')
                    ->required(),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('city')
                    ->label('Город')
                    ->required(),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->autocomplete('new-password')
                    ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                    ->saved(fn (?string $state): bool => filled($state) && password_get_info($state)['algo'] === null)
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
                    ->preload()
                    ->live(),
                Textarea::make('admin_notes')
                    ->label('Заметки администратора')
                    ->placeholder('Например: сильно храпит, поселить в крайний номер')
                    ->columnSpanFull(),
                Section::make('График работы')
                    ->description('Дни и часы, когда этот администратор получает уведомления')
                    ->visible(function (Get $get): bool {
                        $roleIds = $get('roles') ?? [];

                        return Role::whereIn('id', $roleIds)
                            ->whereIn('name', ['admin', 'director'])
                            ->exists();
                    })
                    ->schema([
                        CheckboxList::make('work_schedule.days')
                            ->label('Рабочие дни')
                            ->options([
                                '1' => 'Понедельник',
                                '2' => 'Вторник',
                                '3' => 'Среда',
                                '4' => 'Четверг',
                                '5' => 'Пятница',
                                '6' => 'Суббота',
                                '7' => 'Воскресенье',
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                        TimePicker::make('work_schedule.time_from')
                            ->label('Начало работы')
                            ->seconds(false),
                        TimePicker::make('work_schedule.time_to')
                            ->label('Конец работы')
                            ->seconds(false),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
