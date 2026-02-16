<?php

namespace App\Listeners;

use App\Events\StampsIssued;
use App\Services\BountyService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessStampIssuanceListener implements ShouldQueue
{
    public function __construct(private BountyService $bountyService) {}

    public function handle(StampsIssued $event): void
    {
        $this->bountyService->onStampsIssued($event->campaign, $event->stampCount);
    }
}
