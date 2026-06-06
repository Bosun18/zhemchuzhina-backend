<?php

namespace App\Filament\Resources\News\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Заголовок')
                    ->required(),
                Textarea::make('content')
                    ->label('Содержание')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label('Изображение')
                    ->image(),
                Toggle::make('is_published')
                    ->label('Опубликовано')
                    ->required(),
                DateTimePicker::make('published_at')
                    ->label('Дата публикации'),
            ]);
    }
}
