<?php

namespace App\Filament\Resources\CouponRedemptions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CouponRedemptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('discount_coupon_id')
                    ->relationship('coupon', 'title')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('transaction_id')
                    ->relationship('transaction', 'amount')
                    ->required(),
                TextInput::make('discount_applied')
                    ->required()
                    ->numeric(),
            ]);
    }
}
