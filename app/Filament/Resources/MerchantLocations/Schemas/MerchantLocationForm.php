<?php

namespace App\Filament\Resources\MerchantLocations\Schemas;

use App\Enums\TargetType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                    ->relationship('state', 'name', fn (Builder $query) =>
                        $query->where('country_id', 102) // India only
                    )
                    ->label('State')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->nullable(),
                Select::make('city_id')
                    ->relationship('city', 'name', fn (Builder $query, Get $get) =>
                        $query->where('country_id', 102) // India only
                            ->when($get('state_id'), fn ($query, $stateId) => $query->where('state_id', $stateId))
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
                TextInput::make('star_rating')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->step(0.1)
                    ->nullable(),
                Toggle::make('is_active')
                    ->required(),

                Section::make('Address & Billing Details')
                    ->description('Store address, tax identifers, and bank details.')
                    ->collapsible()
                    ->columns(2)
                    ->components([
                        TextInput::make('address')
                            ->columnSpanFull()
                            ->maxLength(65535),
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90)
                            ->step(0.000001)
                            ->placeholder('e.g. 28.613939')
                            ->nullable()
                            ->helperText('Allow your browser to access location and the field will auto‑fill.')
                            // automatically populate from browser geolocation when form loads if empty
                            ->extraAttributes([
                                'x-init' => "navigator.geolocation && navigator.geolocation.getCurrentPosition(pos => {\
                                    if (!\$wire.get('latitude')) {\
                                        \$wire.set('latitude', pos.coords.latitude);\
                                        \$wire.set('longitude', pos.coords.longitude);\
                                    }\
                                })",
                            ]),
                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180)
                            ->step(0.000001)
                            ->placeholder('e.g. 77.209023')
                            ->nullable()
                            ->helperText('Populated with browser location when available.')
                            // keep extraAttributes so Alpine can set from latitude init above
                            ->extraAttributes([
                                // no additional script needed here; latitude x-init will set both fields
                            ]),
                        TextInput::make('gst_number')
                            ->label('GST Number')
                            ->maxLength(255),
                        TextInput::make('pan_number')
                            ->label('PAN Number')
                            ->maxLength(255),
                        TextInput::make('bank_name')
                            ->maxLength(255),
                        TextInput::make('sub_bank_name')
                            ->label('Branch / Sub Bank Name')
                            ->maxLength(255),
                        TextInput::make('account_number')
                            ->maxLength(255),
                        TextInput::make('ifsc_code')
                            ->label('IFSC Code')
                            ->maxLength(255),
                        TextInput::make('upi_id')
                            ->label('UPI ID')
                            ->maxLength(255),
                    ]),

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
                            ->maxSize(config('upload.max_file_size_kb'))
                            ->conversion('thumb')
                            ->responsiveImages()
                            ->customHeaders(['CacheControl' => 'max-age=86400']),
                    ]),
            ]);
    }
}
