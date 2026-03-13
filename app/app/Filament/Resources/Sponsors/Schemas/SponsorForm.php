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
                    ->conversion('thumb')
                    ->responsiveImages()
                    ->maxSize(config('upload.max_upload_size_mb') * 1024),
                SpatieMediaLibraryFileUpload::make('banner')
                    ->collection('banner')
                    ->image()
                    ->conversion('thumb')
                    ->responsiveImages()
                    ->maxSize(config('upload.max_upload_size_mb') * 1024),
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
