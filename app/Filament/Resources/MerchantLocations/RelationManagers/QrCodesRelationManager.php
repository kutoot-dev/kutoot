<?php

namespace App\Filament\Resources\MerchantLocations\RelationManagers;

use App\Enums\QrCodeStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QrCodesRelationManager extends RelationManager
{
    protected static string $relationship = 'qrCodes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('unique_code')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('token')
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('is_primary')
                    ->label('Is Primary')
                    ->helperText('Check to make this the main QR code for this location.'),
                Select::make('status')
                    ->options(QrCodeStatus::class)
                    ->default(QrCodeStatus::Available)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('unique_code')
            ->columns([
                TextColumn::make('unique_code')
                    ->searchable(),
                IconColumn::make('is_primary')
                    ->boolean()
                    ->label('Primary'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('executive.name')
                    ->label('Linked By'),
                TextColumn::make('linked_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
