<?php

namespace App\Filament\Resources\MerchantCategories\Pages;

use App\Filament\Resources\MerchantCategories\MerchantCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMerchantCategories extends ListRecords
{
    protected static string $resource = MerchantCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
