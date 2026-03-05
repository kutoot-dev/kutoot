<?php

namespace App\Filament\Resources\CharityPartners\Pages;

use App\Filament\Resources\CharityPartners\CharityPartnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditCharityPartner extends EditRecord
{
    protected static string $resource = CharityPartnerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = 'Charity Partner';

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
