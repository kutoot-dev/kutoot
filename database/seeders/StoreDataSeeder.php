<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MerchantCategory;
use App\Models\Tag;
use App\Models\Sponsor;
use Illuminate\Support\Str;

class StoreDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Tags
        $tags = [
            'Dine-in', 'Takeaway', 'Delivery', 'Vegetarian', 'Vegan',
            'Fresh Produce', 'Daily Essentials',
            'Haircare', 'Skincare', 'Spa',
            'Coffee', 'Pastries', 'WiFi',
            'Mobiles', 'Laptops', 'Repair',
            'Men', 'Women', 'Kids',
            'Medicine', 'Health Supplements',
            'Gym', 'Yoga', 'Equipment'
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['name' => $tag]);
        }

        // 2. Seed Sponsors
        $sponsors = [
            ['name' => 'Coca-Cola', 'type' => 'Beverage Partner', 'serial' => 1],
            ['name' => 'Visa', 'type' => 'Payment Partner', 'serial' => 2],
            ['name' => 'Samsung', 'type' => 'Tech Partner', 'serial' => 3],
        ];

        foreach ($sponsors as $sponsorData) {
            Sponsor::firstOrCreate(
                ['name' => $sponsorData['name']],
                [
                    'type' => $sponsorData['type'],
                    'serial' => $sponsorData['serial'],
                    'is_active' => true,
                ]
            );
        }

        // 3. Seed Merchant Categories using the generated AI image for icons/images
        $categories = [
            ['name' => 'Restaurant', 'serial' => 1],
            ['name' => 'Grocery', 'serial' => 2],
            ['name' => 'Salon', 'serial' => 3],
            ['name' => 'Cafe', 'serial' => 4],
            ['name' => 'Electronics', 'serial' => 5],
            ['name' => 'Fashion', 'serial' => 6],
            ['name' => 'Pharmacy', 'serial' => 7],
            ['name' => 'Fitness', 'serial' => 8],
        ];

        // The AI generated a grid image. For this simple seeder, we will link the same grid image 
        // as a representative icon/image, which Filament will render.
        $imagePath = 'merchant-categories/icons_grid.png';

        foreach ($categories as $categoryData) {
            MerchantCategory::firstOrCreate(
                ['name' => $categoryData['name']],
                [
                    'serial' => $categoryData['serial'],
                    'image' => $imagePath,
                    'icon' => $imagePath,
                    'is_active' => true,
                ]
            );
        }
    }
}
