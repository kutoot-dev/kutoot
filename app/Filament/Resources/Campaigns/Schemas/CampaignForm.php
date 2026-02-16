<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use App\Enums\CreatorType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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
                    ->required(),
                TextInput::make('reward_name')
                    ->required(),
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
                DateTimePicker::make('winner_announcement_date'),
            ]);
    }
}
