<?php

namespace App\Filament\Resources\LoanTiers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanTiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('min_streak_months')
                    ->label('Min Streak')
                    ->suffix(' months')
                    ->sortable(),
                TextColumn::make('max_loan_amount')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('interest_rate_percentage')
                    ->label('Interest %')
                    ->suffix('%')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
