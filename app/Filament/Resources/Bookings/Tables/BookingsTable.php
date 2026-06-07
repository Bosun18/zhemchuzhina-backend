<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

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
                    ->action(function ($record) {
                        $record->update(['status' => 'confirmed']);
                        Mail::to($record->user->email)
                            ->send(new BookingConfirmed($record->load(['user', 'room.roomType'])));
                    }),
                Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'cancelled')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'cancelled']);
                        Mail::to($record->user->email)
                            ->send(new BookingCancelled($record->load(['user', 'room.roomType'])));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
