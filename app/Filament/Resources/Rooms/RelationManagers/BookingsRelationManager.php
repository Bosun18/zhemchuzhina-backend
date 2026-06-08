<?php

namespace App\Filament\Resources\Rooms\RelationManagers;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Брони номера';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->defaultSort('check_in', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Гость')
                    ->searchable(),
                TextColumn::make('check_in')
                    ->label('Заезд')
                    ->date()
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('Выезд')
                    ->date()
                    ->sortable(),
                TextColumn::make('guests_count')
                    ->label('Гостей')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждено',
                        'cancelled' => 'Отменено',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждено',
                        'cancelled' => 'Отменено',
                    ]),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'confirmed'])),
                Action::make('cancel')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record): bool => in_array($record->status, ['pending', 'confirmed'], true))
                    ->modalHeading('Отклонить бронирование')
                    ->modalSubmitActionLabel('Отклонить')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Причина отказа')
                            ->helperText('Будет отправлена гостю в письме об отмене.')
                            ->maxLength(1000),
                    ])
                    ->action(fn (array $data, $record) => $record->update([
                        'status' => 'cancelled',
                        'admin_comment' => $data['reason'] ?? null,
                    ])),
            ]);
    }
}
