<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformTermsResource\Pages\ManagePlatformTerms;
use App\Filament\Resources\PlatformTermsResource\Schemas\PlatformTermsForm;
use App\Filament\Resources\PlatformTermsResource\Tables\PlatformTermsTable;
use App\Models\PlatformTerms;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlatformTermsResource extends Resource
{
    protected static ?string $model = PlatformTerms::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PlatformTermsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformTermsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlatformTerms::route('/'),
        ];
    }
}
