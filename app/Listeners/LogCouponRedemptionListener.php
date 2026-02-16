<?php

namespace App\Listeners;

use App\Events\CouponRedeemed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogCouponRedemptionListener implements ShouldQueue
{
    public function handle(CouponRedeemed $event): void
    {
        Log::info('Coupon redeemed', [
            'coupon_id' => $event->redemption->coupon_id,
            'user_id' => $event->redemption->user_id,
            'transaction_id' => $event->redemption->transaction_id,
            'discount_applied' => $event->redemption->discount_applied,
        ]);
    }
}
