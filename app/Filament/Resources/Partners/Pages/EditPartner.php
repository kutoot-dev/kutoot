<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Filament\Resources\Partners\PartnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = 'Partner';

        return $data;
    }

    protected function afterSave(): void
    {
        Cache::forget('sponsors:active');
    }

    protected function afterDelete(): void
    {
        Cache::forget('sponsors:active');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(fn () => Cache::forget('sponsors:active')),
        ];
    }
}
