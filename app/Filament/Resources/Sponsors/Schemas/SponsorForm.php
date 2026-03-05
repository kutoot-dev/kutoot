<?php

namespace App\Filament\Resources\Sponsors\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SponsorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('type')
                    ->required()
                    ->maxLength(255)
                    ->default('Sponsor'),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->collection('logo')
                    ->image()
                    ->validationRules(['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'])
                    ->maxSize(config('upload.max_file_size_kb', 2048))
                    ->conversion('thumb')
                    ->responsiveImages(),
                SpatieMediaLibraryFileUpload::make('banner')
                    ->collection('banner')
                    ->image()
                    ->validationRules(['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'])
                    ->maxSize(config('upload.max_file_size_kb', 4096))
                    ->conversion('thumb')
                    ->responsiveImages(),
                TextInput::make('link')
                    ->url()
                    ->maxLength(2048),
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
