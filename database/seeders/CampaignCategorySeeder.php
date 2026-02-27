<?php

namespace Database\Seeders;

use App\Models\CampaignCategory;
use Illuminate\Database\Seeder;

class CampaignCategorySeeder extends Seeder
{
    /**
     * Seed campaign categories with realistic names.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Food & Dining', 'slug' => 'food-dining', 'icon' => 'heroicon-o-cake'],
            ['name' => 'Retail', 'slug' => 'retail', 'icon' => 'heroicon-o-shopping-bag'],
            ['name' => 'Beverages', 'slug' => 'beverages', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Entertainment', 'slug' => 'entertainment', 'icon' => 'heroicon-o-film'],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'icon' => 'heroicon-o-heart'],
        ];

        foreach ($categories as $category) {
            CampaignCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
