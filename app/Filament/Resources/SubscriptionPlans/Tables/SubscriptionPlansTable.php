<?php

namespace App\Filament\Resources\SubscriptionPlans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('price')
                    ->money('INR')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),
                TextColumn::make('stamps_on_purchase')
                    ->label('Stamps on Buy')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('stamp_denomination')
                    ->label('Denomination')
                    ->prefix('₹')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('stamps_per_denomination')
                    ->label('Stamps/Denom')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_discounted_bills')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_redeemable_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('Duration')
                    ->suffix(' days')
                    ->placeholder('∞')
                    ->sortable(),
                TextColumn::make('campaigns_count')
                    ->counts('campaigns')
                    ->label('Campaigns')
                    ->sortable(),
                TextColumn::make('coupon_categories_count')
                    ->counts('couponCategories')
                    ->label('Coupon Cats')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
