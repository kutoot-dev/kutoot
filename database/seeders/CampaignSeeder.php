<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Enums\CreatorType;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Stamp;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Seed campaigns with varied stamp configs, slot ranges, and plan access tiers.
     * Each campaign has realistic stamp configurations including editability flags.
     */
    public function run(): void
    {
        $admin = User::first();
        $plans = SubscriptionPlan::orderBy('price')->get();

        if ($plans->isEmpty() || ! $admin) {
            $this->command?->warn('Skipping CampaignSeeder: no plans or admin user found.');

            return;
        }

        $categories = CampaignCategory::all();
        if ($categories->isEmpty()) {
            $categories = collect([
                CampaignCategory::create(['name' => 'Food & Dining', 'slug' => 'food-dining']),
                CampaignCategory::create(['name' => 'Retail', 'slug' => 'retail']),
                CampaignCategory::create(['name' => 'Beverages', 'slug' => 'beverages']),
                CampaignCategory::create(['name' => 'Entertainment', 'slug' => 'entertainment']),
                CampaignCategory::create(['name' => 'Lifestyle', 'slug' => 'lifestyle']),
            ]);
        }

        /**
         * Campaign definitions with progressive difficulty and plan access tiers.
         *
         * @var array<int, array{
         *     reward_name: string,
         *     description: string,
         *     code: string,
         *     stamp_target: int,
         *     reward_cost_target: float,
         *     stamp_slots: int,
         *     stamp_slot_min: int,
         *     stamp_slot_max: int,
         *     stamp_editable_on_plan_purchase: bool,
         *     stamp_editable_on_coupon_redemption: bool,
         *     marketing_bounty_percentage: int,
         *     min_plan_index: int,
         *     category_index: int,
         * }>
         */
        $campaignDefinitions = [
            // --- Entry-level: accessible from Base/Bronze plans ---
            [
                'reward_name' => 'Free Samosa Box',
                'description' => 'Collect 5 stamps to win a box of 6 samosas! Simple and quick.',
                'code' => 'SAMOSA',
                'stamp_target' => 5,
                'reward_cost_target' => 150.00,
                'stamp_slots' => 3,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 9,
                'stamp_editable_on_plan_purchase' => false,
                'stamp_editable_on_coupon_redemption' => false,
                'marketing_bounty_percentage' => 20,
                'min_plan_index' => 0,
                'category_index' => 0,
            ],
            [
                'reward_name' => 'Free Chai Set',
                'description' => 'Earn 8 stamps for a premium chai set with biscuits.',
                'code' => 'CHAI',
                'stamp_target' => 8,
                'reward_cost_target' => 250.00,
                'stamp_slots' => 4,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 15,
                'stamp_editable_on_plan_purchase' => true,
                'stamp_editable_on_coupon_redemption' => false,
                'marketing_bounty_percentage' => 25,
                'min_plan_index' => 0,
                'category_index' => 2,
            ],
            // --- Mid-tier: Silver/Gold access ---
            [
                'reward_name' => 'Free Medium Pizza',
                'description' => 'Earn 10 stamps to win a delicious medium pizza of your choice.',
                'code' => 'PIZZA',
                'stamp_target' => 10,
                'reward_cost_target' => 400.00,
                'stamp_slots' => 5,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 25,
                'stamp_editable_on_plan_purchase' => true,
                'stamp_editable_on_coupon_redemption' => true,
                'marketing_bounty_percentage' => 22,
                'min_plan_index' => 1,
                'category_index' => 0,
            ],
            [
                'reward_name' => 'Movie Night Package',
                'description' => 'Collect 12 stamps for 2 movie tickets + popcorn combo.',
                'code' => 'MOVIE',
                'stamp_target' => 12,
                'reward_cost_target' => 600.00,
                'stamp_slots' => 5,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 30,
                'stamp_editable_on_plan_purchase' => true,
                'stamp_editable_on_coupon_redemption' => true,
                'marketing_bounty_percentage' => 30,
                'min_plan_index' => 1,
                'category_index' => 3,
            ],
            [
                'reward_name' => 'Shopping Voucher ₹500',
                'description' => 'Win a ₹500 shopping voucher at partner retail stores.',
                'code' => 'SHOP',
                'stamp_target' => 15,
                'reward_cost_target' => 500.00,
                'stamp_slots' => 5,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 35,
                'stamp_editable_on_plan_purchase' => false,
                'stamp_editable_on_coupon_redemption' => true,
                'marketing_bounty_percentage' => 18,
                'min_plan_index' => 2,
                'category_index' => 1,
            ],
            // --- Premium: Platinum/Diamond access ---
            [
                'reward_name' => 'Spa Day Experience',
                'description' => 'Earn 20 stamps for a full spa day including massage and facial.',
                'code' => 'SPADAY',
                'stamp_target' => 20,
                'reward_cost_target' => 2000.00,
                'stamp_slots' => 6,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 45,
                'stamp_editable_on_plan_purchase' => true,
                'stamp_editable_on_coupon_redemption' => true,
                'marketing_bounty_percentage' => 35,
                'min_plan_index' => 3,
                'category_index' => 4,
            ],
            [
                'reward_name' => 'Weekend Getaway',
                'description' => 'Collect 30 stamps to win a 2-night stay at a partner resort.',
                'code' => 'GETAWAY',
                'stamp_target' => 30,
                'reward_cost_target' => 5000.00,
                'stamp_slots' => 6,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 49,
                'stamp_editable_on_plan_purchase' => true,
                'stamp_editable_on_coupon_redemption' => true,
                'marketing_bounty_percentage' => 40,
                'min_plan_index' => 4,
                'category_index' => 4,
            ],
            // --- Cross-tier: accessible to multiple plans ---
            [
                'reward_name' => 'Coffee Month Pass',
                'description' => 'Earn 10 stamps for a month of free coffee at partner cafes.',
                'code' => 'COFMON',
                'stamp_target' => 10,
                'reward_cost_target' => 800.00,
                'stamp_slots' => 4,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 20,
                'stamp_editable_on_plan_purchase' => true,
                'stamp_editable_on_coupon_redemption' => false,
                'marketing_bounty_percentage' => 28,
                'min_plan_index' => 2,
                'category_index' => 2,
            ],
        ];

        foreach ($campaignDefinitions as $definition) {
            $categoryIndex = $definition['category_index'] % $categories->count();
            $category = $categories->values()->get($categoryIndex);

            $campaign = Campaign::create([
                'category_id' => $category->id,
                'creator_type' => CreatorType::Admin,
                'creator_id' => $admin->id,
                'reward_name' => $definition['reward_name'],
                'description' => $definition['description'],
                'code' => $definition['code'],
                'status' => CampaignStatus::Active,
                'start_date' => now()->subDays(rand(1, 60)),
                'reward_cost_target' => $definition['reward_cost_target'],
                'stamp_target' => $definition['stamp_target'],
                'stamp_slots' => $definition['stamp_slots'],
                'stamp_slot_min' => $definition['stamp_slot_min'],
                'stamp_slot_max' => $definition['stamp_slot_max'],
                'stamp_editable_on_plan_purchase' => $definition['stamp_editable_on_plan_purchase'],
                'stamp_editable_on_coupon_redemption' => $definition['stamp_editable_on_coupon_redemption'],
                'marketing_bounty_percentage' => $definition['marketing_bounty_percentage'],
                'collected_commission_cache' => 0,
                'issued_stamps_cache' => 0,
                'is_active' => true,
            ]);

            // Attach campaign to all plans from min_plan_index upward (progressive access)
            $eligiblePlanIds = $plans->slice($definition['min_plan_index'])->pluck('id');
            $campaign->plans()->attach($eligiblePlanIds);
        }

        $this->command?->info('Seeded '.count($campaignDefinitions).' campaigns with stamp configs and plan access.');
    }
}
