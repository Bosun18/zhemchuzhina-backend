<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Гость')
                    ->searchable(),
                TextColumn::make('room.id')
                    ->label('Номер')
                    ->searchable(),
                TextColumn::make('check_in')
                    ->label('Дата заезда')
                    ->date()
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('Дата выезда')
                    ->date()
                    ->sortable(),
                TextColumn::make('guests_count')
                    ->label('Кол-во гостей')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждено',
                        'cancelled' => 'Отменено',
                        default => $state,
                    }),
                TextColumn::make('guest_notes')
                    ->label('Заметки о госте')
                    ->state(fn ($record) => $record->source === 'website' ? $record->user?->admin_notes : null)
                    ->color('danger')
                    ->wrap()
                    ->placeholder('—')
                    ->tooltip('Заметки персонала о госте — показаны для броней, оформленных гостем на сайте'),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('confirm')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'confirmed'])),
                Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'cancelled')
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()->hasAnyRole(['director', 'developer'])),
                ]),
            ]);
    }
}
