<?php

use App\Filament\Pages\ActivityLog;

it('does not declare the view property as static', function () {
    $reflection = new \ReflectionClass(ActivityLog::class);
    $property = $reflection->getProperty('view');

    expect($property->isStatic())->toBeFalse();
});
