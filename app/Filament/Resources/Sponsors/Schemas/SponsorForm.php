<?php

namespace App\Filament\Resources\Sponsors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
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
                FileUpload::make('logo')
                    ->image()
                    ->directory('sponsors')
                    ->disk('public')
                    ->maxSize(2048),
                FileUpload::make('banner')
                    ->image()
                    ->directory('sponsors-banners')
                    ->disk('public')
                    ->maxSize(4096),
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
