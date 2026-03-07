<?php

namespace App\Filament\Widgets;

use App\Services\SettingService;
use Filament\Widgets\Widget;

class ConfigStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.config-status';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $service = app(SettingService::class);
        $status = $service->getConfigStatus();

        return [
            'integrations' => $status,
        ];
    }
}
