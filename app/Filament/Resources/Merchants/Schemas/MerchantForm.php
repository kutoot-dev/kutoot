<?php

namespace App\Filament\Resources\Merchants\Schemas;

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
                TextInput::make('logo'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
