<?php

namespace App\Filament\Resources\MerchantCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use App\Filament\Tables\Columns\MediaColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MerchantCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                MediaColumn::make('image')
                    ->collection('image')
                    ->conversion('thumb')
                    ->circular(),
                MediaColumn::make('icon')
                    ->collection('icon')
                    ->conversion('thumb')
                    ->circular(),
                TextColumn::make('serial')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('serial')
            ->reorderable('serial')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
