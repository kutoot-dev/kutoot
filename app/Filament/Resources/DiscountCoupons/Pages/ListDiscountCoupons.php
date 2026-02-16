<?php

namespace App\Filament\Resources\DiscountCoupons\Pages;

use App\Filament\Resources\DiscountCoupons\DiscountCouponResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscountCoupons extends ListRecords
{
    protected static string $resource = DiscountCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
