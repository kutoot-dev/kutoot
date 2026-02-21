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
    $response->assertDontSee('📜');
    $response->assertDontSee('Activity Log');
    $response->assertDontSee('📋');
});
