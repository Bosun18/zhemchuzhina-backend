<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Filament\Resources\Reviews\ReviewResource;
use App\Mail\ReviewApproved;
use App\Mail\ReviewRejected;
use App\Models\User;
use App\Notifications\UserNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Гость')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rating')
                    ->label('Оценка')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 8 => 'success',
                        $state >= 5 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('text')
                    ->label('Отзыв')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'Ожидает',
                        'approved' => 'Одобрен',
                        'rejected' => 'Отклонён',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'approved')
                    ->modalHeading('Одобрить отзыв')
                    ->modalDescription('Отзыв будет опубликован на сайте.')
                    ->form([
                        Textarea::make('admin_comment')
                            ->label('Комментарий администратора')
                            ->placeholder('Здесь вы можете поблагодарить гостя или ответить на критику. Можете оставить пустым')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'admin_comment' => $data['admin_comment'],
                        ]);

                        $record->load('user');

                        Mail::to($record->user->email)
                            ->send(new ReviewApproved($record));

                        $record->user->notify(new UserNotification(
                            title: 'Отзыв опубликован',
                            body: 'Ваш отзыв прошёл модерацию и опубликован на сайте.',
                            icon: 'heroicon-o-check-circle',
                            color: 'success',
                        ));
                    }),

                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'rejected')
                    ->form([
                        Textarea::make('admin_comment')
                            ->label('Причина отклонения')
                            ->placeholder('Укажите причину, она будет отправлена гостю...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'admin_comment' => $data['admin_comment'],
                        ]);

                        $record->load('user');

                        Mail::to($record->user->email)
                            ->send(new ReviewRejected($record));

                        $record->user->notify(new UserNotification(
                            title: 'Отзыв отклонён',
                            body: 'Ваш отзыв не прошёл модерацию. Подробности — в письме на почту.',
                            icon: 'heroicon-o-x-circle',
                            color: 'danger',
                        ));

                        $directors = User::role('director')->get();
                        foreach ($directors as $director) {
                            Mail::to($director->email)
                                ->send(new ReviewRejected($record, isDirector: true));

                            $director->notify(new UserNotification(
                                title: 'Отзыв отклонён',
                                body: "Отзыв гостя {$record->user->name} был отклонён.",
                                icon: 'heroicon-o-x-circle',
                                color: 'danger',
                                url: ReviewResource::getUrl('edit', ['record' => $record]),
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
