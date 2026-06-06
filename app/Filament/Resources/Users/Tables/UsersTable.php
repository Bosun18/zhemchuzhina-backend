<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Город')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'guest' => 'Гость',
                        'admin' => 'Администратор',
                        'director' => 'Директор',
                        'developer' => 'Разработчик',
                        default => $state,
                    }),
                IconColumn::make('admin_notes')
                    ->label('Заметка')
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left')
                    ->falseIcon('heroicon-o-minus')
                    ->tooltip(fn ($record) => $record->admin_notes),
                TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
