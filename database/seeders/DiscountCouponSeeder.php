<?php

namespace Database\Seeders;

use App\Models\DiscountCoupon;
use Illuminate\Database\Seeder;

class DiscountCouponSeeder extends Seeder
{
    /**
     * Seed sample discount coupons.
     */
    public function run(): void
    {
        DiscountCoupon::factory()->count(10)->create();
    }
}
