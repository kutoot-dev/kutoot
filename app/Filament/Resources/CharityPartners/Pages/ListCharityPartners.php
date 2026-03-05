<?php

namespace App\Filament\Resources\CharityPartners\Pages;

use App\Filament\Resources\CharityPartners\CharityPartnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCharityPartners extends ListRecords
{
    protected static string $resource = CharityPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
