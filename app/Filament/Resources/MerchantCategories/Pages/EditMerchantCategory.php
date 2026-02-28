<?php

namespace App\Filament\Resources\MerchantCategories\Pages;

use App\Filament\Resources\MerchantCategories\MerchantCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMerchantCategory extends EditRecord
{
    protected static string $resource = MerchantCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
