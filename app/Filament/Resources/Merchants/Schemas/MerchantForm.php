<?php

namespace App\Filament\Resources\Merchants\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('razorpay_account_id')
                    ->label('Razorpay Account ID'),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->collection('logo')
                    ->image()
                    ->conversion('thumb')
                    ->responsiveImages()
                    ->maxSize(config('upload.max_file_size_kb')),
                Toggle::make('is_active')
                    ->required(),

                Section::make('Media Gallery')
                    ->description('Upload images and videos for this merchant.')
                    ->collapsible()
                    ->components([
                        SpatieMediaLibraryFileUpload::make('media')
                            ->collection('media')
                            ->multiple()
                            ->reorderable()
                            ->acceptedFileTypes([
                                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                                'video/mp4', 'video/webm', 'video/quicktime',
                            ])
                            ->maxSize(config('upload.max_file_size_kb'))
                            ->conversion('thumb')
                            ->responsiveImages()
                            ->customHeaders(['CacheControl' => 'max-age=86400']),
                    ]),
            ]);
    }
}
