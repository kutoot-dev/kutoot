<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire subscriptions daily
Schedule::command('subscriptions:expire')->daily();

// Prune activity logs older than 90 days (weekly to avoid large deletes)
Schedule::command('activitylog:clean --days=90')->weekly();

// Prune old personal access tokens
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Recalculate monthly targets on the 1st of each month at 1:00 AM
Schedule::command('app:recalculate-monthly-targets')->monthlyOn(1, '01:00');

// Release expired stamp reservations every minute
Schedule::command('stamps:release-expired')->everyMinute();

// Clean up Livewire S3 temporary uploads daily at 1:00 AM
Schedule::command('livewire:configure-s3-upload-cleanup')->dailyAt('1:00');
