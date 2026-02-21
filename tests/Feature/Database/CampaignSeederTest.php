<?php

use App\Models\Campaign;
use App\Models\Stamp;
use Database\Seeders\CampaignSeeder;

it('seeder creates campaigns with stamps', function () {
    // ensure database is empty or fresh
    Campaign::query()->delete();
    Stamp::query()->delete();

    // run the seeder
    $this->seed(CampaignSeeder::class);

    // at least one campaign exists
    $campaign = Campaign::first();
    expect($campaign)->not->toBeNull();

    // campaign has stamp configuration (using helper method)
    expect($campaign->hasStampConfig())->toBeTrue();

    // at least one stamp exists for this campaign
    $stamp = Stamp::where('campaign_id', $campaign->id)->first();
    expect($stamp)->not->toBeNull();

    // stamp code matches regex
    expect($stamp->code)->toMatch('/^[A-Z0-9]+-[0-9\-]+$/');
});
