<?php

namespace App\Filament\Resources\QrCodes\Pages;

use App\Enums\QrCodeStatus;
use App\Filament\Resources\QrCodeResource;
use App\Models\QrCode;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

class ListQrCodes extends ListRecords
{
    protected static string $resource = QrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_batch')
                ->label('Generate Batch')
                ->icon('heroicon-o-qr-code')
                ->form([
                    TextInput::make('quantity')
                        ->label('Number of QR Codes to Generate')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(100)
                        ->default(10),
                ])
                ->action(function (array $data) {
                    $quantity = (int) $data['quantity'];

                    for ($i = 0; $i < $quantity; $i++) {
                        QrCode::create([
                            'unique_code' => 'KUT-' . strtoupper(Str::random(8)),
                            'token' => Str::random(32),
                            'status' => QrCodeStatus::Available,
                        ]);
                    }

                    Notification::make()
                        ->title("Successfully generated {$quantity} QR Codes.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
