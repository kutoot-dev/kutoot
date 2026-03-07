<?php

namespace App\Filament\Resources\MerchantLocations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use App\Filament\Tables\Columns\MediaColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\State;

class MerchantLocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('merchant.name')
                    ->searchable(),
                TextColumn::make('merchantCategory.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch_name')
                    ->searchable(),
                TextColumn::make('state.name')
                    ->label('State')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('city.name')
                    ->label('City')
                    ->sortable()
                    ->toggleable(),
                MediaColumn::make('media')
                    ->collection('media')
                    ->conversion('thumb')
                    ->limit(3)
                    ->circular(),
                TextColumn::make('commission_percentage')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('star_rating')
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
            ->filters([
                SelectFilter::make('state_id')
                    ->label('State')
                    ->options(fn () => State::where('country_id', 102)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('city_id')
                    ->label('City')
                    ->options(fn () => City::where('country_id', 102)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
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
