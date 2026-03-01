<?php

namespace App\Console\Commands;

use App\Services\StampReservationService;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'stamps:release-expired';

    protected $description = 'Expire stamp reservations that have passed their 5-minute window';

    public function handle(StampReservationService $service): int
    {
        $count = $service->releaseExpired();

        if ($count > 0) {
            $this->info("Released {$count} expired stamp reservation(s).");
        } else {
            $this->info('No expired reservations found.');
        }

        return self::SUCCESS;
    }
}
