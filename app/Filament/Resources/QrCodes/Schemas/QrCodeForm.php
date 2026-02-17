<?php

namespace App\Filament\Resources\QrCodes\Schemas;

use App\Models\QrCode;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\ViewField;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class QrCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('unique_code')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('token')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->suffixAction(
                        Action::make('regenerate')
                            ->icon('heroicon-m-arrow-path')
                            ->action(fn (TextInput $component) => $component->state(\Illuminate\Support\Str::random(32)))
                    ),
                Select::make('merchant_location_id')
                    ->relationship('merchantLocation', 'branch_name')
                    ->required(fn (string $operation) => $operation === 'edit'),
                Toggle::make('status')
                    ->label('Is Active')
                    ->required()
                    ->default(true),
                DateTimePicker::make('linked_at')
                    ->visible(fn (Get $get) => $get('status') === false),
                Select::make('linked_by')
                    ->relationship('executive', 'name')
                    ->required(fn (string $operation) => $operation === 'edit'),
                
                Section::make('QR Code Preview')
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        ViewField::make('preview')
                            ->view('filament.components.qr-code-preview')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
