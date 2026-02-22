<?php

use App\Models\Campaign;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Enums\SubscriptionStatus;

beforeEach(function () {
    $this->basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'duration_days' => null,
    ]);

    $this->bronzePlan = SubscriptionPlan::factory()->create([
        'name' => 'Bronze',
        'price' => 99,
        'duration_days' => 30,
    ]);

    $this->goldPlan = SubscriptionPlan::factory()->create([
        'name' => 'Gold',
        'price' => 599,
        'duration_days' => 90,
    ]);

    // Create campaigns with different plan access
    $this->bronzeCampaign = Campaign::factory()->create(['reward_name' => 'Bronze Reward']);
    $this->bronzeCampaign->plans()->attach([$this->bronzePlan->id, $this->goldPlan->id]);

    $this->goldCampaign = Campaign::factory()->create(['reward_name' => 'Gold Reward']);
    $this->goldCampaign->plans()->attach([$this->goldPlan->id]);

    $this->baseCampaign = Campaign::factory()->create(['reward_name' => 'Base Reward']);
    $this->baseCampaign->plans()->attach([$this->basePlan->id, $this->bronzePlan->id, $this->goldPlan->id]);
});

test('campaigns index shows all campaigns with eligibility for gold user', function () {
    $user = User::factory()->create();
    $user->assignRole('User');
    UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $this->goldPlan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($user)->get(route('campaigns.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Campaigns/Index')
        ->has('campaigns.data', 3)
    );

    $campaigns = $response->original->getData()['page']['props']['campaigns']['data'];
    foreach ($campaigns as $campaign) {
        expect($campaign['is_eligible'])->toBeTrue();
    }
});

test('campaigns index marks locked campaigns for bronze user', function () {
    $user = User::factory()->create();
    $user->assignRole('User');
    UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $this->bronzePlan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($user)->get(route('campaigns.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Campaigns/Index')
        ->has('campaigns.data', 3)
    );

    $campaigns = $response->original->getData()['page']['props']['campaigns']['data'];
    $goldCampaign = collect($campaigns)->firstWhere('id', $this->goldCampaign->id);

    expect($goldCampaign['is_eligible'])->toBeFalse()
        ->and($goldCampaign['required_plan'])->not->toBeNull()
        ->and($goldCampaign['required_plan']['name'])->toBe('Gold');
});

test('campaigns index shows all campaigns as ineligible for guest user', function () {
    $response = $this->get(route('campaigns.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Campaigns/Index')
        ->has('campaigns.data', 3)
    );

    $campaigns = $response->original->getData()['page']['props']['campaigns']['data'];
    foreach ($campaigns as $campaign) {
        expect($campaign['is_eligible'])->toBeFalse();
    }
});

test('locked campaigns include required plan info', function () {
    $user = User::factory()->create();
    $user->assignRole('User');
    UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $this->basePlan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($user)->get(route('campaigns.index'));

    $campaigns = $response->original->getData()['page']['props']['campaigns']['data'];
    $goldCampaign = collect($campaigns)->firstWhere('id', $this->goldCampaign->id);

    expect($goldCampaign['is_eligible'])->toBeFalse()
        ->and($goldCampaign['required_plan']['name'])->toBe('Gold')
        ->and($goldCampaign['required_plan']['price'])->toBe(599.0);
});
