<?php

use App\Enums\CampaignStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\CampaignSubscriptionService;

beforeEach(function () {
    $this->service = app(CampaignSubscriptionService::class);

    // Create tiered plans
    $this->basePlan = SubscriptionPlan::factory()->create(['name' => 'Base Plan', 'is_default' => true, 'price' => 0]);
    $this->bronzePlan = SubscriptionPlan::factory()->create(['name' => 'Bronze', 'price' => 99]);
    $this->goldPlan = SubscriptionPlan::factory()->create(['name' => 'Gold', 'price' => 599]);

    // Create campaigns with plan access
    $this->baseCampaign = Campaign::factory()->create(['reward_name' => 'Base Campaign']);
    $this->baseCampaign->plans()->attach([$this->basePlan->id, $this->bronzePlan->id, $this->goldPlan->id]);

    $this->bronzeCampaign = Campaign::factory()->create(['reward_name' => 'Bronze Campaign']);
    $this->bronzeCampaign->plans()->attach([$this->bronzePlan->id, $this->goldPlan->id]);

    $this->goldCampaign = Campaign::factory()->create(['reward_name' => 'Gold Campaign']);
    $this->goldCampaign->plans()->attach([$this->goldPlan->id]);

    // Create user on Gold plan
    $this->user = User::factory()->create();
    UserSubscription::create([
        'user_id' => $this->user->id,
        'plan_id' => $this->goldPlan->id,
        'status' => SubscriptionStatus::Active,
    ]);
});

// --- Subscribe Tests ---

test('user can subscribe to a campaign accessible under their plan', function () {
    $this->service->subscribe($this->user, $this->goldCampaign->id);

    expect($this->user->isSubscribedToCampaign($this->goldCampaign->id))->toBeTrue();
});

test('first campaign subscription is automatically set as primary', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);

    $this->user->refresh();
    expect($this->user->primary_campaign_id)->toBe($this->baseCampaign->id);
    expect($this->user->campaigns()->wherePivot('is_primary', true)->count())->toBe(1);
});

test('subsequent subscriptions are not primary', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->subscribe($this->user, $this->bronzeCampaign->id);

    $primaryCount = $this->user->campaigns()->wherePivot('is_primary', true)->count();
    expect($primaryCount)->toBe(1);
    expect($this->user->campaigns()->count())->toBe(2);
});

test('user can subscribe to multiple campaigns', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->subscribe($this->user, $this->bronzeCampaign->id);
    $this->service->subscribe($this->user, $this->goldCampaign->id);

    expect($this->user->campaigns()->count())->toBe(3);
});

test('subscribing to same campaign twice throws exception', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);

    $this->service->subscribe($this->user, $this->baseCampaign->id);
})->throws(InvalidArgumentException::class, 'already subscribed');

test('subscribing to campaign not in plan throws exception', function () {
    // Create a campaign only accessible to a plan the user does NOT have
    $premiumPlan = SubscriptionPlan::factory()->create(['name' => 'Premium', 'price' => 9999]);
    $premiumCampaign = Campaign::factory()->create(['reward_name' => 'Premium Only']);
    $premiumCampaign->plans()->attach($premiumPlan->id);

    $this->service->subscribe($this->user, $premiumCampaign->id);
})->throws(InvalidArgumentException::class, 'requires');

test('subscribing to inactive campaign throws exception', function () {
    $inactiveCampaign = Campaign::factory()->create(['is_active' => false]);
    $inactiveCampaign->plans()->attach($this->goldPlan->id);

    $this->service->subscribe($this->user, $inactiveCampaign->id);
})->throws(InvalidArgumentException::class, 'not currently active');

test('subscribing to closed campaign throws exception', function () {
    $closedCampaign = Campaign::factory()->create(['status' => CampaignStatus::Closed]);
    $closedCampaign->plans()->attach($this->goldPlan->id);

    $this->service->subscribe($this->user, $closedCampaign->id);
})->throws(InvalidArgumentException::class, 'cannot accept new subscribers');

test('subscribing to fully stamped campaign throws exception', function () {
    $fullCampaign = Campaign::factory()->create([
        'stamp_target' => 10,
        'issued_stamps_cache' => 10,
    ]);
    $fullCampaign->plans()->attach($this->goldPlan->id);

    $this->service->subscribe($this->user, $fullCampaign->id);
})->throws(InvalidArgumentException::class, 'stamp target');

// --- Unsubscribe Tests ---

test('user can unsubscribe from a campaign', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->subscribe($this->user, $this->bronzeCampaign->id);

    $this->service->unsubscribe($this->user, $this->bronzeCampaign->id);

    expect($this->user->campaigns()->count())->toBe(1);
    expect($this->user->isSubscribedToCampaign($this->bronzeCampaign->id))->toBeFalse();
});

test('unsubscribing primary campaign promotes next campaign', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->subscribe($this->user, $this->bronzeCampaign->id);

    // baseCampaign is primary
    expect($this->user->fresh()->primary_campaign_id)->toBe($this->baseCampaign->id);

    $this->service->unsubscribe($this->user, $this->baseCampaign->id);

    $this->user->refresh();
    expect($this->user->primary_campaign_id)->toBe($this->bronzeCampaign->id);
    expect($this->user->campaigns()->wherePivot('is_primary', true)->first()->id)->toBe($this->bronzeCampaign->id);
});

test('unsubscribing last campaign clears primary', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->unsubscribe($this->user, $this->baseCampaign->id);

    expect($this->user->fresh()->primary_campaign_id)->toBeNull();
});

test('unsubscribing from non-subscribed campaign throws exception', function () {
    $this->service->unsubscribe($this->user, $this->baseCampaign->id);
})->throws(InvalidArgumentException::class, 'not subscribed');

// --- Set Primary Tests ---

test('user can change primary campaign', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->subscribe($this->user, $this->bronzeCampaign->id);

    $this->service->setPrimary($this->user, $this->bronzeCampaign->id);

    $this->user->refresh();
    expect($this->user->primary_campaign_id)->toBe($this->bronzeCampaign->id);

    // Exactly one primary
    expect($this->user->campaigns()->wherePivot('is_primary', true)->count())->toBe(1);
});

test('setting primary on unsubscribed campaign throws exception', function () {
    $this->service->setPrimary($this->user, $this->baseCampaign->id);
})->throws(InvalidArgumentException::class, 'must be subscribed');

// --- Plan Change Reconciliation Tests ---

test('downgrade removes campaigns not accessible in new plan', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->subscribe($this->user, $this->bronzeCampaign->id);
    $this->service->subscribe($this->user, $this->goldCampaign->id);

    // Downgrade to Bronze (loses Gold campaign)
    $result = $this->service->reconcileAfterPlanChange($this->user, $this->bronzePlan);

    expect($result['removed'])->toContain($this->goldCampaign->id);
    expect($result['kept'])->toContain($this->baseCampaign->id);
    expect($result['kept'])->toContain($this->bronzeCampaign->id);
    expect($this->user->isSubscribedToCampaign($this->goldCampaign->id))->toBeFalse();
});

test('downgrade auto-promotes new primary if primary was removed', function () {
    $this->service->subscribe($this->user, $this->goldCampaign->id);
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->setPrimary($this->user, $this->goldCampaign->id);

    // Downgrade to Bronze (loses Gold campaign which was primary)
    $this->service->reconcileAfterPlanChange($this->user, $this->bronzePlan);

    $this->user->refresh();
    expect($this->user->primary_campaign_id)->toBe($this->baseCampaign->id);
});

test('downgrade to plan with no campaigns clears all subscriptions and primary', function () {
    $this->service->subscribe($this->user, $this->goldCampaign->id);

    $emptyPlan = SubscriptionPlan::factory()->create(['name' => 'Empty Plan', 'price' => 0]);

    $this->service->reconcileAfterPlanChange($this->user, $emptyPlan);

    $this->user->refresh();
    expect($this->user->campaigns()->count())->toBe(0);
    expect($this->user->primary_campaign_id)->toBeNull();
});

// --- Auto-Subscribe Tests ---

test('auto-subscribe adds all accessible campaigns for the plan', function () {
    $newIds = $this->service->autoSubscribeForPlan($this->user, $this->goldPlan);

    expect($newIds)->toHaveCount(3); // base, bronze, gold campaigns
    expect($this->user->campaigns()->count())->toBe(3);
});

test('auto-subscribe does not duplicate existing subscriptions', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);

    $newIds = $this->service->autoSubscribeForPlan($this->user, $this->goldPlan);

    expect($newIds)->toHaveCount(2); // only bronze + gold, not base
    expect($this->user->campaigns()->count())->toBe(3);
});

test('auto-subscribe sets first campaign as primary if none exists', function () {
    $this->service->autoSubscribeForPlan($this->user, $this->goldPlan);

    $this->user->refresh();
    expect($this->user->primary_campaign_id)->not()->toBeNull();
    expect($this->user->campaigns()->wherePivot('is_primary', true)->count())->toBe(1);
});

test('auto-subscribe skips inactive campaigns', function () {
    $inactiveCampaign = Campaign::factory()->create(['is_active' => false]);
    $inactiveCampaign->plans()->attach($this->goldPlan->id);

    $newIds = $this->service->autoSubscribeForPlan($this->user, $this->goldPlan);

    expect($newIds)->not()->toContain($inactiveCampaign->id);
});

// --- Available & Locked Campaigns ---

test('get available campaigns returns unsubscribed accessible campaigns', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);

    $available = $this->service->getAvailableCampaigns($this->user);

    expect($available->pluck('id'))->toContain($this->bronzeCampaign->id);
    expect($available->pluck('id'))->toContain($this->goldCampaign->id);
    expect($available->pluck('id'))->not()->toContain($this->baseCampaign->id);
});

test('get locked campaigns returns campaigns not in plan with required plan info', function () {
    // Create campaign only accessible to a premium plan
    $premiumPlan = SubscriptionPlan::factory()->create(['name' => 'Premium', 'price' => 9999]);
    $premiumCampaign = Campaign::factory()->create(['reward_name' => 'Premium Only']);
    $premiumCampaign->plans()->attach($premiumPlan->id);

    // User is on Gold - this campaign should be locked
    $locked = $this->service->getLockedCampaigns($this->user);

    $lockedCampaignIds = $locked->pluck('campaign.id');
    expect($lockedCampaignIds)->toContain($premiumCampaign->id);

    $premiumLock = $locked->firstWhere('campaign.id', $premiumCampaign->id);
    expect($premiumLock['required_plan']->name)->toBe('Premium');
});

// --- Edge Cases ---

test('only one primary campaign allowed at a time', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->subscribe($this->user, $this->bronzeCampaign->id);
    $this->service->subscribe($this->user, $this->goldCampaign->id);

    $this->service->setPrimary($this->user, $this->bronzeCampaign->id);
    $this->service->setPrimary($this->user, $this->goldCampaign->id);

    expect($this->user->campaigns()->wherePivot('is_primary', true)->count())->toBe(1);
    expect($this->user->fresh()->primary_campaign_id)->toBe($this->goldCampaign->id);
});

test('user without any subscription cannot subscribe to a campaign', function () {
    $noSubUser = User::factory()->create();
    // No subscription at all, and no default base plan
    SubscriptionPlan::where('is_default', true)->delete();

    $this->service->subscribe($noSubUser, $this->baseCampaign->id);
})->throws(InvalidArgumentException::class);

test('primary campaign id stays in sync with pivot', function () {
    $this->service->subscribe($this->user, $this->baseCampaign->id);
    $this->service->subscribe($this->user, $this->bronzeCampaign->id);

    $this->user->refresh();
    $pivotPrimary = $this->user->campaigns()->wherePivot('is_primary', true)->first();

    expect($this->user->primary_campaign_id)->toBe($pivotPrimary->id);
});
