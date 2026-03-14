<?php

namespace App\Filament\Resources\MarketingBanners\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MarketingBannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->maxLength(255),
                TextInput::make('subtitle')
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
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection('image')
                    ->image()
                    ->conversion('thumb')
                    ->responsiveImages()
                    ->maxSize((int) config('upload.max_upload_size_mb', 100) * 1024),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
