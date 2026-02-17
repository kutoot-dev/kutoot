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
        // 1. Create Subscription Plans
        $goldPlan = SubscriptionPlan::create([
            'name' => 'Gold Member',
            'max_discounted_bills' => 50,
            'max_redeemable_amount' => 5000,
            'max_concurrent_campaigns_per_bill' => 3,
        ]);

        $silverPlan = SubscriptionPlan::create([
            'name' => 'Silver Member',
            'max_discounted_bills' => 10,
            'max_redeemable_amount' => 1000,
            'max_concurrent_campaigns_per_bill' => 1,
        ]);

        // 2. Create Users
        $user = User::factory()->create([
            'name' => 'John Banana',
            'email' => 'user@kutoot.com',
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

        // Assign Merchant Admin role to a new user for Pizza Hut
        $merchantUser = User::factory()->create([
            'name' => 'Manager Pizza',
            'email' => 'manager@pizzahut.com',
            'password' => Hash::make('password'),
        ]);
        $merchantUser->assignRole('Merchant Admin');
        $merchantUser->merchantLocations()->attach($pizzaDowntown);

        // 5. Create Categories
        $foodCat = CampaignCategory::create(['name' => 'Food & Dining', 'slug' => 'food-dining']);
        $retailCat = CampaignCategory::create(['name' => 'Retail', 'slug' => 'retail']);

        $couponCatFood = CouponCategory::create(['name' => 'Food', 'slug' => 'food', 'is_active' => true]);

        // 6. Create Campaigns
        $campaign = Campaign::create([
            'category_id' => $foodCat->id,
            'creator_type' => CreatorType::Admin,
            'creator_id' => $merchantUser->id,
            'reward_name' => 'Free Large Pizza',
            'status' => CampaignStatus::Active,
            'start_date' => now()->subDays(5),
            'reward_cost_target' => 500.00,
            'stamp_target' => 10,
            'collected_commission_cache' => 150.00,
            'issued_stamps_cache' => 3,
        ]);

        // Link campaign to Gold Plan
        $campaign->plans()->attach($goldPlan);

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

        $pizzaCoupon->plans()->attach($goldPlan); // Only for Gold members

        $bananaCoupon = DiscountCoupon::create([
            'coupon_category_id' => $couponCatFood->id,
            'merchant_location_id' => $bananaMall->id, // Nano Banana Store
            'title' => 'Free Nano Banana',
            'description' => 'One free Nano Banana with every purchase over $10.',
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 5, // Value of a banana?
            'min_order_value' => 10,
            'code' => 'BANANA1',
            'starts_at' => now(),
            'is_active' => true,
        ]);

        $bananaCoupon->plans()->attach([$goldPlan->id, $silverPlan->id]);
    }
}
