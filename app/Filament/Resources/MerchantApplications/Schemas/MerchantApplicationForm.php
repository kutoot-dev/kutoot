<?php

namespace App\Filament\Resources\MerchantApplications\Schemas;

use App\Enums\MerchantApplicationStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Store Details')
                    ->columns(2)
                    ->components([
                        TextInput::make('store_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('store_type')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->columnSpanFull()
                            ->maxLength(65535),
                    ]),

                Section::make('Contact Information')
                    ->columns(2)
                    ->components([
                        TextInput::make('owner_mobile')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('owner_email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Toggle::make('phone_verified')
                            ->disabled(),
                        Toggle::make('email_verified')
                            ->disabled(),
                    ]),

                Section::make('Tax Details')
                    ->columns(2)
                    ->collapsible()
                    ->components([
                        TextInput::make('gst_number')
                            ->label('GST Number')
                            ->maxLength(255),
                        TextInput::make('pan_number')
                            ->label('PAN Number')
                            ->maxLength(255),
                    ]),

                Section::make('Bank & Payout Details')
                    ->columns(2)
                    ->collapsible()
                    ->components([
                        TextInput::make('bank_name')
                            ->maxLength(255),
                        TextInput::make('sub_bank_name')
                            ->label('Branch Name')
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

                Section::make('Processing')
                    ->columns(2)
                    ->components([
                        Select::make('status')
                            ->options(MerchantApplicationStatus::class)
                            ->required(),
                        Textarea::make('admin_notes')
                            ->columnSpanFull()
                            ->maxLength(65535),
                    ]),
            ]);
    }
}
