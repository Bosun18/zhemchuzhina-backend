<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Filament\Resources\Bookings\BookingResource;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Models\User;
use App\Notifications\UserNotification;
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
                        $record->load(['user', 'room.roomType']);

                        Mail::to($record->user->email)
                            ->send(new BookingConfirmed($record));

                        $record->user->notify(new UserNotification(
                            title: 'Бронирование подтверждено',
                            body: "Ваше бронирование №{$record->room->number} на {$record->check_in->format('d.m.Y')} — {$record->check_out->format('d.m.Y')} подтверждено.",
                            icon: 'heroicon-o-check-circle',
                            color: 'success',
                        ));
                    }),
                Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'cancelled')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'cancelled']);

                        $record->load(['user', 'room.roomType']);

                        Mail::to($record->user->email)
                            ->send(new BookingCancelled($record));

                        $record->user->notify(new UserNotification(
                            title: 'Бронирование отменено',
                            body: "Ваше бронирование №{$record->room->number} на {$record->check_in->format('d.m.Y')} — {$record->check_out->format('d.m.Y')} отменено.",
                            icon: 'heroicon-o-x-circle',
                            color: 'danger',
                        ));

                        $directors = User::role('director')->get();
                        foreach ($directors as $director) {
                            Mail::to($director->email)
                                ->send(new BookingCancelled($record, isDirector: true));

                            $director->notify(new UserNotification(
                                title: 'Бронирование отменено',
                                body: "Бронирование гостя {$record->user->name} (№{$record->room->number}) было отменено.",
                                icon: 'heroicon-o-x-circle',
                                color: 'danger',
                                url: BookingResource::getUrl('edit', ['record' => $record]),
                            ));
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
