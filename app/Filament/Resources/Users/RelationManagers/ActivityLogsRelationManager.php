<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Services\ActivityLogHumanizer;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity Log';

    public function table(Table $table): Table
    {
        $humanizer = app(ActivityLogHumanizer::class);

        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('description')
                    ->label('Activity')
                    ->state(fn ($record): string => $humanizer->humanize($record))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'scanned' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->state(fn ($record): string => class_basename($record->subject_type ?? ''))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
