<?php

namespace App\Filament\Resources\Galleries\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GalleryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->label('Изображение')
                    ->image()
                    ->required(),
                TextInput::make('caption')
                    ->label('Подпись'),
                TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
