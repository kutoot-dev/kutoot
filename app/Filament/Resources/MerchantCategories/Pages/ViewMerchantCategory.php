<?php

namespace App\Filament\Resources\MerchantCategories\Pages;

use App\Filament\Resources\MerchantCategories\MerchantCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMerchantCategory extends ViewRecord
{
    protected static string $resource = MerchantCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
