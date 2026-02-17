<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register via OTP', function () {
    // Step 1: Send OTP
    $response = $this->post('/register/send-otp', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'mobile' => '9876543210',
    ]);

    $response->assertRedirect();

    // Step 2: Verify OTP - grab it from session
    $otpData = session('otp.9876543210');
    expect($otpData)->not->toBeNull();

    $response = $this->post('/register/verify', [
        'otp' => $otpData['code'],
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'mobile' => '9876543210',
    ]);
});

test('registration fails with duplicate email', function () {
    \App\Models\User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/register/send-otp', [
        'name' => 'Test User',
        'email' => 'taken@example.com',
        'mobile' => '9876543211',
    ]);

    $response->assertSessionHasErrors('email');
});

test('registration fails with duplicate mobile', function () {
    \App\Models\User::factory()->create(['mobile' => '9876543210']);

    $response = $this->post('/register/send-otp', [
        'name' => 'Test User',
        'email' => 'new@example.com',
        'mobile' => '9876543210',
    ]);

    $response->assertSessionHasErrors('mobile');
});
