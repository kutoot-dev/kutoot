<?php

/**
 * Default Plan Assignment Tests
 *
 * Tests the complete first-login flow:
 * 1. When a user registers/logs in for the first time → they get the default plan subscription
 * 2. Bonus stamps from the default plan are attached to the user
 * 3. The default plan's campaign is set as the user's primary campaign
 *
 * Flow tested:
 *   User registers → Registered event fires → AssignBasePlanListener handles it
 *     → Creates UserSubscription (Active, no expiry)
 *     → If plan has stamps_on_purchase > 0, calls StampService::awardStampsForPlanPurchase()
 *
 *   Also tests SubscriptionService::assignDefaultPlan() which does the same but additionally:
 *     → Auto-subscribes user to plan's campaigns
 *     → Sets the first campaign as user's primary
 *     → Awards bonus stamps to that primary campaign
 */

use App\Enums\CampaignStatus;
use App\Enums\StampSource;
use App\Enums\StampStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Stamp;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ────────────────────────────────────────────────────────────────────────────────
// LISTENER: AssignBasePlanListener (fires on Registered event)
// ────────────────────────────────────────────────────────────────────────────────

test('newly registered user via Registered event gets default plan subscription', function () {
    // Arrange: create a default plan
    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 0,
        'duration_days' => null,
    ]);

    // Act: create user and fire the Registered event (same as what registration does)
    $user = User::factory()->create();
    event(new \Illuminate\Auth\Events\Registered($user));

    // Assert: user has active subscription to the default plan
    $subscription = $user->fresh()->activeSubscription;
    expect($subscription)->not->toBeNull()
        ->and($subscription->plan_id)->toBe($basePlan->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->expires_at)->toBeNull(); // Base plan never expires
});

test('Registered event creates subscription even when plan has bonus stamps', function () {
    // Arrange: default plan with bonus stamps + a campaign linked to it
    $campaign = Campaign::factory()->create([
        'code' => 'WELCOME',
        'stamp_slots' => 6,
        'stamp_slot_min' => 1,
        'stamp_slot_max' => 49,
        'is_active' => true,
        'status' => CampaignStatus::Active,
        'stamp_target' => 1000,
    ]);

    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 3, // 3 bonus stamps!
        'duration_days' => null,
    ]);

    // Link campaign to plan
    $basePlan->campaigns()->attach($campaign->id);

    // Act: create user and fire the Registered event
    $user = User::factory()->create();
    event(new \Illuminate\Auth\Events\Registered($user));

    // Assert: subscription is created regardless of stamps
    $subscription = $user->fresh()->activeSubscription;
    expect($subscription)->not->toBeNull()
        ->and($subscription->plan_id)->toBe($basePlan->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);

    // Note: AssignBasePlanListener awards stamps only if user has a primary_campaign_id.
    // For a brand-new user with no campaign subscriptions, stamps won't be awarded here.
    // The full flow (with campaigns + stamps) is handled by SubscriptionService::assignDefaultPlan().
});

// ────────────────────────────────────────────────────────────────────────────────
// SERVICE: SubscriptionService::assignDefaultPlan() — full first-login flow
// ────────────────────────────────────────────────────────────────────────────────

test('assignDefaultPlan creates active subscription to default plan', function () {
    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 0,
        'duration_days' => null,
    ]);

    $user = User::factory()->create();
    $service = app(SubscriptionService::class);

    $subscription = $service->assignDefaultPlan($user);

    expect($subscription)->not->toBeNull()
        ->and($subscription->plan_id)->toBe($basePlan->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->expires_at)->toBeNull();
});

test('assignDefaultPlan auto-subscribes user to plan campaigns and sets primary', function () {
    // Arrange: default plan with 2 campaigns
    $campaign1 = Campaign::factory()->create([
        'is_active' => true,
        'status' => CampaignStatus::Active,
        'stamp_target' => 1000,
    ]);
    $campaign2 = Campaign::factory()->create([
        'is_active' => true,
        'status' => CampaignStatus::Active,
        'stamp_target' => 1000,
    ]);

    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 0,
        'duration_days' => null,
    ]);
    $basePlan->campaigns()->attach([$campaign1->id, $campaign2->id]);

    $user = User::factory()->create();
    $service = app(SubscriptionService::class);

    // Act
    $service->assignDefaultPlan($user);
    $user->refresh();

    // Assert: user is subscribed to both campaigns
    expect($user->campaigns)->toHaveCount(2);

    // Assert: user has a primary campaign set (first one)
    expect($user->primary_campaign_id)->not->toBeNull();
    $primaryCampaign = $user->campaigns()->wherePivot('is_primary', true)->first();
    expect($primaryCampaign)->not->toBeNull();
});

test('assignDefaultPlan awards bonus stamps to primary campaign', function () {
    // Arrange: default plan with stamps_on_purchase and a linked campaign
    $campaign = Campaign::factory()->create([
        'code' => 'BONUS',
        'stamp_slots' => 6,
        'stamp_slot_min' => 1,
        'stamp_slot_max' => 49,
        'is_active' => true,
        'status' => CampaignStatus::Active,
        'stamp_target' => 1000,
    ]);

    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 5, // 5 bonus stamps
        'duration_days' => null,
    ]);
    $basePlan->campaigns()->attach($campaign->id);

    $user = User::factory()->create();
    $service = app(SubscriptionService::class);

    // Act
    $service->assignDefaultPlan($user);
    $user->refresh();

    // Assert: 5 stamps were created for the user on the campaign
    $stamps = Stamp::where('user_id', $user->id)
        ->where('campaign_id', $campaign->id)
        ->get();
    expect($stamps)->toHaveCount(5);

    // Assert: all stamps are from PlanPurchase source
    foreach ($stamps as $stamp) {
        expect($stamp->source)->toBe(StampSource::PlanPurchase);
        expect($stamp->status)->toBe(StampStatus::Used);
    }

    // Assert: stamp codes start with campaign code prefix
    foreach ($stamps as $stamp) {
        expect($stamp->code)->toStartWith('BONUS-');
    }
});

test('assignDefaultPlan skips if user already has active subscription', function () {
    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 5,
        'duration_days' => null,
    ]);

    $premiumPlan = SubscriptionPlan::factory()->create([
        'name' => 'Gold Plan',
        'price' => 999,
        'duration_days' => 30,
    ]);

    $user = User::factory()->create();

    // Give user an existing subscription
    UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $premiumPlan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(30),
    ]);

    $service = app(SubscriptionService::class);
    $subscription = $service->assignDefaultPlan($user);

    // Assert: should return existing subscription, not create new one
    $user->refresh();
    expect($user->activeSubscription->plan_id)->toBe($premiumPlan->id);
    expect(UserSubscription::where('user_id', $user->id)->count())->toBe(1);
});

test('assignDefaultPlan returns null when no default plan configured', function () {
    // No plan with is_default = true
    SubscriptionPlan::factory()->create(['is_default' => false]);

    $user = User::factory()->create();
    $service = app(SubscriptionService::class);

    $result = $service->assignDefaultPlan($user);

    expect($result)->toBeNull();
});

test('assignDefaultPlan does not award stamps when plan has zero stamps_on_purchase', function () {
    $campaign = Campaign::factory()->create([
        'is_active' => true,
        'status' => CampaignStatus::Active,
        'stamp_target' => 1000,
    ]);

    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 0, // No bonus stamps
        'duration_days' => null,
    ]);
    $basePlan->campaigns()->attach($campaign->id);

    $user = User::factory()->create();
    $service = app(SubscriptionService::class);

    $service->assignDefaultPlan($user);

    expect(Stamp::where('user_id', $user->id)->count())->toBe(0);
});

test('assignDefaultPlan creates a transaction record for bonus stamps', function () {
    $campaign = Campaign::factory()->create([
        'code' => 'TXNTEST',
        'stamp_slots' => 6,
        'stamp_slot_min' => 1,
        'stamp_slot_max' => 49,
        'is_active' => true,
        'status' => CampaignStatus::Active,
        'stamp_target' => 1000,
    ]);

    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 2,
        'duration_days' => null,
    ]);
    $basePlan->campaigns()->attach($campaign->id);

    $user = User::factory()->create();
    $service = app(SubscriptionService::class);

    $service->assignDefaultPlan($user);
    $user->refresh();

    // Assert: a transaction was created for the plan purchase
    $transaction = $user->transactions()->where('payment_gateway', 'plan_upgrade')->first();
    expect($transaction)->not->toBeNull()
        ->and((float) $transaction->amount)->toBe(0.0); // Free plan, so amount=0

    // Assert: stamps are linked to this transaction
    $stamps = Stamp::where('user_id', $user->id)->get();
    foreach ($stamps as $stamp) {
        expect($stamp->transaction_id)->toBe($transaction->id);
    }
});

test('campaign issued_stamps_cache is incremented after bonus stamps', function () {
    $campaign = Campaign::factory()->create([
        'code' => 'CACHETEST',
        'stamp_slots' => 6,
        'stamp_slot_min' => 1,
        'stamp_slot_max' => 49,
        'is_active' => true,
        'status' => CampaignStatus::Active,
        'stamp_target' => 1000,
        'issued_stamps_cache' => 0,
    ]);

    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 4,
        'duration_days' => null,
    ]);
    $basePlan->campaigns()->attach($campaign->id);

    $user = User::factory()->create();
    $service = app(SubscriptionService::class);

    $service->assignDefaultPlan($user);

    // Assert: campaign's issued_stamps_cache increased by at least 4
    // (StampsIssued event listener may also increment the cache)
    expect($campaign->fresh()->issued_stamps_cache)->toBeGreaterThanOrEqual(4);
});

// ────────────────────────────────────────────────────────────────────────────────
// OTP LOGIN AUTO-REGISTRATION: new user first login
// ────────────────────────────────────────────────────────────────────────────────

test('auto-registered user via OTP login gets default plan', function () {
    $basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'stamps_on_purchase' => 0,
        'duration_days' => null,
    ]);

    // OTP login with a new mobile → auto-creates user → fires Registered event
    $this->post('/otp-login/send', ['identifier' => '9999888877']);

    $user = User::where('mobile', '9999888877')->first();
    expect($user)->not->toBeNull();

    $otp = $user->otp_code;
    $this->post('/otp-login/verify', [
        'identifier' => '9999888877',
        'otp' => $otp,
    ]);

    $user->refresh();
    $subscription = $user->activeSubscription;
    expect($subscription)->not->toBeNull()
        ->and($subscription->plan_id)->toBe($basePlan->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);
});
