<?php

use App\Models\DiscountCoupon;
use App\Models\MerchantLocation;
use App\Models\MerchantNotificationSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function createSellerWithLocation(array $locationOverrides = []): array
{
    $user = User::factory()->create([
        'username' => 'seller_'.fake()->unique()->bothify('####'),
        'password' => Hash::make('password'),
    ]);

    $location = MerchantLocation::factory()->create($locationOverrides);

    $location->users()->attach($user->id, ['role' => 'owner']);

    Sanctum::actingAs($user, ['merchant:*']);

    return [$user, $location];
}

function sellerBaseUrl(MerchantLocation $location): string
{
    return '/api/v1/merchant-locations/'.$location->id;
}

/*
|--------------------------------------------------------------------------
| Auth Endpoints
|--------------------------------------------------------------------------
*/

describe('Merchant Auth', function () {

    it('can login with valid credentials', function () {
        $user = User::factory()->create([
            'username' => 'testshop',
            'password' => Hash::make('secret123'),
        ]);
        $location = MerchantLocation::factory()->create();
        $location->users()->attach($user->id, ['role' => 'owner']);

        $this->postJson('/api/v1/merchant-locations/auth/login', [
            'username' => 'testshop',
            'password' => 'secret123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['token', 'seller' => ['sellerId', 'shopId', 'shopName']],
            ]);
    });

    it('rejects invalid credentials', function () {
        User::factory()->create([
            'username' => 'testshop',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson('/api/v1/merchant-locations/auth/login', [
            'username' => 'testshop',
            'password' => 'wrongpass',
        ])->assertUnauthorized();
    });

    it('can retrieve authenticated seller info', function () {
        [$user, $location] = createSellerWithLocation();

        $this->getJson('/api/v1/merchant-locations/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shopId', $location->id);
    });

    it('can logout', function () {
        [$user, $location] = createSellerWithLocation();

        $this->postJson('/api/v1/merchant-locations/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);
    });

    it('requires auth for me endpoint', function () {
        $this->getJson('/api/v1/merchant-locations/auth/me')
            ->assertUnauthorized();
    });
});

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
*/

describe('Access Control', function () {

    it('returns 403 when user has no access to location', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['merchant:*']);

        $location = MerchantLocation::factory()->create();

        $this->getJson(sellerBaseUrl($location).'/dashboard/summary')
            ->assertForbidden();
    });

    it('returns 401 when unauthenticated', function () {
        $location = MerchantLocation::factory()->create();

        $this->getJson(sellerBaseUrl($location).'/dashboard/summary')
            ->assertUnauthorized();
    });

    it('returns 404 for non-existent location', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['merchant:*']);

        $this->getJson('/api/v1/merchant-locations/99999/dashboard/summary')
            ->assertNotFound();
    });
});

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

describe('Dashboard', function () {

    it('returns dashboard summary', function () {
        [$user, $location] = createSellerWithLocation();

        $this->getJson(sellerBaseUrl($location).'/dashboard/summary')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['totalSales', 'totalDiscount', 'totalVisitors'],
            ]);
    });

    it('returns dashboard summary with transaction data', function () {
        [$user, $location] = createSellerWithLocation();

        Transaction::factory()->paid()->count(3)->create([
            'merchant_location_id' => $location->id,
        ]);

        $response = $this->getJson(sellerBaseUrl($location).'/dashboard/summary')
            ->assertOk();

        expect($response->json('data.totalSales'))->toBeGreaterThan(0);
    });

    it('returns revenue trend', function () {
        [$user, $location] = createSellerWithLocation();

        $this->getJson(sellerBaseUrl($location).'/dashboard/revenue-trend')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    });

    it('returns visitors trend', function () {
        [$user, $location] = createSellerWithLocation();

        $this->getJson(sellerBaseUrl($location).'/dashboard/visitors-trend')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    });
});

/*
|--------------------------------------------------------------------------
| Visitors
|--------------------------------------------------------------------------
*/

describe('Visitors', function () {

    it('lists visitors (distinct users who transacted)', function () {
        [$user, $location] = createSellerWithLocation();

        // Create 3 transactions for 2 distinct customers
        $customer1 = User::factory()->create();
        $customer2 = User::factory()->create();

        Transaction::factory()->paid()->create([
            'merchant_location_id' => $location->id,
            'user_id' => $customer1->id,
        ]);
        Transaction::factory()->paid()->create([
            'merchant_location_id' => $location->id,
            'user_id' => $customer1->id,
        ]);
        Transaction::factory()->paid()->create([
            'merchant_location_id' => $location->id,
            'user_id' => $customer2->id,
        ]);

        $this->getJson(sellerBaseUrl($location).'/visitors')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    });

    it('supports search in visitors', function () {
        [$user, $location] = createSellerWithLocation();

        $this->getJson(sellerBaseUrl($location).'/visitors?search=test')
            ->assertOk();
    });
});

/*
|--------------------------------------------------------------------------
| Store Profile
|--------------------------------------------------------------------------
*/

describe('Store Profile', function () {

    it('returns store profile', function () {
        [$user, $location] = createSellerWithLocation();

        $this->getJson(sellerBaseUrl($location).'/profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['location'],
            ]);
    });

    it('updates store profile', function () {
        [$user, $location] = createSellerWithLocation();

        $this->patchJson(sellerBaseUrl($location).'/profile', [
            'branch_name' => 'Updated Store Name',
            'address' => '123 New Address',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        expect($location->fresh()->branch_name)->toBe('Updated Store Name');
    });

    it('validates profile update fields', function () {
        [$user, $location] = createSellerWithLocation();

        $this->patchJson(sellerBaseUrl($location).'/profile', [
            'latitude' => 'not-a-number',
        ])
            ->assertUnprocessable();
    });
});

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

describe('Settings', function () {

    it('returns master admin settings', function () {
        [$user, $location] = createSellerWithLocation();

        $this->getJson(sellerBaseUrl($location).'/settings/master-admin')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => ['commissionPercent']]);
    });

    it('returns bank details', function () {
        [$user, $location] = createSellerWithLocation();

        $this->getJson(sellerBaseUrl($location).'/settings/bank')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    });

    it('updates bank details', function () {
        [$user, $location] = createSellerWithLocation();

        $this->patchJson(sellerBaseUrl($location).'/settings/bank', [
            'bank_name' => 'State Bank of India',
            'account_number' => '12345678901234',
            'ifsc_code' => 'SBIN0001234',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        expect($location->fresh()->bank_name)->toBe('State Bank of India');
    });
});

/*
|--------------------------------------------------------------------------
| Notification Settings
|--------------------------------------------------------------------------
*/

describe('Notification Settings', function () {

    it('returns notification settings with defaults', function () {
        [$user, $location] = createSellerWithLocation();

        $this->getJson(sellerBaseUrl($location).'/settings/notifications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['enabled', 'channels']]);
    });

    it('creates notification settings if not exist', function () {
        [$user, $location] = createSellerWithLocation();

        $this->putJson(sellerBaseUrl($location).'/settings/notifications', [
            'enabled' => false,
            'channels' => ['email' => true, 'sms' => false, 'whatsapp' => true],
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $setting = MerchantNotificationSetting::where('merchant_location_id', $location->id)->first();
        expect($setting)->not->toBeNull();
        expect($setting->enabled)->toBeFalse();
    });

    it('updates existing notification settings', function () {
        [$user, $location] = createSellerWithLocation();

        MerchantNotificationSetting::create([
            'merchant_location_id' => $location->id,
            'enabled' => true,
            'channels' => ['email' => true, 'sms' => true, 'whatsapp' => true],
        ]);

        $this->putJson(sellerBaseUrl($location).'/settings/notifications', [
            'enabled' => true,
            'channels' => ['email' => false, 'sms' => true, 'whatsapp' => false],
        ])->assertOk();

        $setting = MerchantNotificationSetting::where('merchant_location_id', $location->id)->first();
        expect($setting->channels['email'])->toBeFalse();
    });

    it('validates notification settings', function () {
        [$user, $location] = createSellerWithLocation();

        $this->putJson(sellerBaseUrl($location).'/settings/notifications', [
            'enabled' => 'not-a-boolean',
        ])->assertUnprocessable();
    });
});

/*
|--------------------------------------------------------------------------
| Change Password
|--------------------------------------------------------------------------
*/

describe('Change Password', function () {

    it('changes password with correct old password', function () {
        [$user, $location] = createSellerWithLocation();

        $this->putJson(sellerBaseUrl($location).'/settings/change-password', [
            'oldPassword' => 'password',
            'newPassword' => 'newpassword123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
    });

    it('rejects wrong old password', function () {
        [$user, $location] = createSellerWithLocation();

        $this->putJson(sellerBaseUrl($location).'/settings/change-password', [
            'oldPassword' => 'wrongpassword',
            'newPassword' => 'newpassword123',
        ])->assertUnprocessable();
    });

    it('validates new password minimum length', function () {
        [$user, $location] = createSellerWithLocation();

        $this->putJson(sellerBaseUrl($location).'/settings/change-password', [
            'oldPassword' => 'password',
            'newPassword' => 'abc',
        ])->assertUnprocessable();
    });
});

/*
|--------------------------------------------------------------------------
| Coupons / Deals
|--------------------------------------------------------------------------
*/

describe('Coupons', function () {

    it('lists coupons for the location', function () {
        [$user, $location] = createSellerWithLocation();

        DiscountCoupon::factory()->count(3)->create([
            'merchant_location_id' => $location->id,
        ]);

        $this->getJson(sellerBaseUrl($location).'/coupons')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.deals')
            // each returned deal should include a status attribute (could be approved by default)
            ->assertJsonStructure(['data' => ['deals' => [['status']]]]);
    });

    it('creates a coupon', function () {
        [$user, $location] = createSellerWithLocation();

        $this->postJson(sellerBaseUrl($location).'/coupons', [
            'title' => 'Summer Sale',
            'description' => '20% off all items',
            'discount_type' => 'percentage',
            'discount_value' => 20,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Summer Sale');

        expect(DiscountCoupon::where('merchant_location_id', $location->id)->count())->toBe(1);
    });

    it('auto-generates coupon code if not provided', function () {
        [$user, $location] = createSellerWithLocation();

        $response = $this->postJson(sellerBaseUrl($location).'/coupons', [
            'title' => 'Auto Code Deal',
            'discount_type' => 'percentage',
            'discount_value' => 50,
        ])->assertCreated();

        expect($response->json('data.code'))->not->toBeEmpty();
    });

    it('updates a coupon', function () {
        [$user, $location] = createSellerWithLocation();

        $coupon = DiscountCoupon::factory()->create([
            'merchant_location_id' => $location->id,
        ]);

        $this->patchJson(sellerBaseUrl($location).'/coupons/'.$coupon->id, [
            'title' => 'Updated Deal',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        expect($coupon->fresh()->title)->toBe('Updated Deal');
    });

    it('deactivates a coupon (soft delete)', function () {
        [$user, $location] = createSellerWithLocation();

        $coupon = DiscountCoupon::factory()->create([
            'merchant_location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->deleteJson(sellerBaseUrl($location).'/coupons/'.$coupon->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        expect($coupon->fresh()->is_active)->toBeFalse();
    });

    it('cannot update coupon of another location', function () {
        [$user, $location] = createSellerWithLocation();

        $otherLocation = MerchantLocation::factory()->create();
        $coupon = DiscountCoupon::factory()->create([
            'merchant_location_id' => $otherLocation->id,
        ]);

        $this->patchJson(sellerBaseUrl($location).'/coupons/'.$coupon->id, [
            'title' => 'Hacked',
        ])->assertForbidden();
    });

    it('cannot delete coupon of another location', function () {
        [$user, $location] = createSellerWithLocation();

        $otherLocation = MerchantLocation::factory()->create();
        $coupon = DiscountCoupon::factory()->create([
            'merchant_location_id' => $otherLocation->id,
        ]);

        $this->deleteJson(sellerBaseUrl($location).'/coupons/'.$coupon->id)
            ->assertForbidden();
    });

    it('validates coupon creation', function () {
        [$user, $location] = createSellerWithLocation();

        $this->postJson(sellerBaseUrl($location).'/coupons', [
            // missing required fields
        ])->assertUnprocessable();
    });
});
