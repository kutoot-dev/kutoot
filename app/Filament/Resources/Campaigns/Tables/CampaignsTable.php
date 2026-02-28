<?php

namespace App\Filament\Resources\Campaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('media')
                    ->collection('media')
                    ->conversion('thumb')
                    ->limit(3)
                    ->circular(),
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('creator_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->searchable(),
                TextColumn::make('reward_name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('reward_cost_target')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('stamp_target')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('collected_commission_cache')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('issued_stamps_cache')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('marketing_bounty_percentage')
                    ->label('Marketing %')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('winner_announcement_date')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_premium')
                    ->boolean()
                    ->label('Premium')
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
            ->defaultSort('created_at', 'desc')
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
