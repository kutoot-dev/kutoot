<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('returns dashboard data for authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/dashboard')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'user' => [
                    'name',
                    'email',
                    'mobile',
                    'gender',
                    'country_id',
                    'country_name',
                    'state_id',
                    'state_name',
                    'city_id',
                    'city_name',
                    'pin_code',
                    'full_address',
                    'profile_picture_url',
                ],
                'plan',
                'stats',
            ],
        ]);
});

it('requires auth for dashboard', function () {
    $this->getJson('/api/v1/dashboard')
        ->assertUnauthorized();
});
