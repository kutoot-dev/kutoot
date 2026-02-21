<?php

use Illuminate\Support\Arr;

beforeEach(function () {
    // Ensure no leftover environment values interfere with subsequent tests.
    putenv('RAZORPAY_KEY_ID');
    putenv('RAZORPAY_KEY_SECRET');
    putenv('RAZORPAY_WEBHOOK_SECRET');
    putenv('RAZORPAY_TEST_KEY_ID');
    putenv('RAZORPAY_TEST_KEY_SECRET');
    putenv('RAZORPAY_TEST_WEBHOOK_SECRET');
    putenv('RAZORPAY_LIVE_KEY_ID');
    putenv('RAZORPAY_LIVE_KEY_SECRET');
    putenv('RAZORPAY_LIVE_WEBHOOK_SECRET');
});

it('reads the new razorpay variables directly', function () {
    putenv('RAZORPAY_KEY_ID=foo_key');
    putenv('RAZORPAY_KEY_SECRET=foo_secret');
    putenv('RAZORPAY_WEBHOOK_SECRET=foo_webhook');

    $config = require realpath(__DIR__ . '/../../config/app.php');

    expect($config['razorpay']['key_id'])->toBe('foo_key');
    expect($config['razorpay']['key_secret'])->toBe('foo_secret');
    expect($config['razorpay']['webhook_secret'])->toBe('foo_webhook');
});

it('falls back to legacy test variables when new ones are absent', function () {
    putenv('RAZORPAY_TEST_KEY_ID=test_id');
    putenv('RAZORPAY_TEST_KEY_SECRET=test_secret');
    putenv('RAZORPAY_TEST_WEBHOOK_SECRET=test_webhook');

    $config = require realpath(__DIR__ . '/../../config/app.php');

    expect($config['razorpay']['key_id'])->toBe('test_id');
    expect($config['razorpay']['key_secret'])->toBe('test_secret');
    expect($config['razorpay']['webhook_secret'])->toBe('test_webhook');
});
