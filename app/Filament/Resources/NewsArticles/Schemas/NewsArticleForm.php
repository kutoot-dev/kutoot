<?php

namespace App\Filament\Resources\NewsArticles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NewsArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(4)
                    ->maxLength(5000),
                TextInput::make('link_url')
                    ->label('Link URL')
                    ->url()
                    ->maxLength(2048),
                DateTimePicker::make('published_at')
                    ->label('Published At'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection('image')
                    ->image()
                    ->conversion('thumb')
                    ->responsiveImages()
                    ->maxSize(config('upload.max_upload_size_mb') * 1024),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
