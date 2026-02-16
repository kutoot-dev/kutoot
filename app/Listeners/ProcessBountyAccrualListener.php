<?php

namespace App\Listeners;

use App\Events\CommissionEarned;
use App\Services\BountyService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessBountyAccrualListener implements ShouldQueue
{
    public function __construct(private BountyService $bountyService) {}

    public function handle(CommissionEarned $event): void
    {
        $this->bountyService->onCommissionEarned($event->transaction);
    }
}
