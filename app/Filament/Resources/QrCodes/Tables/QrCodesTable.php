<?php

namespace App\Filament\Resources\QrCodes\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QrCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unique_code')
                    ->searchable(),
                TextColumn::make('token')
                    ->searchable(),
                TextColumn::make('merchantLocation.branch_name')
                    ->label('Merchant Location')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('linked_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('executive.name')
                    ->label('Linked By')
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
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-m-eye')
                    ->modalContent(fn ($record) => view('filament.components.qr-code-preview', ['getRecord' => fn () => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalWidth('lg'),
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-m-printer')
                    ->modalContent(fn ($record) => view('filament.components.qr-code-preview', ['getRecord' => fn () => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalWidth('lg'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
