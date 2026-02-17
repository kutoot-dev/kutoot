<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use App\Enums\CreatorType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('creator_type')
                    ->options(CreatorType::class)
                    ->required(),
                Select::make('creator_id')
                    ->relationship('creator', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('reward_name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(CampaignStatus::class)
                    ->default('active')
                    ->required(),
                DatePicker::make('start_date')
                    ->required(),
                TextInput::make('reward_cost_target')
                    ->required()
                    ->numeric(),
                TextInput::make('stamp_target')
                    ->required()
                    ->numeric(),
                TextInput::make('collected_commission_cache')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('issued_stamps_cache')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('marketing_bounty_percentage')
                    ->label('Marketing Bounty %')
                    ->helperText('Dummy percentage added to the bounty meter for marketing purposes (0-100).')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100),
                DateTimePicker::make('winner_announcement_date'),
                CheckboxList::make('plans')
                    ->relationship('plans', 'name')
                    ->label('Eligible Subscription Plans')
                    ->helperText('Select which subscription plans can access this campaign.')
                    ->bulkToggleable()
                    ->columns(2),
            ]);
    }
}
