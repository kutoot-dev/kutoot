<?php

namespace App\Filament\Resources\MerchantApplications\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Store Details')
                    ->columns(2)
                    ->components([
                        TextEntry::make('store_name'),
                        TextEntry::make('store_type'),
                        TextEntry::make('address')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Section::make('Contact Information')
                    ->columns(2)
                    ->components([
                        TextEntry::make('owner_mobile'),
                        TextEntry::make('owner_email'),
                        IconEntry::make('phone_verified')
                            ->boolean(),
                        IconEntry::make('email_verified')
                            ->boolean(),
                    ]),

                Section::make('Tax Details')
                    ->columns(2)
                    ->collapsible()
                    ->components([
                        TextEntry::make('gst_number')
                            ->label('GST Number')
                            ->placeholder('-'),
                        TextEntry::make('pan_number')
                            ->label('PAN Number')
                            ->placeholder('-'),
                    ]),

                Section::make('Bank & Payout Details')
                    ->columns(2)
                    ->collapsible()
                    ->components([
                        TextEntry::make('bank_name')
                            ->placeholder('-'),
                        TextEntry::make('sub_bank_name')
                            ->label('Branch Name')
                            ->placeholder('-'),
                        TextEntry::make('account_number')
                            ->placeholder('-'),
                        TextEntry::make('ifsc_code')
                            ->label('IFSC Code')
                            ->placeholder('-'),
                        TextEntry::make('upi_id')
                            ->label('UPI ID')
                            ->placeholder('-'),
                    ]),

                Section::make('Processing')
                    ->columns(2)
                    ->components([
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('processedByUser.name')
                            ->label('Processed By')
                            ->placeholder('-'),
                        TextEntry::make('processed_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('admin_notes')
                            ->columnSpanFull()
                            ->placeholder('-'),
                        TextEntry::make('merchantLocation.branch_name')
                            ->label('Created Store')
                            ->placeholder('-'),
                    ]),

                Section::make('Timestamps')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->components([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
