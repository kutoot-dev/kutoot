<?php

use App\Models\Campaign;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ── Plans ────────────────────────────────────────────────────────────────

it('lists subscription plans', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    SubscriptionPlan::factory()->count(3)->create();

    $this->getJson('/api/v1/subscriptions/plans')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('requires auth for plans listing', function () {
    $this->getJson('/api/v1/subscriptions/plans')
        ->assertUnauthorized();
});

// ── Current Subscription ─────────────────────────────────────────────────

it('returns current subscription', function () {
    $plan = SubscriptionPlan::factory()->create(['is_default' => true]);
    $user = User::factory()->create();
    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/subscriptions/current')
        ->assertSuccessful()
        ->assertJsonStructure(['data']);
});

// ── Upgrade ──────────────────────────────────────────────────────────────

it('validates plan_id for upgrade', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/subscriptions/upgrade', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['plan_id']);
});

it('rejects upgrade with non-existent plan', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/subscriptions/upgrade', [
        'plan_id' => 99999,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['plan_id']);
});

// ── Verify Payment ───────────────────────────────────────────────────────

it('validates required fields for subscription payment verification', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/subscriptions/verify-payment', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature']);
});

// ── Primary Campaign ─────────────────────────────────────────────────────

it('validates campaign_id for setting primary campaign', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/subscriptions/primary-campaign', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['campaign_id']);
});

// ── Campaign Selections on Upgrade ──────────────────────────────────────

it('accepts upgrade with campaign_selections', function () {
    $plan = SubscriptionPlan::factory()->create(['price' => 0, 'is_default' => false, 'stamps_on_purchase' => 0]);
    $campaign = Campaign::factory()->create();
    $campaign->plans()->attach($plan->id);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/subscriptions/upgrade', [
        'plan_id' => $plan->id,
        'campaign_selections' => [
            ['campaign_id' => $campaign->id, 'stamp_count' => 3],
        ],
    ])->assertSuccessful();
});

it('accepts upgrade without campaign_selections (backwards compatible)', function () {
    $plan = SubscriptionPlan::factory()->create(['price' => 0, 'is_default' => false, 'stamps_on_purchase' => 0]);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/subscriptions/upgrade', [
        'plan_id' => $plan->id,
    ])->assertSuccessful();
});

it('validates campaign_selections has valid campaign_id', function () {
    $plan = SubscriptionPlan::factory()->create(['price' => 0, 'is_default' => false]);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/subscriptions/upgrade', [
        'plan_id' => $plan->id,
        'campaign_selections' => [
            ['campaign_id' => 99999, 'stamp_count' => 1],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['campaign_selections.0.campaign_id']);
});

it('validates campaign_selections stamp_count is non-negative', function () {
    $plan = SubscriptionPlan::factory()->create(['price' => 0, 'is_default' => false]);
    $campaign = Campaign::factory()->create();
    $campaign->plans()->attach($plan->id);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/subscriptions/upgrade', [
        'plan_id' => $plan->id,
        'campaign_selections' => [
            ['campaign_id' => $campaign->id, 'stamp_count' => -1],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['campaign_selections.0.stamp_count']);
});
