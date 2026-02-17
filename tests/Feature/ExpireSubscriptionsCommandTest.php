<?php

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;

beforeEach(function () {
    $this->basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'duration_days' => null,
    ]);
});

test('expires subscriptions past their expiry date and reverts to base plan', function () {
    $plan = SubscriptionPlan::factory()->create(['duration_days' => 30]);

    $user = User::factory()->create();
    $subscription = UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('subscriptions:expire')
        ->expectsOutputToContain('Expired 1 subscription(s)')
        ->assertExitCode(0);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);

    $activeSubscription = $user->fresh()->activeSubscription;
    expect($activeSubscription)->not->toBeNull()
        ->and($activeSubscription->plan_id)->toBe($this->basePlan->id);
});

test('does not expire subscriptions that have not reached their expiry date', function () {
    $plan = SubscriptionPlan::factory()->create(['duration_days' => 30]);

    $user = User::factory()->create();
    $subscription = UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(10),
    ]);

    $this->artisan('subscriptions:expire')
        ->expectsOutputToContain('Expired 0 subscription(s)')
        ->assertExitCode(0);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

test('does not expire subscriptions without an expiry date', function () {
    $user = User::factory()->create();
    $subscription = UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $this->basePlan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => null,
    ]);

    $this->artisan('subscriptions:expire')
        ->expectsOutputToContain('Expired 0 subscription(s)')
        ->assertExitCode(0);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

test('assigns base plan to newly registered user', function () {
    // Send OTP for registration
    $this->post('/register/send-otp', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'mobile' => '9876543210',
    ]);

    // Get the debug OTP from the session
    $otpData = session('otp.9876543210');
    expect($otpData)->not->toBeNull();

    // Verify OTP and register
    $this->post('/register/verify', [
        'otp' => $otpData['code'],
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    $subscription = $user->activeSubscription;
    expect($subscription)->not->toBeNull()
        ->and($subscription->plan_id)->toBe($this->basePlan->id)
        ->and($subscription->expires_at)->toBeNull();
});
