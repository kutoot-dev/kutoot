<?php

namespace App\Filament\Resources\PlatformTermsResource\Pages;

use App\Filament\Resources\PlatformTermsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePlatformTerms extends ManageRecords
{
    protected static string $resource = PlatformTermsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
