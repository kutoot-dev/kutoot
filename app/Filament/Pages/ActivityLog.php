<?php

namespace App\Filament\Pages;

use App\Services\ActivityLogHumanizer;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected string $view = 'filament.pages.activity-log';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'Activity Log';

    public function table(Table $table): Table
    {
        $humanizer = app(ActivityLogHumanizer::class);

        return $table
            ->query(Activity::query()->with('subject', 'causer'))
            ->columns([
                TextColumn::make('description')
                    ->label('Activity')
                    ->state(fn ($record): string => $humanizer->humanize($record))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label('By')
                    ->default('System')
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
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'scanned' => 'Scanned',
                    ]),
                SelectFilter::make('subject_type')
                    ->label('Subject Type')
                    ->options(fn (): array => Activity::query()
                        ->distinct()
                        ->pluck('subject_type')
                        ->filter()
                        ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                        ->toArray()
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
