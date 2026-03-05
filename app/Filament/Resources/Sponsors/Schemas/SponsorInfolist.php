<?php

namespace App\Filament\Resources\Sponsors\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SponsorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('type'),
                SpatieMediaLibraryImageEntry::make('logo')
                    ->collection('logo')
                    ->conversion('preview'),
                SpatieMediaLibraryImageEntry::make('banner')
                    ->collection('banner')
                    ->conversion('preview'),
                TextEntry::make('link')
                    ->placeholder('-'),
                TextEntry::make('serial')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
