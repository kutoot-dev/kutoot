<?php

namespace App\Filament\Resources\CharityPartners;

use App\Filament\Resources\CharityPartners\Pages\CreateCharityPartner;
use App\Filament\Resources\CharityPartners\Pages\EditCharityPartner;
use App\Filament\Resources\CharityPartners\Pages\ListCharityPartners;
use App\Filament\Resources\CharityPartners\Schemas\CharityPartnerForm;
use App\Filament\Resources\CharityPartners\Tables\CharityPartnersTable;
use App\Models\Sponsor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CharityPartnerResource extends Resource
{
    protected static ?string $model = Sponsor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Charity Partners';

    protected static ?string $modelLabel = 'Charity Partner';

    protected static ?string $pluralModelLabel = 'Charity Partners';

    protected static ?string $slug = 'charity-partners';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'Charity Partner');
    }

    public static function form(Schema $schema): Schema
    {
        return CharityPartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CharityPartnersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCharityPartners::route('/'),
            'create' => CreateCharityPartner::route('/create'),
            'edit' => EditCharityPartner::route('/{record}/edit'),
        ];
    }
}
