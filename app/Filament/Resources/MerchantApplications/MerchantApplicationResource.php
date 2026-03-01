<?php

namespace App\Filament\Resources\MerchantApplications;

use App\Filament\Resources\MerchantApplications\Pages\EditMerchantApplication;
use App\Filament\Resources\MerchantApplications\Pages\ListMerchantApplications;
use App\Filament\Resources\MerchantApplications\Pages\ViewMerchantApplication;
use App\Filament\Resources\MerchantApplications\Schemas\MerchantApplicationForm;
use App\Filament\Resources\MerchantApplications\Schemas\MerchantApplicationInfolist;
use App\Filament\Resources\MerchantApplications\Tables\MerchantApplicationsTable;
use App\Models\MerchantApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MerchantApplicationResource extends Resource
{
    protected static ?string $model = MerchantApplication::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Stores & Merchants';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Store Applications';

    protected static ?string $modelLabel = 'Store Application';

    protected static ?string $pluralModelLabel = 'Store Applications';

    public static function getNavigationBadge(): ?string
    {
        return (string) MerchantApplication::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return MerchantApplicationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MerchantApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantApplicationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchantApplications::route('/'),
            'view' => ViewMerchantApplication::route('/{record}'),
            'edit' => EditMerchantApplication::route('/{record}/edit'),
        ];
    }
}
