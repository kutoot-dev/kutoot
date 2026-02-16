<?php

namespace App\Filament\Resources\SubscriptionPlans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('max_discounted_bills')
                    ->required()
                    ->numeric(),
                TextInput::make('max_redeemable_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('max_concurrent_campaigns_per_bill')
                    ->required()
                    ->numeric(),
            ]);
    }
}
