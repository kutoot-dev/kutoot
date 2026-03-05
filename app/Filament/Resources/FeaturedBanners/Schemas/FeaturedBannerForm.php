<?php

namespace App\Filament\Resources\FeaturedBanners\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FeaturedBannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->maxLength(255),
                TextInput::make('link_url')
                    ->label('Link URL')
                    ->url()
                    ->maxLength(2048),
                TextInput::make('link_text')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                SpatieMediaLibraryFileUpload::make('images')
                    ->collection('images')
                    ->image()
                    ->validationRules(['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'])
                    ->conversion('thumb')
                    ->responsiveImages()
                    ->maxSize(config('upload.max_file_size_kb')),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
