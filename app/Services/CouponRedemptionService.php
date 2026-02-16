<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Events\CouponRedeemed;
use App\Models\CouponRedemption;
use App\Models\DiscountCoupon;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CouponRedemptionService
{
    public function redeemCoupon(User $user, DiscountCoupon $coupon, Transaction $transaction): CouponRedemption
    {
        // 1. Check if active
        if (! $coupon->is_active) {
            throw ValidationException::withMessages(['coupon' => 'This coupon is no longer active.']);
        }

        // 2. Validate user owns/can use (optional logic here)

        // 3. Calculate discount amount
        $discountAmount = 0.0;
        if ($coupon->discount_type === DiscountType::Fixed) {
            $discountAmount = $coupon->discount_value;
        } else {
            $discountAmount = ($transaction->amount * $coupon->discount_value) / 100;
        }

        // 4. Create redemption record
        $redemption = CouponRedemption::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'transaction_id' => $transaction->id,
            'discount_applied' => $discountAmount,
        ]);

        CouponRedeemed::dispatch($redemption);

        return $redemption;
    }
}
