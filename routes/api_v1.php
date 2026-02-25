<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\MerchantLocationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QrScanController;
use App\Http\Controllers\Api\V1\StampController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;
use Nnjeim\World\Http\Controllers\City\CityController;
use Nnjeim\World\Http\Controllers\Country\CountryController;
use Nnjeim\World\Http\Controllers\State\StateController;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| All routes are automatically prefixed with /api/v1 and use the 'api'
| middleware group. Sanctum token-based auth is used for protected routes.
|
*/

// ── World reference data (public, no auth) ─────────────────────────────
Route::get('/countries', [CountryController::class, 'index'])
    ->name('api.v1.countries.index');
Route::get('/states', [StateController::class, 'index'])
    ->name('api.v1.states.index');
Route::get('/cities', [CityController::class, 'index'])
    ->name('api.v1.cities.index');

// ── Authentication (public) ─────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/otp/send', [AuthController::class, 'sendOtp'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.otp.send');

    Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.otp.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user'])
            ->name('api.v1.auth.user');

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');
    });
});

// ── Public endpoints ─────────────────────────────────────────────────────
Route::get('/campaigns', [CampaignController::class, 'index'])
    ->name('api.v1.campaigns.index');

Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])
    ->name('api.v1.campaigns.show');

// Subscription plans (public, no auth needed)
Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans'])
    ->name('api.v1.subscriptions.plans');

// ── Authenticated user endpoints ─────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('api.v1.dashboard');

    // Campaigns (auth-only)
    Route::get('/campaigns/{campaign}/bounty', [CampaignController::class, 'bounty'])
        ->name('api.v1.campaigns.bounty');

    // Coupons
    Route::get('/coupons', [CouponController::class, 'index'])
        ->name('api.v1.coupons.index');
    Route::get('/coupons/{coupon}', [CouponController::class, 'show'])
        ->name('api.v1.coupons.show');
    Route::post('/coupons/{coupon}/redeem', [CouponController::class, 'redeem'])
        ->name('api.v1.coupons.redeem');
    Route::post('/coupons/verify-payment', [CouponController::class, 'verifyPayment'])
        ->name('api.v1.coupons.verify-payment');

    // Subscriptions (auth-protected)
    Route::get('/subscriptions/current', [SubscriptionController::class, 'current'])
        ->name('api.v1.subscriptions.current');
    Route::post('/subscriptions/upgrade', [SubscriptionController::class, 'upgrade'])
        ->name('api.v1.subscriptions.upgrade');
    Route::post('/subscriptions/verify-payment', [SubscriptionController::class, 'verifyPayment'])
        ->name('api.v1.subscriptions.verify-payment');
    Route::post('/subscriptions/primary-campaign', [SubscriptionController::class, 'setPrimaryCampaign'])
        ->name('api.v1.subscriptions.primary-campaign');

    // Stamps
    Route::get('/stamps', [StampController::class, 'index'])
        ->name('api.v1.stamps.index');
    Route::patch('/stamps/{stamp}/code', [StampController::class, 'updateCode'])
        ->name('api.v1.stamps.update-code');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])
        ->name('api.v1.transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])
        ->name('api.v1.transactions.show');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('api.v1.profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('api.v1.profile.update');
    Route::patch('/profile/primary-campaign', [ProfileController::class, 'updatePrimaryCampaign'])
        ->name('api.v1.profile.primary-campaign');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('api.v1.profile.destroy');

    // QR Scan
    Route::get('/qr/{token}/scan', [QrScanController::class, 'scan'])
        ->name('api.v1.qr.scan');

    // Merchant Locations
    Route::get('/merchant-locations', [MerchantLocationController::class, 'index'])
        ->name('api.v1.merchant-locations.index');
});

// ── Admin endpoints ──────────────────────────────────────────────────────
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    // Campaigns
    Route::apiResource('campaigns', \App\Http\Controllers\Api\V1\Admin\CampaignController::class)
        ->names('api.v1.admin.campaigns');

    // Campaign Categories
    Route::apiResource('campaign-categories', \App\Http\Controllers\Api\V1\Admin\CampaignCategoryController::class)
        ->names('api.v1.admin.campaign-categories');

    // Merchants
    Route::apiResource('merchants', \App\Http\Controllers\Api\V1\Admin\MerchantController::class)
        ->names('api.v1.admin.merchants');

    // Merchant Locations
    Route::apiResource('merchant-locations', \App\Http\Controllers\Api\V1\Admin\MerchantLocationController::class)
        ->names('api.v1.admin.merchant-locations');

    // Discount Coupons
    Route::apiResource('coupons', \App\Http\Controllers\Api\V1\Admin\DiscountCouponController::class)
        ->names('api.v1.admin.coupons');

    // Coupon Categories
    Route::apiResource('coupon-categories', \App\Http\Controllers\Api\V1\Admin\CouponCategoryController::class)
        ->names('api.v1.admin.coupon-categories');

    // Coupon Redemptions (read-only)
    Route::apiResource('coupon-redemptions', \App\Http\Controllers\Api\V1\Admin\CouponRedemptionController::class)
        ->only(['index', 'show'])
        ->names('api.v1.admin.coupon-redemptions');

    // Subscription Plans
    Route::apiResource('subscription-plans', \App\Http\Controllers\Api\V1\Admin\SubscriptionPlanController::class)
        ->names('api.v1.admin.subscription-plans');

    // User Subscriptions (read-only)
    Route::apiResource('user-subscriptions', \App\Http\Controllers\Api\V1\Admin\UserSubscriptionController::class)
        ->only(['index', 'show'])
        ->names('api.v1.admin.user-subscriptions');

    // Transactions (read-only)
    Route::apiResource('transactions', \App\Http\Controllers\Api\V1\Admin\TransactionController::class)
        ->only(['index', 'show'])
        ->names('api.v1.admin.transactions');

    // Users
    Route::apiResource('users', \App\Http\Controllers\Api\V1\Admin\UserController::class)
        ->names('api.v1.admin.users');

    // Stamps (read-only)
    Route::apiResource('stamps', \App\Http\Controllers\Api\V1\Admin\StampController::class)
        ->only(['index', 'show'])
        ->names('api.v1.admin.stamps');

    // QR Codes
    Route::apiResource('qr-codes', \App\Http\Controllers\Api\V1\Admin\QrCodeController::class)
        ->names('api.v1.admin.qr-codes');
    Route::post('qr-codes/generate-batch', [\App\Http\Controllers\Api\V1\Admin\QrCodeController::class, 'generateBatch'])
        ->name('api.v1.admin.qr-codes.generate-batch');
    Route::post('qr-codes/{qrCode}/link', [\App\Http\Controllers\Api\V1\Admin\QrCodeController::class, 'link'])
        ->name('api.v1.admin.qr-codes.link');

    // Loan Tiers
    Route::apiResource('loan-tiers', \App\Http\Controllers\Api\V1\Admin\LoanTierController::class)
        ->names('api.v1.admin.loan-tiers');

    // Roles
    Route::apiResource('roles', \App\Http\Controllers\Api\V1\Admin\RoleController::class)
        ->names('api.v1.admin.roles');

    // Permissions
    Route::apiResource('permissions', \App\Http\Controllers\Api\V1\Admin\PermissionController::class)
        ->names('api.v1.admin.permissions');
});
