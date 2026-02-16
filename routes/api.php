<?php

use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\MerchantLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/merchant-locations', [MerchantLocationController::class, 'index']);

    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{campaign}/bounty', [CampaignController::class, 'bounty']);

    Route::get('/coupons', [CouponController::class, 'index']);
    Route::post('/coupons/{coupon}/redeem', [CouponController::class, 'redeem']);
});
