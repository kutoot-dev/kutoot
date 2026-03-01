<?php

namespace App\Filament\Resources\MerchantApplications\Pages;

use App\Filament\Resources\MerchantApplications\MerchantApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMerchantApplications extends ListRecords
{
    protected static string $resource = MerchantApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
