<?php

namespace App\Filament\Resources\MerchantCategories\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MerchantCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection('image')
                    ->image()
                    ->conversion('thumb')
                    ->responsiveImages()
                    ->maxSize(config('upload.max_file_size_kb')),
                SpatieMediaLibraryFileUpload::make('icon')
                    ->collection('icon')
                    ->image()
                    ->conversion('thumb')
                    ->responsiveImages()
                    ->maxSize(config('upload.max_file_size_kb')),
                TextInput::make('serial')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
