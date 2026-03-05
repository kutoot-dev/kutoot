<?php

namespace App\Filament\Resources\QrCodes\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Collection;

class QrCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unique_code')
                    ->searchable(),
                IconColumn::make('is_primary')
                    ->boolean()
                    ->label('Primary')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('token')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    BulkAction::make('bulk_print')
                        ->label('Bulk Print')
                        ->icon('heroicon-o-printer')
                        ->form([
                            Select::make('layout')
                                ->options([
                                    '3-across' => 'Landscape (3 stickers across)',
                                    'single' => 'Single sticker',
                                ])
                                ->default('3-across')
                                ->required(),
                            TextInput::make('sticker_width')
                                ->label('Sticker Width (mm)')
                                ->numeric()
                                ->default(38),
                            TextInput::make('sticker_height')
                                ->label('Sticker Height (mm)')
                                ->numeric()
                                ->default(25),
                            TextInput::make('margin')
                                ->label('Margin (mm)')
                                ->numeric()
                                ->default(2),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $ids = $records->pluck('id')->join(',');
                            $url = route('qr-code.bulk-print') . "?ids={$ids}&" . http_build_query($data);
                            return redirect($url);
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
