<?php

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BountyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(BountyService::class);
});

test('it recalculates bounty meter correctly', function () {
    $merchant = Merchant::factory()->create();
    $location = MerchantLocation::factory()->for($merchant)->create();
    $user = User::factory()->create();

    $campaign = Campaign::factory()->create([
        'reward_cost_target' => 100.00,
        'stamp_target' => 10,
        'collected_commission_cache' => 50.00,
        'issued_stamps_cache' => 5,
        'status' => CampaignStatus::Active,
    ]);

    // Calculation: (50/100 * 0.66) + (5/10 * 0.33) = 0.33 + 0.165 = 0.495
    $meter = $this->service->recalculateBountyMeter($campaign);

    expect($meter)->toBeGreaterThan(0.49);
    expect($meter)->toBeLessThan(0.50);
});

test('it accrues commission and updates cache', function () {
    $campaign = Campaign::factory()->create([
        'reward_cost_target' => 100.00,
        'collected_commission_cache' => 10.00,
    ]);

    $transaction = Transaction::factory()->create([
        'commission_amount' => 5.50,
    ]);

    // Associate transaction with campaign if needed, but the service handles it by filtering campaigns?
    // Actually our listener filters campaigns that have the transaction's merchant location.
    // So let's ensure models are correctly linked.
    $this->service->onCommissionEarned($campaign, 5.50);

    $campaign->refresh();
    expect($campaign->collected_commission_cache)->toEqual(15.50);
});

test('it completes campaign when meter reaches 100%', function () {
    $campaign = Campaign::factory()->create([
        'reward_cost_target' => 100.00,
        'stamp_target' => 10,
        'collected_commission_cache' => 100.00, // 66%
        'issued_stamps_cache' => 10, // 33%
        'status' => CampaignStatus::Active,
    ]);

    $transaction = Transaction::factory()->create([
        'commission_amount' => 2.00,
    ]);

    $this->service->onCommissionEarned($campaign, 10.00);

    $campaign->refresh();
    expect($campaign->status)->toBe(CampaignStatus::Completed);
    expect($campaign->collected_commission_cache)->toEqual(110.00);
});

test('effective bounty percentage includes marketing boost', function () {
    $campaign = Campaign::factory()->create([
        'reward_cost_target' => 100.00,
        'stamp_target' => 10,
        'collected_commission_cache' => 0,
        'issued_stamps_cache' => 0,
        'marketing_bounty_percentage' => 25,
        'status' => CampaignStatus::Active,
    ]);

    $percentage = $this->service->effectiveBountyPercentage($campaign);

    // Organic is 0%, marketing adds 25%
    expect($percentage)->toBe(25);
});

test('effective bounty percentage combines organic and marketing', function () {
    $campaign = Campaign::factory()->create([
        'reward_cost_target' => 100.00,
        'stamp_target' => 10,
        'collected_commission_cache' => 50.00,
        'issued_stamps_cache' => 5,
        'marketing_bounty_percentage' => 20,
        'status' => CampaignStatus::Active,
    ]);

    // Organic: (50/100 * 0.66) + (5/10 * 0.33) = 0.33 + 0.165 = 0.495 => ~50%
    // Marketing: 20%
    // Total: ~70%
    $percentage = $this->service->effectiveBountyPercentage($campaign);

    expect($percentage)->toBeGreaterThanOrEqual(69);
    expect($percentage)->toBeLessThanOrEqual(70);
});

test('effective bounty percentage caps at 100', function () {
    $campaign = Campaign::factory()->create([
        'reward_cost_target' => 100.00,
        'stamp_target' => 10,
        'collected_commission_cache' => 100.00,
        'issued_stamps_cache' => 10,
        'marketing_bounty_percentage' => 50,
        'status' => CampaignStatus::Active,
    ]);

    $percentage = $this->service->effectiveBountyPercentage($campaign);

    expect($percentage)->toBe(100);
});

test('effective bounty percentage works with zero marketing', function () {
    $campaign = Campaign::factory()->create([
        'reward_cost_target' => 100.00,
        'stamp_target' => 10,
        'collected_commission_cache' => 50.00,
        'issued_stamps_cache' => 5,
        'marketing_bounty_percentage' => 0,
        'status' => CampaignStatus::Active,
    ]);

    // Organic only: ~50%
    $percentage = $this->service->effectiveBountyPercentage($campaign);

    expect($percentage)->toBeGreaterThanOrEqual(49);
    expect($percentage)->toBeLessThanOrEqual(50);
});
