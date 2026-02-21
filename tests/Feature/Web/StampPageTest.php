<?php

use App\Models\Campaign;
use App\Models\Stamp;
use App\Models\User;

it('requires authentication to view stamps page', function () {
    $response = $this->get(route('stamps.index'));
    $response->assertRedirect(route('login'));
});

it('shows stamps grouped by campaign with primary campaign highlighted', function () {
    $user = User::factory()->create();
    $campaignA = Campaign::factory()->create(['reward_name' => 'Campaign Alpha']);
    $campaignB = Campaign::factory()->create(['reward_name' => 'Campaign Beta']);

    $stampsA = Stamp::factory()->count(2)
        ->for($user)
        ->for($campaignA)
        ->create();
    $stampsB = Stamp::factory()->count(1)
        ->for($user)
        ->for($campaignB)
        ->create();

    $user->primary_campaign_id = $campaignA->id;
    $user->save();

    $response = $this->actingAs($user)->get(route('stamps.index'));
    $response->assertStatus(200);

    $response->assertInertia(fn ($page) => $page
        ->component('Stamps/Index')
        ->where('primaryCampaign', 'Campaign Alpha')
        ->where('totalStamps', 3)
        ->has('stampGroups.Campaign Alpha', 2)
        ->has('stampGroups.Campaign Beta', 1)
    );
});

it('shows empty state when user has no stamps', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('stamps.index'));
    $response->assertStatus(200);

    $response->assertInertia(fn ($page) => $page
        ->component('Stamps/Index')
        ->where('totalStamps', 0)
        ->where('stamps', [])
    );
});

it('does not show other users stamps', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $campaign = Campaign::factory()->create(['reward_name' => 'Shared Campaign']);

    Stamp::factory()
        ->for($otherUser)
        ->for($campaign)
        ->create(['code' => 'OTHER-999']);

    Stamp::factory()
        ->for($user)
        ->for($campaign)
        ->create(['code' => 'MINE-111']);

    $response = $this->actingAs($user)->get(route('stamps.index'));
    $response->assertStatus(200);

    $response->assertInertia(fn ($page) => $page
        ->component('Stamps/Index')
        ->where('totalStamps', 1)
        ->where('stamps.0.code', 'MINE-111')
    );
});

it('orders primary campaign first in stamp groups', function () {
    $user = User::factory()->create();
    $campaignA = Campaign::factory()->create(['reward_name' => 'Zebra Campaign']);
    $campaignB = Campaign::factory()->create(['reward_name' => 'Alpha Campaign']);

    Stamp::factory()->for($user)->for($campaignA)->create();
    Stamp::factory()->for($user)->for($campaignB)->create();

    // set Zebra as primary; should still appear first
    $user->primary_campaign_id = $campaignA->id;
    $user->save();

    $response = $this->actingAs($user)->get(route('stamps.index'));
    $response->assertStatus(200);

    $response->assertInertia(fn ($page) => $page
        ->component('Stamps/Index')
        ->has('stampGroups')
        ->where('primaryCampaign', 'Zebra Campaign')
    );

    // verify primary campaign is the first key in stamp groups
    $stampGroups = $response->original->getData()['page']['props']['stampGroups'];
    $keys = array_keys(is_array($stampGroups) ? $stampGroups : $stampGroups->toArray());
    expect($keys[0])->toBe('Zebra Campaign');
});

it('includes editable stamp information', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'reward_name' => 'Editable Campaign',
        'stamp_slots' => 3,
        'stamp_slot_min' => 1,
        'stamp_slot_max' => 45,
    ]);

    Stamp::factory()
        ->for($user)
        ->for($campaign)
        ->create(['editable_until' => now()->addMinutes(30)]);

    $response = $this->actingAs($user)->get(route('stamps.index'));
    $response->assertStatus(200);

    $response->assertInertia(fn ($page) => $page
        ->component('Stamps/Index')
        ->where('stamps.0.is_editable', true)
        ->has('stamps.0.stamp_config')
        ->where('stamps.0.stamp_config.slots', 3)
        ->where('stamps.0.stamp_config.min', 1)
        ->where('stamps.0.stamp_config.max', 45)
    );
});
