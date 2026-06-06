<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->label('Модель')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—')
                    ->sortable(),

                TextColumn::make('subject_id')
                    ->label('ID объекта')
                    ->sortable(),

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
