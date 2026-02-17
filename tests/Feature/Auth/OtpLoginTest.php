<?php

use App\Models\User;
use App\Services\OtpService;

test('otp login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('otp can be sent to a valid email', function () {
    $user = User::factory()->create(['email' => 'otp@example.com']);

    $response = $this->post('/otp-login/send', [
        'identifier' => 'otp@example.com',
    ]);

    $response->assertRedirect();
    $user->refresh();
    expect($user->otp_code)->not->toBeNull();
    expect($user->otp_expires_at)->not->toBeNull();
});

test('otp can be sent to a valid mobile', function () {
    $user = User::factory()->create(['mobile' => '9000000099']);

    $response = $this->post('/otp-login/send', [
        'identifier' => '9000000099',
    ]);

    $response->assertRedirect();
    $user->refresh();
    expect($user->otp_code)->not->toBeNull();
});

test('otp send fails for non-existent user', function () {
    $response = $this->post('/otp-login/send', [
        'identifier' => 'nobody@example.com',
    ]);

    $response->assertSessionHasErrors('identifier');
});

test('otp verification logs in the user', function () {
    $user = User::factory()->create(['email' => 'verify@example.com']);

    $otpService = app(OtpService::class);
    $otp = $otpService->generateOtp($user);

    $response = $this->post('/otp-login/verify', [
        'identifier' => 'verify@example.com',
        'otp' => $otp,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('otp verification fails with wrong otp', function () {
    $user = User::factory()->create(['email' => 'wrong@example.com']);

    $otpService = app(OtpService::class);
    $otpService->generateOtp($user);

    $response = $this->post('/otp-login/verify', [
        'identifier' => 'wrong@example.com',
        'otp' => '000000',
    ]);

    $response->assertSessionHasErrors('otp');
    $this->assertGuest();
});

test('otp verification fails with expired otp', function () {
    $user = User::factory()->create(['email' => 'expired@example.com']);

    $otpService = app(OtpService::class);
    $otp = $otpService->generateOtp($user);

    // Manually expire the OTP
    $user->update(['otp_expires_at' => now()->subMinutes(10)]);

    $response = $this->post('/otp-login/verify', [
        'identifier' => 'expired@example.com',
        'otp' => $otp,
    ]);

    $response->assertSessionHasErrors('otp');
    $this->assertGuest();
});
