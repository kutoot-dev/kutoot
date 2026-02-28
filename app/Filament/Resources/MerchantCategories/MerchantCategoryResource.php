<?php

namespace App\Filament\Resources\MerchantCategories;

use App\Filament\Resources\MerchantCategories\Pages\CreateMerchantCategory;
use App\Filament\Resources\MerchantCategories\Pages\EditMerchantCategory;
use App\Filament\Resources\MerchantCategories\Pages\ListMerchantCategories;
use App\Filament\Resources\MerchantCategories\Pages\ViewMerchantCategory;
use App\Filament\Resources\MerchantCategories\Schemas\MerchantCategoryForm;
use App\Filament\Resources\MerchantCategories\Schemas\MerchantCategoryInfolist;
use App\Filament\Resources\MerchantCategories\Tables\MerchantCategoriesTable;
use App\Models\MerchantCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MerchantCategoryResource extends Resource
{
    protected static ?string $model = MerchantCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Stores & Merchants';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MerchantCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MerchantCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantCategoriesTable::configure($table);
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
            'index' => ListMerchantCategories::route('/'),
            'create' => CreateMerchantCategory::route('/create'),
            'view' => ViewMerchantCategory::route('/{record}'),
            'edit' => EditMerchantCategory::route('/{record}/edit'),
        ];
    }
}
