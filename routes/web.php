<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/debug-error', function () {
    abort(500, 'This is a test error to show the error page.');
});

Route::get('/', [\App\Http\Controllers\CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/{campaign}', [\App\Http\Controllers\CampaignController::class, 'show'])->name('campaigns.show');
Route::get('/coupons', [\App\Http\Controllers\CouponController::class, 'index'])->name('coupons.index');
Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscriptions.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/stamps', [\App\Http\Controllers\StampController::class, 'index'])->name('stamps.index');
    Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');

    Route::post('/coupons/{coupon}/redeem', [\App\Http\Controllers\CouponController::class, 'redeem'])->name('coupons.redeem');
    Route::post('/coupons/transactions/{transaction}/verify', [\App\Http\Controllers\CouponController::class, 'verifyPayment'])->name('coupons.verify-payment');

    // QR Codes
    Route::get('/executive/qr', [\App\Http\Controllers\ExecutiveQrController::class, 'index'])->name('executive.qr.index');
    Route::get('/executive/qr/{qrCode}/download', [\App\Http\Controllers\ExecutiveQrController::class, 'download'])->name('executive.qr.download');
    Route::post('/executive/qr/link', [\App\Http\Controllers\ExecutiveQrController::class, 'link'])->name('executive.qr.link');
    Route::post('/admin/qr/generate', [\App\Http\Controllers\ExecutiveQrController::class, 'generateBatch'])->name('admin.qr.generate');

    Route::get('/q/{token}', [\App\Http\Controllers\QrScanController::class, 'scan'])->name('qr.scan');

    // Subscriptions
    Route::post('/subscriptions/upgrade', [\App\Http\Controllers\SubscriptionController::class, 'upgrade'])->name('subscriptions.upgrade');
    Route::post('/subscriptions/verify-payment/{transaction}', [\App\Http\Controllers\SubscriptionController::class, 'verifyPlanPayment'])->name('subscriptions.verify-payment');
    Route::post('/subscriptions/primary-campaign', [\App\Http\Controllers\SubscriptionController::class, 'setPrimaryCampaign'])->name('subscriptions.setPrimaryCampaign');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/primary-campaign', [ProfileController::class, 'updatePrimaryCampaign'])->name('profile.update-primary-campaign');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
