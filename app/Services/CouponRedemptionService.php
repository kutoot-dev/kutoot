<?php

namespace App\Services;

use App\Events\CouponRedeemed;
use App\Models\CouponRedemption;
use App\Models\DiscountCoupon;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CouponRedemptionService
{
    /**
     * @param  array{original_bill_amount: float, discount_amount: float, platform_fee: float, gst_amount: float, total_paid: float}  $financials
     */
    public function redeemCoupon(User $user, DiscountCoupon $coupon, Transaction $transaction, array $financials): CouponRedemption
    {
        // 1. Check if active
        if (! $coupon->is_active) {
            throw ValidationException::withMessages(['coupon' => 'This coupon is no longer active.']);
        }

        // 2. Create redemption record with all financial details
        $redemption = CouponRedemption::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'transaction_id' => $transaction->id,
            'discount_applied' => $financials['discount_amount'],
            'original_bill_amount' => $financials['original_bill_amount'],
            'platform_fee' => $financials['platform_fee'],
            'gst_amount' => $financials['gst_amount'],
            'total_paid' => $financials['total_paid'],
        ]);

        CouponRedeemed::dispatch($redemption);

        return $redemption;
    }
}
