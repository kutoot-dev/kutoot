<?php

namespace Database\Seeders;

use App\Models\CouponRedemption;
use Illuminate\Database\Seeder;

class CouponRedemptionSeeder extends Seeder
{
    /**
     * Seed sample coupon redemptions.
     */
    public function run(): void
    {
        CouponRedemption::factory()->count(10)->create();
    }
}
