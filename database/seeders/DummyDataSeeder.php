<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Enums\CreatorType;
use App\Enums\DiscountType;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\CouponCategory;
use App\Models\DiscountCoupon;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create 5 Subscription Plans
        $bronzePlan = SubscriptionPlan::create([
            'name' => 'Bronze',
            'price' => 99,
            'stamps_on_purchase' => 2,
            'stamps_per_100' => 1,
            'max_discounted_bills' => 5,
            'max_redeemable_amount' => 500,
            'duration_days' => 30,
        ]);

        $silverPlan = SubscriptionPlan::create([
            'name' => 'Silver',
            'price' => 299,
            'stamps_on_purchase' => 5,
            'stamps_per_100' => 2,
            'max_discounted_bills' => 15,
            'max_redeemable_amount' => 1500,
            'duration_days' => 60,
        ]);

        $goldPlan = SubscriptionPlan::create([
            'name' => 'Gold',
            'price' => 599,
            'stamps_on_purchase' => 10,
            'stamps_per_100' => 3,
            'max_discounted_bills' => 30,
            'max_redeemable_amount' => 3000,
            'duration_days' => 90,
        ]);

        $platinumPlan = SubscriptionPlan::create([
            'name' => 'Platinum',
            'price' => 999,
            'stamps_on_purchase' => 15,
            'stamps_per_100' => 5,
            'max_discounted_bills' => 50,
            'max_redeemable_amount' => 5000,
            'duration_days' => 180,
        ]);

        $diamondPlan = SubscriptionPlan::create([
            'name' => 'Diamond',
            'price' => 1999,
            'stamps_on_purchase' => 25,
            'stamps_per_100' => 8,
            'max_discounted_bills' => 100,
            'max_redeemable_amount' => 10000,
            'duration_days' => 365,
        ]);

        $allPlans = [$bronzePlan, $silverPlan, $goldPlan, $platinumPlan, $diamondPlan];

        // 2. Create Users
        $user = User::factory()->create([
            'name' => 'John Banana',
            'email' => 'user@kutoot.com',
            'mobile' => '9000000002',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('User');

        // Subscribe user to Gold Plan
        UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $goldPlan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        // 3. Create Merchants
        $pizzaHut = Merchant::create([
            'name' => 'Pizza Hut',
            'slug' => 'pizza-hut',
            'logo' => 'https://placehold.co/400x400/e74c3c/ffffff?text=Pizza+Hut',
            'is_active' => true,
        ]);

        $bananaStore = Merchant::create([
            'name' => 'Nano Banana Store',
            'slug' => 'nano-banana-store',
            'logo' => 'https://placehold.co/400x400/f1c40f/000000?text=Nano+Banana',
            'is_active' => true,
        ]);

        $coffeeBrew = Merchant::create([
            'name' => 'Coffee Brew',
            'slug' => 'coffee-brew',
            'logo' => 'https://placehold.co/400x400/8B4513/ffffff?text=Coffee+Brew',
            'is_active' => true,
        ]);

        // 4. Create Locations
        $pizzaDowntown = MerchantLocation::create([
            'merchant_id' => $pizzaHut->id,
            'branch_name' => 'Downtown Plaza',
            'commission_percentage' => 5.0,
        ]);

        $bananaMall = MerchantLocation::create([
            'merchant_id' => $bananaStore->id,
            'branch_name' => 'Mega Mall',
            'commission_percentage' => 8.0,
        ]);

        $coffeeStation = MerchantLocation::create([
            'merchant_id' => $coffeeBrew->id,
            'branch_name' => 'Central Station',
            'commission_percentage' => 6.0,
        ]);

        // Assign Merchant Admin role to a new user for Pizza Hut
        $merchantUser = User::factory()->create([
            'name' => 'Manager Pizza',
            'email' => 'manager@pizzahut.com',
            'mobile' => '9000000003',
            'password' => Hash::make('password'),
        ]);
        $merchantUser->assignRole('Merchant Admin');
        $merchantUser->merchantLocations()->attach($pizzaDowntown);

        // 5. Create Categories
        $foodCat = CampaignCategory::firstOrCreate(['slug' => 'food-dining'], ['name' => 'Food & Dining']);
        $retailCat = CampaignCategory::firstOrCreate(['slug' => 'retail'], ['name' => 'Retail']);
        $beverageCat = CampaignCategory::firstOrCreate(['slug' => 'beverages'], ['name' => 'Beverages']);

        $couponCatFood = CouponCategory::create(['name' => 'Food', 'slug' => 'food', 'is_active' => true]);
        $couponCatBeverage = CouponCategory::create(['name' => 'Beverages', 'slug' => 'beverages', 'is_active' => true]);

        // 6. Create one Campaign per Plan
        $campaignData = [
            [$bronzePlan, 'Free Snack Box', $foodCat, 200.00, 5, $pizzaDowntown, 35],
            [$silverPlan, 'Free Medium Pizza', $foodCat, 400.00, 8, $pizzaDowntown, 22],
            [$goldPlan, 'Free Large Pizza', $foodCat, 500.00, 10, $pizzaDowntown, 15],
            [$platinumPlan, 'Free Banana Hamper', $retailCat, 800.00, 12, $bananaMall, 40],
            [$diamondPlan, 'Free Coffee Month Pass', $beverageCat, 1200.00, 15, $coffeeStation, 28],
        ];

        $goldCampaigns = [];
        foreach ($campaignData as [$plan, $rewardName, $category, $costTarget, $stampTarget, $location, $marketingBounty]) {
            $campaign = Campaign::create([
                'category_id' => $category->id,
                'creator_type' => CreatorType::Admin,
                'creator_id' => $merchantUser->id,
                'reward_name' => $rewardName,
                'description' => "Earn {$stampTarget} stamps to win: {$rewardName}",
                'status' => CampaignStatus::Active,
                'start_date' => now()->subDays(rand(1, 30)),
                'reward_cost_target' => $costTarget,
                'stamp_target' => $stampTarget,
                'collected_commission_cache' => 0,
                'issued_stamps_cache' => 0,
                'marketing_bounty_percentage' => $marketingBounty,
                'code' => strtoupper(substr(str_replace(' ', '', $rewardName), 0, 6)),
                'stamp_slots' => 4,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 20,
                'stamp_editable_on_plan_purchase' => true,
                'stamp_editable_on_coupon_redemption' => false,
            ]);
            $campaign->plans()->attach($plan);

            // Track campaigns accessible to Gold plan (Gold has access to Bronze, Silver, Gold campaigns)
            if (in_array($plan->id, [$bronzePlan->id, $silverPlan->id, $goldPlan->id])) {
                $goldCampaigns[] = $campaign;
            }
        }

        // 6b. Subscribe user to campaigns accessible under their Gold plan
        if (! empty($goldCampaigns)) {
            $firstCampaign = $goldCampaigns[0];
            $user->campaigns()->attach($firstCampaign->id, [
                'is_primary' => true,
                'subscribed_at' => now(),
            ]);
            $user->update(['primary_campaign_id' => $firstCampaign->id]);

            // Subscribe to remaining Gold-accessible campaigns (non-primary)
            foreach (array_slice($goldCampaigns, 1) as $campaign) {
                $user->campaigns()->attach($campaign->id, [
                    'is_primary' => false,
                    'subscribed_at' => now(),
                ]);
            }
        }

        // 7. Create Coupons
        $pizzaCoupon = DiscountCoupon::create([
            'coupon_category_id' => $couponCatFood->id,
            'merchant_location_id' => $pizzaDowntown->id,
            'title' => '50% Off Any Pizza',
            'description' => 'Get half price on your favorite pizza at Downtown Plaza.',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 50,
            'min_order_value' => 20,
            'max_discount_amount' => 50,
            'code' => 'PIZZA50',
            'usage_limit' => 100,
            'starts_at' => now(),
            'is_active' => true,
        ]);

        // Link coupon categories to plans (eligibility goes through categories)
        $couponCatFood->subscriptionPlans()->attach(collect($allPlans)->pluck('id'));
        $couponCatBeverage->subscriptionPlans()->attach([$goldPlan->id, $platinumPlan->id, $diamondPlan->id]);

        $bananaCoupon = DiscountCoupon::create([
            'coupon_category_id' => $couponCatFood->id,
            'merchant_location_id' => $bananaMall->id,
            'title' => 'Free Nano Banana',
            'description' => 'One free Nano Banana with every purchase over ₹10.',
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 5,
            'min_order_value' => 10,
            'code' => 'BANANA1',
            'starts_at' => now(),
            'is_active' => true,
        ]);

        $coffeeCoupon = DiscountCoupon::create([
            'coupon_category_id' => $couponCatBeverage->id,
            'merchant_location_id' => $coffeeStation->id,
            'title' => '30% Off Coffee',
            'description' => 'Get 30% off any coffee at Central Station.',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 30,
            'min_order_value' => 50,
            'max_discount_amount' => 100,
            'code' => 'COFFEE30',
            'usage_limit' => 200,
            'starts_at' => now(),
            'is_active' => true,
        ]);
    }
}
