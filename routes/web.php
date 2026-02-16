<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/{campaign}', [\App\Http\Controllers\CampaignController::class, 'show'])->name('campaigns.show');
Route::get('/coupons', [\App\Http\Controllers\CouponController::class, 'index'])->name('coupons.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    }
    )->name('dashboard');

    Route::post('/coupons/{coupon}/redeem', [\App\Http\Controllers\CouponController::class, 'redeem'])->name('coupons.redeem');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
