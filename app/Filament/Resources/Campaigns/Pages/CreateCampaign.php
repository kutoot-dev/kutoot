<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\CampaignResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Campaign Created')
            ->body('The campaign has been created successfully.');
    }
}
