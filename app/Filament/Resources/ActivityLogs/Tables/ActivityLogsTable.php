<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Resources\News\NewsResource;
use App\Filament\Resources\Prices\PriceResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Rooms\RoomResource;
use App\Filament\Resources\RoomTypes\RoomTypeResource;
use App\Filament\Resources\Seasons\SeasonResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    private static array $modelLabels = [
        'User' => 'Пользователь (User)',
        'Booking' => 'Бронирование (Booking)',
        'Room' => 'Номер (Room)',
        'RoomType' => 'Тип номера (RoomType)',
        'Season' => 'Сезон (Season)',
        'Price' => 'Цена (Price)',
        'Review' => 'Отзыв (Review)',
        'News' => 'Новость (News)',
        'Service' => 'Услуга (Service)',
        'Gallery' => 'Фото (Gallery)',
    ];

    private static array $resourceMap = [
        'User' => UserResource::class,
        'Booking' => BookingResource::class,
        'Room' => RoomResource::class,
        'RoomType' => RoomTypeResource::class,
        'Season' => SeasonResource::class,
        'Price' => PriceResource::class,
        'Review' => ReviewResource::class,
        'News' => NewsResource::class,
        'Service' => ServiceResource::class,
        'Gallery' => GalleryResource::class,
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('subject', 'causer'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('log_name')
                    ->label('Журнал')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'admin' => 'Администратор',
                        'user' => 'Пользователь',
                        'system' => 'Система',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'admin' => 'warning',
                        'user' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Действие')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'created' => 'Создано',
                        'updated' => 'Изменено',
                        'deleted' => 'Удалено',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('subject_type')
                    ->label('Раздел')
                    ->formatStateUsing(fn (?string $state) => $state
                        ? (self::$modelLabels[class_basename($state)] ?? class_basename($state))
                        : '—'
                    )
                    ->sortable(),

                TextColumn::make('subject_id')
                    ->label('Объект')
                    ->formatStateUsing(function ($state, $record) {
                        $subject = $record->subject;
                        $model = $record->subject_type ? class_basename($record->subject_type) : null;

                        if (! $subject) {
                            return match ($model) {
                                'Booking' => "Бронь #{$state}",
                                default => "#{$state}",
                            };
                        }

                        return match ($model) {
                            'User' => $subject->name ?? "Пользователь #{$state}",
                            'Booking' => "Бронь #{$state}",
                            'Room' => 'Номер '.($subject->number ?? "#{$state}"),
                            'RoomType' => $subject->name ?? "Тип #{$state}",
                            'Season' => $subject->name ?? "Сезон #{$state}",
                            'Price' => "Цена #{$state}",
                            'Review' => "Отзыв #{$state}",
                            'News' => $subject->title ?? "Новость #{$state}",
                            'Service' => $subject->title ?? "Услуга #{$state}",
                            'Gallery' => "Фото #{$state}",
                            default => "#{$state}",
                        };
                    })
                    ->url(function ($record) {
                        if (! $record->subject_id || ! $record->subject_type) {
                            return null;
                        }

                        $model = class_basename($record->subject_type);
                        $resource = self::$resourceMap[$model] ?? null;

                        if (! $resource) {
                            return null;
                        }

                        try {
                            return $resource::getUrl('edit', ['record' => $record->subject_id]);
                        } catch (\Exception) {
                            return null;
                        }
                    })
                    ->color('primary'),

                TextColumn::make('causer.name')
                    ->label('Кто изменил')
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Журнал')
                    ->options([
                        'admin' => 'Администратор',
                        'user' => 'Пользователь',
                        'system' => 'Система',
                    ]),

                SelectFilter::make('description')
                    ->label('Действие')
                    ->options([
                        'created' => 'Создано',
                        'updated' => 'Изменено',
                        'deleted' => 'Удалено',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }
}
