<?php

namespace App\Filament\Resources\MerchantLocations\Schemas;

use App\Enums\TargetType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class MerchantLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->required(),
                Select::make('merchant_category_id')
                    ->relationship('merchantCategory', 'name')
                    ->label('Store Category')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),
                Select::make('state_id')
                    ->relationship('state', 'name')
                    ->label('State')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->nullable(),
                Select::make('city_id')
                    ->relationship('city', 'name', fn (Builder $query, Get $get) => 
                        $query->when($get('state_id'), fn ($query, $stateId) => $query->where('state_id', $stateId))
                    )
                    ->label('City')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('branch_name')
                    ->required(),
                TextInput::make('commission_percentage')
                    ->required()
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),

                Section::make('Monthly Target & Loan Eligibility')
                    ->description('Configure monthly targets for streak-based loan eligibility. Leave target type empty to opt out.')
                    ->collapsible()
                    ->components([
                        Select::make('monthly_target_type')
                            ->label('Target Type')
                            ->options(TargetType::class)
                            ->nullable()
                            ->live()
                            ->helperText('Choose whether the target is an amount (₹) or transaction count.'),
                        TextInput::make('monthly_target_value')
                            ->label('Target Value')
                            ->numeric()
                            ->minValue(0.01)
                            ->nullable()
                            ->visible(fn (Get $get): bool => $get('monthly_target_type') !== null)
                            ->helperText('The threshold to meet each month. For amount: ₹ value. For count: number of transactions.'),
                        Toggle::make('deduct_commission_from_target')
                            ->label('Deduct Commission from Target')
                            ->default(true)
                            ->visible(fn (Get $get): bool => $get('monthly_target_type') === TargetType::Amount->value)
                            ->helperText('When enabled, the target comparison uses (bill amount - commission). When disabled, it uses the full bill amount.'),
                    ]),

                Section::make('Media')
                    ->description('Upload images and videos for this location.')
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
                            ->maxSize(102400)
                            ->conversion('thumb')
                            ->responsiveImages()
                            ->customHeaders(['CacheControl' => 'max-age=86400']),
                    ]),
            ]);
    }
}
