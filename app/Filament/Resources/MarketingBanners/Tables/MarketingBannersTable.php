<?php

namespace App\Filament\Resources\MarketingBanners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use App\Filament\Tables\Columns\MediaColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketingBannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                MediaColumn::make('images')
                    ->label('Image')
                    ->collection('images')
                    ->conversion('thumb')
                    ->circular(),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('subtitle')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort_order')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
