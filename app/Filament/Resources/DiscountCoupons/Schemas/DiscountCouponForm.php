<?php

namespace App\Filament\Resources\DiscountCoupons\Schemas;

use App\Enums\DiscountType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DiscountCouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('coupon_category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('merchant_location_id')
                    ->relationship('merchantLocation', 'branch_name'),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('discount_type')
                    ->options(DiscountType::class)
                    ->required(),
                TextInput::make('discount_value')
                    ->required()
                    ->numeric(),
                TextInput::make('min_order_value')
                    ->numeric(),
                TextInput::make('max_discount_amount')
                    ->numeric(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('usage_limit')
                    ->numeric(),
                TextInput::make('usage_per_user')
                    ->required()
                    ->numeric()
                    ->default(1),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('expires_at'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
