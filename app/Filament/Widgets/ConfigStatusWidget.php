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
        // avoid container binding resolution; also guard if class missing
        if (! class_exists(SettingService::class)) {
            return ['integrations' => []];
        }

        $service = new SettingService();
        $status = $service->getConfigStatus();

        return [
            'integrations' => $status,
        ];
    }
}
