<?php

namespace Database\Seeders;

use App\Models\CouponCategory;
use Illuminate\Database\Seeder;

class CouponCategorySeeder extends Seeder
{
    /**
     * Seed coupon categories with realistic names.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Food', 'slug' => 'food', 'is_active' => true],
            ['name' => 'Beverages', 'slug' => 'beverages', 'is_active' => true],
            ['name' => 'Shopping', 'slug' => 'shopping', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            CouponCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
