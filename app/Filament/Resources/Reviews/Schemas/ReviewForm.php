<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('user_name')
                    ->label('Гость')
                    ->content(fn ($record) => $record?->user->name ?? '—'),

                Placeholder::make('rating_display')
                    ->label('Оценка')
                    ->content(fn ($record) => $record ? "{$record->rating}/10" : '—'),

                Placeholder::make('text_display')
                    ->label('Текст отзыва')
                    ->content(fn ($record) => $record?->text ?? '—')
                    ->columnSpanFull(),

                Textarea::make('admin_comment')
                    ->label('Комментарий администратора')
                    ->placeholder('Причина отклонения или внутренняя заметка...')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
