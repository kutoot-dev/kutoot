<?php

namespace App\Filament\Resources\SubscriptionPlans\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Display Order')
                    ->helperText('Controls the display order of plans. Lower numbers appear first. Also determines upgrade eligibility.')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                TextInput::make('price')
                    ->label('Price (₹)')
                    ->helperText('The price users pay to purchase this plan. Set to 0 for free/default plans.')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('₹'),
                Toggle::make('is_default')
                    ->label('Default Plan')
                    ->helperText('New users will be assigned to this plan automatically.'),

                TextInput::make('duration_days')
                    ->label('Plan Duration (Days)')
                    ->helperText('Number of days the plan is valid after purchase. Leave empty for plans that never expire (e.g. base plan).')
                    ->numeric()
                    ->nullable(),

                Section::make('Stamp Configuration')
                    ->schema([
                        TextInput::make('stamps_on_purchase')
                            ->label('Bonus Stamps on Purchase')
                            ->helperText('Number of stamps awarded when a user purchases this plan.')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('stamp_denomination')
                            ->label('Stamp Denomination (₹)')
                            ->helperText('Bill amount required to earn stamps. E.g. ₹10 means stamps are calculated per ₹10 of the bill.')
                            ->required()
                            ->numeric()
                            ->default(100)
                            ->minValue(0.01)
                            ->prefix('₹'),
                        TextInput::make('stamps_per_denomination')
                            ->label('Stamps per Denomination')
                            ->helperText('Number of stamps awarded for each denomination unit spent on a bill.')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(0),
                    ]),

                Section::make('Billing Limits')
                    ->schema([
                        TextInput::make('max_discounted_bills')
                            ->required()
                            ->numeric(),
                        TextInput::make('max_redeemable_amount')
                            ->required()
                            ->numeric(),
                    ]),

                Section::make('Access')
                    ->schema([
                        CheckboxList::make('campaigns')
                            ->relationship('campaigns', 'reward_name')
                            ->label('Eligible Campaigns')
                            ->helperText('Users on this plan can subscribe to these campaigns.')
                            ->bulkToggleable()
                            ->columns(2),
                        CheckboxList::make('couponCategories')
                            ->relationship('couponCategories', 'name')
                            ->label('Eligible Coupon Categories')
                            ->helperText('Users on this plan can use coupons from these categories.')
                            ->bulkToggleable()
                            ->columns(2),
                    ]),
            ]);
    }
}
