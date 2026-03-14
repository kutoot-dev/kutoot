<?php

namespace App\Filament\Resources\Partners\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->label('Logo')
                    ->collection('logo')
                    ->image()
                    ->conversion('thumb')
                    ->responsiveImages()
                    ->maxSize((int) config('upload.max_upload_size_mb', 100) * 1024)
                    ->required(),
                TextInput::make('link')
                    ->label('Website URL')
                    ->url()
                    ->maxLength(2048),
                TextInput::make('serial')
                    ->label('Display Order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
