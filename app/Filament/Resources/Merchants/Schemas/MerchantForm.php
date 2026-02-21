<?php

namespace App\Filament\Resources\Merchants\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                FileUpload::make('logo')
                    ->image()
                    ->directory('merchants/logos')
                    ->visibility('public'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
