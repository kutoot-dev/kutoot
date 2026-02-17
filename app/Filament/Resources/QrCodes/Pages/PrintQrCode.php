<?php

namespace App\Filament\Resources\QrCodes\Pages;

use App\Filament\Resources\QrCodeResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class PrintQrCode extends Page
{
    use InteractsWithRecord;

    protected static string $resource = QrCodeResource::class;

    protected string $view = 'filament.resources.qr-code-resource.pages.print-qr-code';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
