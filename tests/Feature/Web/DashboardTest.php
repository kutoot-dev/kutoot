<?php

use App\Models\Transaction;
use App\Models\User;

it('dashboard does not render recent activity section', function () {
    $user = User::factory()->create();

    // add some transactions so the old section would have shown
    Transaction::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertStatus(200);

    // ensure the title and emoji no longer exist in the response
    $response->assertDontSee('Recent Activity');
    $response->assertDontSee('Activity Log');
});

it('dashboard does not pass stamps or stampGroups props', function () {
    $user = User::factory()->create();
    $campaign = \App\Models\Campaign::factory()->create(['reward_name' => 'Test Campaign']);

    \App\Models\Stamp::factory()->count(3)
        ->for($user)
        ->for($campaign)
        ->create();

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertStatus(200);

    // stamps and stampGroups should NOT be in dashboard props anymore
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->missing('stamps')
        ->missing('stampGroups')
    );
});

it('dashboard returns correct stats props', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertStatus(200);

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('user')
        ->has('stats')
        ->has('stats.stamps_count')
        ->has('stats.total_coupons_used')
        ->has('stats.total_discount_redeemed')
        ->has('stats.remaining_bills')
        ->has('stats.remaining_redeem_amount')
    );
});

it('dashboard renders identically for free and paid plan users', function () {
    $freeUser = User::factory()->create();
    $paidUser = User::factory()->create();

    // give paid user a subscription
    $plan = \App\Models\SubscriptionPlan::factory()->create(['is_default' => false]);
    \App\Models\UserSubscription::factory()->create([
        'user_id' => $paidUser->id,
        'plan_id' => $plan->id,
        'expires_at' => now()->addDays(30),
    ]);

    $freeResponse = $this->actingAs($freeUser)->get(route('dashboard'));
    $paidResponse = $this->actingAs($paidUser)->get(route('dashboard'));

    $freeResponse->assertStatus(200);
    $paidResponse->assertStatus(200);

    // both render the same Dashboard component
    $freeResponse->assertInertia(fn ($page) => $page->component('Dashboard'));
    $paidResponse->assertInertia(fn ($page) => $page->component('Dashboard'));

    // neither should have stamps or stampGroups props
    $freeResponse->assertInertia(fn ($page) => $page->missing('stamps')->missing('stampGroups'));
    $paidResponse->assertInertia(fn ($page) => $page->missing('stamps')->missing('stampGroups'));
});
