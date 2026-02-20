<?php

use App\Models\Campaign;
use App\Models\DiscountCoupon;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\QrCode;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLogHumanizer;

beforeEach(function () {
    $this->humanizer = app(ActivityLogHumanizer::class);
    // Activity logging is disabled in .env.testing, enable it for these tests
    app(\Spatie\Activitylog\ActivityLogStatus::class)->enable();
    config(['activitylog.database_connection' => config('database.default')]);
});

test('it humanizes user created events', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    $activity = activity()
        ->performedOn($user)
        ->event('created')
        ->withProperties(['attributes' => ['name' => 'John Doe']])
        ->log('created');

    $result = $this->humanizer->humanize($activity);

    expect($result)->toBe('Account was created');
});

test('it humanizes discount coupon created with discount details', function () {
    $coupon = DiscountCoupon::factory()->create([
        'title' => 'Summer Sale',
        'discount_type' => 'percentage',
        'discount_value' => 20,
    ]);

    $activity = activity()
        ->performedOn($coupon)
        ->event('created')
        ->withProperties(['attributes' => [
            'title' => 'Summer Sale',
            'discount_type' => 'percentage',
            'discount_value' => 20,
        ]])
        ->log('created');

    $result = $this->humanizer->humanize($activity);

    expect($result)->toContain('Summer Sale')
        ->and($result)->toContain('20')
        ->and($result)->toContain('%');
});

test('it humanizes fixed discount coupons with rupee symbol', function () {
    $coupon = DiscountCoupon::factory()->create([
        'title' => 'Flat Off',
        'discount_type' => 'fixed',
        'discount_value' => 100,
    ]);

    $activity = activity()
        ->performedOn($coupon)
        ->event('created')
        ->withProperties(['attributes' => [
            'title' => 'Flat Off',
            'discount_type' => 'fixed',
            'discount_value' => 100,
        ]])
        ->log('created');

    $result = $this->humanizer->humanize($activity);

    expect($result)->toContain('₹100');
});

test('it humanizes transaction events with amount', function () {
    $transaction = Transaction::factory()->create([
        'total_amount' => 599.00,
    ]);

    $activity = activity()
        ->performedOn($transaction)
        ->event('created')
        ->withProperties(['attributes' => ['total_amount' => 599.00]])
        ->log('created');

    $result = $this->humanizer->humanize($activity);

    expect($result)->toContain('₹')
        ->and($result)->toContain('599');
});

test('it humanizes campaign events', function () {
    $campaign = Campaign::factory()->create([
        'reward_name' => 'Free Coffee',
    ]);

    $activity = activity()
        ->performedOn($campaign)
        ->event('created')
        ->withProperties(['attributes' => ['reward_name' => 'Free Coffee']])
        ->log('created');

    $result = $this->humanizer->humanize($activity);

    expect($result)->toContain('Free Coffee');
});

test('it humanizes merchant location events', function () {
    $location = MerchantLocation::factory()->create([
        'branch_name' => 'Downtown Branch',
    ]);

    $activity = activity()
        ->performedOn($location)
        ->event('created')
        ->withProperties(['attributes' => ['branch_name' => 'Downtown Branch']])
        ->log('created');

    $result = $this->humanizer->humanize($activity);

    expect($result)->toContain('Downtown Branch');
});

test('it humanizes qr code scanned events', function () {
    $qrCode = QrCode::factory()->create([
        'unique_code' => 'QR-TEST-001',
    ]);

    $activity = activity()
        ->performedOn($qrCode)
        ->event('scanned')
        ->withProperties(['attributes' => ['unique_code' => 'QR-TEST-001']])
        ->log('scanned');

    $result = $this->humanizer->humanize($activity);

    expect($result)->toContain('QR-TEST-001')
        ->and($result)->toContain('scanned');
});

test('it returns correct emojis for event types', function () {
    expect($this->humanizer->icon('created'))->toBe('✨')
        ->and($this->humanizer->icon('updated'))->toBe('✏️')
        ->and($this->humanizer->icon('deleted'))->toBe('🗑️')
        ->and($this->humanizer->icon('scanned'))->toBe('📱')
        ->and($this->humanizer->icon('unknown_event'))->toBe('⚡');
});

test('it gracefully handles unmapped subject types', function () {
    $activity = new \Spatie\Activitylog\Models\Activity;
    $activity->subject_type = 'App\\Models\\UnknownModel';
    $activity->event = 'created';
    $activity->properties = collect([]);

    $result = $this->humanizer->humanize($activity);

    expect($result)->toBe('Created unknown model');
});

test('it humanizes updated and deleted events', function () {
    $merchant = Merchant::factory()->create(['name' => 'Cafe Mocha']);

    $updated = activity()
        ->performedOn($merchant)
        ->event('updated')
        ->withProperties(['attributes' => ['name' => 'Cafe Mocha']])
        ->log('updated');

    $resultUpdated = $this->humanizer->humanize($updated);

    expect($resultUpdated)->toContain('Cafe Mocha')
        ->and($resultUpdated)->toContain('updated');

    $deleted = activity()
        ->performedOn($merchant)
        ->event('deleted')
        ->log('deleted');

    $resultDeleted = $this->humanizer->humanize($deleted);

    expect($resultDeleted)->toBe('Merchant was removed');
});

test('it humanizes subscription plan events', function () {
    $plan = SubscriptionPlan::factory()->create(['name' => 'Premium Plan']);

    $activity = activity()
        ->performedOn($plan)
        ->event('created')
        ->withProperties(['attributes' => ['name' => 'Premium Plan']])
        ->log('created');

    $result = $this->humanizer->humanize($activity);

    expect($result)->toContain('Premium Plan');
});
