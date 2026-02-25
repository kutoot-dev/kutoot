<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ── Show Profile ─────────────────────────────────────────────────────────

it('returns the authenticated user profile', function () {
    $country = Country::first();
    $state = State::where('country_id', $country->id)->first();
    $city = City::where('state_id', $state->id)->first();

    $user = User::factory()->create([
        'gender' => 'male',
        'country_id' => $country->id,
        'state_id' => $state->id,
        'city_id' => $city->id,
        'pin_code' => '560001',
        'full_address' => 'Some address',
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.gender', 'male')
        ->assertJsonPath('data.country_id', $country->id);
});

it('requires auth to view profile', function () {
    $this->getJson('/api/v1/profile')
        ->assertUnauthorized();
});

// ── Update Profile ───────────────────────────────────────────────────────

it('updates user profile', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => 'Updated Name',
        'email' => $user->email,
        'mobile' => null,
        'gender' => 'other',
    ])->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.gender', 'other');
});

it('validates email uniqueness on profile update', function () {
    $existing = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => $user->name,
        'email' => 'taken@example.com',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('allows keeping own email on profile update', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => 'Same Email',
        'email' => $user->email,
    ])->assertSuccessful();
});

it('requires at least one contact method', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => 'No Contact',
        'email' => null,
        'mobile' => null,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'mobile']);
});

it('allows updating only mobile when email absent', function () {
    $user = User::factory()->create(['email' => null]);
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => 'Mobile Only',
        'mobile' => '9123456789',
    ])->assertSuccessful()
        ->assertJsonPath('data.mobile', '9123456789');
});

it('updates country state city by id', function () {
    $country = Country::first();
    $state = State::where('country_id', $country->id)->first();
    $city = City::where('state_id', $state->id)->first();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'country_id' => $country->id,
        'state_id' => $state->id,
        'city_id' => $city->id,
    ])->assertSuccessful()
        ->assertJsonPath('data.country_id', $country->id)
        ->assertJsonPath('data.state_id', $state->id)
        ->assertJsonPath('data.city_id', $city->id);
});

it('validates country_id exists in countries table', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'country_id' => 999999,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['country_id']);
});

// ── Avatar Upload ─────────────────────────────────────────────────────
it('uploads a profile picture', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('avatar.jpg');

    $this->patchJson('/api/v1/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'profile_picture' => $file,
    ])->assertSuccessful();

    $this->assertNotNull($user->fresh()->getFirstMedia('avatar'));
});

// ── World Reference Endpoints ───────────────────────────────────────────

it('returns countries list publicly', function () {
    $this->getJson('/api/v1/countries')
        ->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'name']]]);
});

it('returns states filtered by country_id', function () {
    $country = Country::first();

    $this->getJson("/api/v1/states?filters[country_id]={$country->id}")
        ->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'name']]]);
});

it('returns cities filtered by state_id', function () {
    $country = Country::first();
    $state = State::where('country_id', $country->id)->first();

    $this->getJson("/api/v1/cities?filters[state_id]={$state->id}")
        ->assertSuccessful()
        ->assertJsonStructure(['data' => [['id', 'name']]]);
});

// ── Delete Account ───────────────────────────────────────────────────────

it('deletes user account', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/profile')
        ->assertSuccessful();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
