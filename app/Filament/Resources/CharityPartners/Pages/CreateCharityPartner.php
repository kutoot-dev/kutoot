<?php

namespace App\Filament\Resources\CharityPartners\Pages;

use App\Filament\Resources\CharityPartners\CharityPartnerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateCharityPartner extends CreateRecord
{
    protected static string $resource = CharityPartnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'Charity Partner';

        return $data;
    }

    protected function afterCreate(): void
    {
        Cache::forget('sponsors:active');
    }
}
