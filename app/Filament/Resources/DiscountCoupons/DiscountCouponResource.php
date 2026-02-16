<?php

namespace App\Filament\Resources\DiscountCoupons;

use App\Filament\Resources\DiscountCoupons\Pages\CreateDiscountCoupon;
use App\Filament\Resources\DiscountCoupons\Pages\EditDiscountCoupon;
use App\Filament\Resources\DiscountCoupons\Pages\ListDiscountCoupons;
use App\Filament\Resources\DiscountCoupons\Schemas\DiscountCouponForm;
use App\Filament\Resources\DiscountCoupons\Tables\DiscountCouponsTable;
use App\Models\DiscountCoupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DiscountCouponResource extends Resource
{
    protected static ?string $model = DiscountCoupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DiscountCouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountCouponsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscountCoupons::route('/'),
            'create' => CreateDiscountCoupon::route('/create'),
            'edit' => EditDiscountCoupon::route('/{record}/edit'),
        ];
    }
}
