<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Enums\CreatorType;
use App\Enums\DiscountType;
use App\Enums\LoanStatus;
use App\Enums\MerchantApplicationStatus;
use App\Enums\PaymentStatus;
use App\Enums\QrCodeStatus;
use App\Enums\StampSource;
use App\Enums\StampStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TargetType;
use App\Enums\TransactionType;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\CouponCategory;
use App\Models\CouponRedemption;
use App\Models\DiscountCoupon;
use App\Models\FeaturedBanner;
use App\Models\LoanTier;
use App\Models\MarketingBanner;
use App\Models\Merchant;
use App\Models\MerchantApplication;
use App\Models\MerchantCategory;
use App\Models\MerchantLocation;
use App\Models\MerchantLocationLoan;
use App\Models\MerchantLocationMonthlySummary;
use App\Models\MerchantNotificationSetting;
use App\Models\NewsArticle;
use App\Models\QrCode;
use App\Models\Sponsor;
use App\Models\Stamp;
use App\Models\StoreBanner;
use App\Models\SubscriptionPlan;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComprehensiveSeeder extends Seeder
{
    /**
     * Comprehensive seeder that populates ALL tables with realistic Indian data.
     *
     * Plans: Free (₹0) → Basic (₹19) → Pro (₹39) → VIP (₹59) → Elite (₹79) → All Access (₹99)
     * Campaigns: iPhone, Jewellery, BMW Bike, Tata Sierra Car, Villa
     * Exclusive campaign access: each plan unlocks its own specific campaign(s).
     *
     * Free → iPhone | Basic → Jewellery | Pro → BMW Bike (Most Popular)
     * VIP → Tata Sierra Car | Elite → Villa | All Access → Bike + Car + Villa
     */
    public function run(): void
    {
        $admin = User::where('email', 'it@kutoot.com')->first() ?? User::first();

        if (! $admin) {
            $this->command?->error('No admin user found. Run SuperAdminSeeder first.');

            return;
        }

        // ───────────────────────────────────────────────
        // 1. Campaign Categories
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding campaign categories...');

        $campaignCategories = [];
        foreach ([
            ['name' => 'Automotive', 'slug' => 'automotive', '🚗'],
            ['name' => 'Electronics', 'slug' => 'electronics', '📱'],
            ['name' => 'Real Estate', 'slug' => 'real-estate', '🏠'],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle', '💎'],
            ['name' => 'Food & Dining', 'slug' => 'food-dining', '🍕'],
        ] as $cat) {
            $campaignCategories[$cat['slug']] = CampaignCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                $cat,
            );
        }

        // ───────────────────────────────────────────────
        // 2. Coupon Categories
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding coupon categories...');

        $couponCategories = [];
        foreach ([
            ['name' => 'Food', 'slug' => 'food'],
            ['name' => 'Beverages', 'slug' => 'beverages'],
            ['name' => 'Shopping', 'slug' => 'shopping'],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle'],
        ] as $cat) {
            $couponCategories[$cat['slug']] = CouponCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['is_active' => true]),
            );
        }

        // ───────────────────────────────────────────────
        // 3. Merchant Categories
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding merchant categories...');


        $merchantCategories = [];
        foreach ([
            ['name' => 'Restaurant', 'serial' => 1],
            ['name' => 'Grocery', 'serial' => 2],
            ['name' => 'Salon', 'serial' => 3],
            ['name' => 'Cafe', 'serial' => 4],
            ['name' => 'Electronics', 'serial' => 5],
            ['name' => 'Fashion', 'serial' => 6],
            ['name' => 'Pharmacy', 'serial' => 7],
            ['name' => 'Fitness', 'serial' => 8],
        ] as $cat) {
            $merchantCategories[$cat['name']] = MerchantCategory::firstOrCreate(
                ['name' => $cat['name']],
                [
                    'serial' => $cat['serial'],


                    'is_active' => true,
                ],
            );
        }

        // ───────────────────────────────────────────────
        // 4. Tags
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding tags...');

        $tagNames = [
            'Dine-in', 'Takeaway', 'Delivery', 'Vegetarian', 'Vegan',
            'Fresh Produce', 'Daily Essentials',
            'Haircare', 'Skincare', 'Spa',
            'Coffee', 'Pastries', 'WiFi',
            'Mobiles', 'Laptops', 'Repair',
            'Men', 'Women', 'Kids',
            'Medicine', 'Health Supplements',
            'Gym', 'Yoga', 'Equipment',
        ];

        $tags = [];
        foreach ($tagNames as $name) {
            $tags[$name] = Tag::firstOrCreate(['name' => $name]);
        }

        // ───────────────────────────────────────────────
        // 5. Sponsors
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding sponsors...');

        foreach ([
            ['name' => 'Coca-Cola', 'type' => 'Beverage Partner', 'serial' => 1, 'link' => 'https://www.coca-cola.com'],
            ['name' => 'Visa', 'type' => 'Payment Partner', 'serial' => 2, 'link' => 'https://www.visa.com'],
            ['name' => 'Samsung', 'type' => 'Tech Partner', 'serial' => 3, 'link' => 'https://www.samsung.com'],
            ['name' => 'Zomato', 'type' => 'Delivery Partner', 'serial' => 4, 'link' => 'https://www.zomato.com'],
            ['name' => 'Paytm', 'type' => 'Fintech Partner', 'serial' => 5, 'link' => 'https://www.paytm.com'],
        ] as $s) {
            Sponsor::firstOrCreate(
                ['name' => $s['name']],
                [
                    'type' => $s['type'],
                    'serial' => $s['serial'],
                    'link' => $s['link'],
                    'is_active' => true,
                ],
            );
        }

        // ───────────────────────────────────────────────
        // 6. Campaigns (5 real campaigns)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding campaigns...');

        $campaignDefs = [
            'iphone' => [
                'reward_name' => 'iPhone',
                'description' => 'Win the latest Apple iPhone — power, elegance, and innovation in your hands.',
                'code' => 'IPHONE',
                'category' => 'electronics',
                'stamp_target' => 20,
                'reward_cost_target' => 120000.00,
                'stamp_slots' => 5,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 30,
                'marketing_bounty_percentage' => 25,
                'is_premium' => false,
            ],
            'jewellery' => [
                'reward_name' => 'Jewellery',
                'description' => 'Win exquisite gold and diamond jewellery — timeless beauty, crafted to perfection.',
                'code' => 'JEWEL',
                'category' => 'lifestyle',
                'stamp_target' => 25,
                'reward_cost_target' => 200000.00,
                'stamp_slots' => 5,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 40,
                'marketing_bounty_percentage' => 30,
                'is_premium' => false,
            ],
            'bmw_bike' => [
                'reward_name' => 'BMW Bike',
                'description' => 'Win a BMW motorcycle — precision engineering and pure riding thrill.',
                'code' => 'BMWBIK',
                'category' => 'automotive',
                'stamp_target' => 35,
                'reward_cost_target' => 500000.00,
                'stamp_slots' => 6,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 45,
                'marketing_bounty_percentage' => 35,
                'is_premium' => true,
            ],
            'tata_sierra' => [
                'reward_name' => 'Tata Sierra Car',
                'description' => 'Win the all-new Tata Sierra SUV — rugged, stylish, and built for adventure.',
                'code' => 'SIERRA',
                'category' => 'automotive',
                'stamp_target' => 50,
                'reward_cost_target' => 1500000.00,
                'stamp_slots' => 6,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 50,
                'marketing_bounty_percentage' => 40,
                'is_premium' => true,
            ],
            'villa' => [
                'reward_name' => 'Villa',
                'description' => 'Win a luxury villa — your dream home awaits with premium interiors and stunning views.',
                'code' => 'VILLA',
                'category' => 'real-estate',
                'stamp_target' => 100,
                'reward_cost_target' => 5000000.00,
                'stamp_slots' => 6,
                'stamp_slot_min' => 1,
                'stamp_slot_max' => 99,
                'marketing_bounty_percentage' => 45,
                'is_premium' => true,
            ],
        ];

        $campaigns = [];
        foreach ($campaignDefs as $key => $def) {
            $campaigns[$key] = Campaign::updateOrCreate(
                ['code' => $def['code']],
                [
                    'category_id' => $campaignCategories[$def['category']]->id,
                    'creator_type' => CreatorType::Admin,
                    'creator_id' => $admin->id,
                    'reward_name' => $def['reward_name'],
                    'description' => $def['description'],
                    'status' => CampaignStatus::Active,
                    'start_date' => now()->subDays(rand(5, 30)),
                    'reward_cost_target' => $def['reward_cost_target'],
                    'stamp_target' => $def['stamp_target'],
                    'stamp_slots' => $def['stamp_slots'],
                    'stamp_slot_min' => $def['stamp_slot_min'],
                    'stamp_slot_max' => $def['stamp_slot_max'],
                    'stamp_editable_on_plan_purchase' => true,
                    'stamp_editable_on_coupon_redemption' => false,
                    'marketing_bounty_percentage' => $def['marketing_bounty_percentage'],
                    'collected_commission_cache' => 0,
                    'issued_stamps_cache' => 0,
                    'is_active' => true,
                    'is_premium' => $def['is_premium'],
                ],
            );
        }

        // ───────────────────────────────────────────────
        // 7. Subscription Plans (6 plans, EXCLUSIVE campaign access)
        //    Free → iPhone | Basic → Jewellery | Pro → BMW Bike
        //    VIP → Tata Sierra | Elite → Villa | All Access → Bike+Car+Villa
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding subscription plans...');

        $planDefs = [
            [
                'name' => 'Free',
                'price' => 0,
                'sort_order' => 1,
                'stamps_on_purchase' => 0,
                'stamp_denomination' => 100,
                'stamps_per_denomination' => 1,
                'max_discounted_bills' => 3,
                'max_redeemable_amount' => 300,
                'duration_days' => null,
                'is_default' => true,
                'best_value' => false,
                'campaign_keys' => ['iphone'],
            ],
            [
                'name' => 'Basic',
                'price' => 19,
                'sort_order' => 2,
                'stamps_on_purchase' => 2,
                'stamp_denomination' => 100,
                'stamps_per_denomination' => 1,
                'max_discounted_bills' => 5,
                'max_redeemable_amount' => 500,
                'duration_days' => 30,
                'is_default' => false,
                'best_value' => false,
                'campaign_keys' => ['jewellery'],
            ],
            [
                'name' => 'Pro',
                'price' => 39,
                'sort_order' => 3,
                'stamps_on_purchase' => 5,
                'stamp_denomination' => 50,
                'stamps_per_denomination' => 2,
                'max_discounted_bills' => 15,
                'max_redeemable_amount' => 1500,
                'duration_days' => 60,
                'is_default' => false,
                'best_value' => true,  // "Most Popular"
                'campaign_keys' => ['bmw_bike'],
            ],
            [
                'name' => 'VIP',
                'price' => 59,
                'sort_order' => 4,
                'stamps_on_purchase' => 10,
                'stamp_denomination' => 25,
                'stamps_per_denomination' => 3,
                'max_discounted_bills' => 30,
                'max_redeemable_amount' => 3000,
                'duration_days' => 90,
                'is_default' => false,
                'best_value' => false,
                'campaign_keys' => ['tata_sierra'],
            ],
            [
                'name' => 'Elite',
                'price' => 79,
                'sort_order' => 5,
                'stamps_on_purchase' => 15,
                'stamp_denomination' => 15,
                'stamps_per_denomination' => 5,
                'max_discounted_bills' => 50,
                'max_redeemable_amount' => 5000,
                'duration_days' => 180,
                'is_default' => false,
                'best_value' => false,
                'campaign_keys' => ['villa'],
            ],
            [
                'name' => 'All Access',
                'price' => 99,
                'sort_order' => 6,
                'stamps_on_purchase' => 25,
                'stamp_denomination' => 10,
                'stamps_per_denomination' => 8,
                'max_discounted_bills' => 100,
                'max_redeemable_amount' => 10000,
                'duration_days' => 365,
                'is_default' => false,
                'best_value' => false,
                'campaign_keys' => ['bmw_bike', 'tata_sierra', 'villa'],
            ],
        ];

        $plans = [];
        foreach ($planDefs as $def) {
            $campaignKeys = $def['campaign_keys'];
            unset($def['campaign_keys']);

            $plan = SubscriptionPlan::updateOrCreate(
                ['name' => $def['name']],
                $def,
            );

            // Exclusive campaign access per plan
            $campaignIds = collect($campaignKeys)
                ->map(fn (string $key): int => $campaigns[$key]->id)
                ->all();
            $plan->campaigns()->sync($campaignIds);

            // All coupon categories accessible to every plan
            $plan->couponCategories()->sync(
                collect($couponCategories)->pluck('id')->all(),
            );

            $plans[$def['name']] = $plan;
        }

        // ───────────────────────────────────────────────
        // 8. Merchants (6 realistic Indian businesses)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding merchants...');

        $merchantDefs = [
            ['name' => 'Taj Darbar', 'slug' => 'taj-darbar', 'category' => 'Restaurant'],
            ['name' => 'Reliance Fresh', 'slug' => 'reliance-fresh', 'category' => 'Grocery'],
            ['name' => 'Lakme Salon', 'slug' => 'lakme-salon', 'category' => 'Salon'],
            ['name' => 'Chai Point', 'slug' => 'chai-point', 'category' => 'Cafe'],
            ['name' => 'Croma Electronics', 'slug' => 'croma-electronics', 'category' => 'Electronics'],
            ['name' => 'Fabindia', 'slug' => 'fabindia', 'category' => 'Fashion'],
        ];

        // Use the kutoot logo as merchant logo (from public/images)
        $logoPath = public_path('images/kutoot-full-logo.png');

        $merchants = [];
        foreach ($merchantDefs as $def) {
            $merchant = Merchant::updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'is_active' => true,
                ],
            );

            // Attach logo from public/images folder
            if (file_exists($logoPath) && $merchant->getFirstMedia('logo') === null) {
                $merchant->addMedia($logoPath)
                    ->preservingOriginal()
                    ->toMediaCollection('logo');
            }

            $merchants[$def['slug']] = $merchant;
        }

        // ───────────────────────────────────────────────
        // 9. Merchant Locations (2 per merchant = 12 locations)
        //    Using real Indian state/city IDs from nnjeim/world
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding merchant locations...');

        // State IDs (from nnjeim/world, India = 102)
        // Maharashtra=1660, Karnataka=1655, Delhi=1648, Tamil Nadu=1670, Telangana=1671, Gujarat=1650
        // City IDs: Mumbai=47064, Bengaluru=46125, New Delhi=45452, Chennai=47826, Hyderabad=48157, Ahmedabad=45514, Thane=47246, Pune(Hadapsar)=46901

        $locationDefs = [
            // Taj Darbar
            ['merchant' => 'taj-darbar', 'branch' => 'Connaught Place', 'commission' => 7.5, 'category' => 'Restaurant',
                'state_id' => 1648, 'city_id' => 45452, 'address' => 'Block A, Connaught Place, New Delhi 110001',
                'target_type' => TargetType::Amount, 'target_value' => 500000],
            ['merchant' => 'taj-darbar', 'branch' => 'Bandra West', 'commission' => 7.0, 'category' => 'Restaurant',
                'state_id' => 1660, 'city_id' => 47064, 'address' => 'Hill Road, Bandra West, Mumbai 400050',
                'target_type' => TargetType::TransactionCount, 'target_value' => 200],
            // Reliance Fresh
            ['merchant' => 'reliance-fresh', 'branch' => 'MG Road', 'commission' => 5.0, 'category' => 'Grocery',
                'state_id' => 1655, 'city_id' => 46125, 'address' => '45 MG Road, Bengaluru 560001',
                'target_type' => TargetType::Amount, 'target_value' => 300000],
            ['merchant' => 'reliance-fresh', 'branch' => 'Anna Nagar', 'commission' => 5.5, 'category' => 'Grocery',
                'state_id' => 1670, 'city_id' => 47826, 'address' => '12 Anna Nagar Main Road, Chennai 600040',
                'target_type' => null, 'target_value' => null],
            // Lakme Salon
            ['merchant' => 'lakme-salon', 'branch' => 'Koramangala', 'commission' => 10.0, 'category' => 'Salon',
                'state_id' => 1655, 'city_id' => 46125, 'address' => '5th Block, Koramangala, Bengaluru 560095',
                'target_type' => TargetType::Amount, 'target_value' => 200000],
            ['merchant' => 'lakme-salon', 'branch' => 'Jubilee Hills', 'commission' => 9.5, 'category' => 'Salon',
                'state_id' => 1671, 'city_id' => 48157, 'address' => 'Road No 36, Jubilee Hills, Hyderabad 500033',
                'target_type' => null, 'target_value' => null],
            // Chai Point
            ['merchant' => 'chai-point', 'branch' => 'Indiranagar', 'commission' => 8.0, 'category' => 'Cafe',
                'state_id' => 1655, 'city_id' => 46125, 'address' => '100 Feet Road, Indiranagar, Bengaluru 560038',
                'target_type' => TargetType::TransactionCount, 'target_value' => 500],
            ['merchant' => 'chai-point', 'branch' => 'CG Road', 'commission' => 7.5, 'category' => 'Cafe',
                'state_id' => 1650, 'city_id' => 45514, 'address' => 'CG Road, Navrangpura, Ahmedabad 380009',
                'target_type' => null, 'target_value' => null],
            // Croma Electronics
            ['merchant' => 'croma-electronics', 'branch' => 'Phoenix Mall', 'commission' => 6.0, 'category' => 'Electronics',
                'state_id' => 1660, 'city_id' => 47064, 'address' => 'Phoenix Marketcity, Kurla West, Mumbai 400070',
                'target_type' => TargetType::Amount, 'target_value' => 1000000],
            ['merchant' => 'croma-electronics', 'branch' => 'T Nagar', 'commission' => 6.5, 'category' => 'Electronics',
                'state_id' => 1670, 'city_id' => 47826, 'address' => 'Usman Road, T Nagar, Chennai 600017',
                'target_type' => null, 'target_value' => null],
            // Fabindia
            ['merchant' => 'fabindia', 'branch' => 'Khan Market', 'commission' => 8.5, 'category' => 'Fashion',
                'state_id' => 1648, 'city_id' => 45452, 'address' => '14 Khan Market, New Delhi 110003',
                'target_type' => TargetType::Amount, 'target_value' => 400000],
            ['merchant' => 'fabindia', 'branch' => 'Thane West', 'commission' => 8.0, 'category' => 'Fashion',
                'state_id' => 1660, 'city_id' => 47246, 'address' => 'Viviana Mall, Thane West, Mumbai 400606',
                'target_type' => null, 'target_value' => null],
        ];

        $locations = [];
        foreach ($locationDefs as $def) {
            $loc = MerchantLocation::updateOrCreate(
                [
                    'merchant_id' => $merchants[$def['merchant']]->id,
                    'branch_name' => $def['branch'],
                ],
                [
                    'merchant_category_id' => $merchantCategories[$def['category']]->id,
                    'commission_percentage' => $def['commission'],
                    'star_rating' => round(rand(35, 50) / 10, 1),
                    'is_active' => true,
                    'monthly_target_type' => $def['target_type'],
                    'monthly_target_value' => $def['target_value'],
                    'state_id' => $def['state_id'],
                    'city_id' => $def['city_id'],
                    'address' => $def['address'],
                    'gst_number' => '29AABCU' . rand(1000, 9999) . 'D1Z' . rand(1, 9),
                    'pan_number' => 'AABCU' . rand(1000, 9999) . chr(rand(65, 90)),
                ],
            );

            $locations[$def['merchant'] . '-' . Str::slug($def['branch'])] = $loc;
        }

        // Attach random tags to each location
        $tagIds = collect($tags)->pluck('id')->all();
        foreach ($locations as $location) {
            $randomTagIds = collect($tagIds)->random(rand(3, 6))->all();
            $location->tags()->syncWithoutDetaching($randomTagIds);
        }

        // ───────────────────────────────────────────────
        // 10. Seller / Merchant Admin Users (3 sellers)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding seller users...');

        $sellerDefs = [
            [
                'name' => 'Raj Kumar',
                'email' => 'seller1@kutoot.com',
                'username' => 'rajkumar',
                'mobile' => '9100000001',
                'location_key' => 'taj-darbar-connaught-place',
            ],
            [
                'name' => 'Priya Sharma',
                'email' => 'seller2@kutoot.com',
                'username' => 'priyasharma',
                'mobile' => '9100000002',
                'location_key' => 'reliance-fresh-mg-road',
            ],
            [
                'name' => 'Anita Desai',
                'email' => 'seller3@kutoot.com',
                'username' => 'anitadesai',
                'mobile' => '9100000003',
                'location_key' => 'lakme-salon-koramangala',
            ],
        ];

        $sellers = [];
        foreach ($sellerDefs as $def) {
            $seller = User::updateOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'username' => $def['username'],
                    'mobile' => $def['mobile'],
                    'password' => Hash::make('password'),
                    'country_id' => 102,
                ],
            );
            $seller->assignRole('Merchant Admin');

            // Attach to merchant location
            $location = $locations[$def['location_key']];
            $seller->merchantLocations()->syncWithoutDetaching([
                $location->id => ['role' => 'owner'],
            ]);

            $sellers[] = $seller;
        }

        // ───────────────────────────────────────────────
        // 11. Regular Users (5 test customers)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding customer users...');

        $userDefs = [
            ['name' => 'Amit Patel', 'email' => 'user1@kutoot.com', 'username' => 'amitpatel', 'mobile' => '9200000001', 'plan' => 'Free', 'gender' => 'male'],
            ['name' => 'Sneha Iyer', 'email' => 'user2@kutoot.com', 'username' => 'snehaiyer', 'mobile' => '9200000002', 'plan' => 'Basic', 'gender' => 'female'],
            ['name' => 'Vikram Singh', 'email' => 'user3@kutoot.com', 'username' => 'vikramsingh', 'mobile' => '9200000003', 'plan' => 'Pro', 'gender' => 'male'],
            ['name' => 'Deepa Nair', 'email' => 'user4@kutoot.com', 'username' => 'deepanair', 'mobile' => '9200000004', 'plan' => 'VIP', 'gender' => 'female'],
            ['name' => 'Karthik Reddy', 'email' => 'user5@kutoot.com', 'username' => 'karthikreddy', 'mobile' => '9200000005', 'plan' => 'Elite', 'gender' => 'male'],
        ];

        $users = [];
        foreach ($userDefs as $def) {
            $user = User::updateOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'username' => $def['username'],
                    'mobile' => $def['mobile'],
                    'password' => Hash::make('password'),
                    'gender' => $def['gender'],
                    'country_id' => 102,
                ],
            );
            $user->assignRole('User');

            // Create subscription
            $plan = $plans[$def['plan']];
            UserSubscription::updateOrCreate(
                ['user_id' => $user->id, 'plan_id' => $plan->id],
                [
                    'status' => SubscriptionStatus::Active,
                    'expires_at' => $plan->duration_days ? now()->addDays($plan->duration_days) : null,
                ],
            );

            // Subscribe to campaigns accessible by their plan
            $planCampaignIds = $plan->campaigns()->pluck('campaigns.id')->all();
            if (! empty($planCampaignIds)) {
                $firstCampaign = true;
                foreach ($planCampaignIds as $campaignId) {
                    $user->campaigns()->syncWithoutDetaching([
                        $campaignId => [
                            'is_primary' => $firstCampaign,
                            'subscribed_at' => now(),
                        ],
                    ]);
                    if ($firstCampaign) {
                        $user->update(['primary_campaign_id' => $campaignId]);
                        $firstCampaign = false;
                    }
                }
            }

            $users[$def['email']] = $user;
        }

        // ───────────────────────────────────────────────
        // 12. Discount Coupons (15 coupons across locations)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding discount coupons...');

        $couponDefs = [
            // Taj Darbar coupons
            ['category' => 'food', 'location' => 'taj-darbar-connaught-place', 'title' => '20% Off Biryani', 'code' => 'BIRYANI20',
                'type' => DiscountType::Percentage, 'value' => 20, 'min' => 200, 'max_discount' => 100, 'limit' => 500],
            ['category' => 'food', 'location' => 'taj-darbar-bandra-west', 'title' => 'Flat ₹150 Off', 'code' => 'TAJFLAT150',
                'type' => DiscountType::Fixed, 'value' => 150, 'min' => 500, 'max_discount' => null, 'limit' => 200],
            // Reliance Fresh coupons
            ['category' => 'shopping', 'location' => 'reliance-fresh-mg-road', 'title' => '10% Off Groceries', 'code' => 'FRESH10',
                'type' => DiscountType::Percentage, 'value' => 10, 'min' => 300, 'max_discount' => 200, 'limit' => 1000],
            ['category' => 'shopping', 'location' => 'reliance-fresh-anna-nagar', 'title' => 'Flat ₹50 Off', 'code' => 'FRESHFLAT50',
                'type' => DiscountType::Fixed, 'value' => 50, 'min' => 200, 'max_discount' => null, 'limit' => null],
            // Lakme Salon coupons
            ['category' => 'lifestyle', 'location' => 'lakme-salon-koramangala', 'title' => '30% Off Hair Spa', 'code' => 'HAIRSPA30',
                'type' => DiscountType::Percentage, 'value' => 30, 'min' => 500, 'max_discount' => 300, 'limit' => 100],
            ['category' => 'lifestyle', 'location' => 'lakme-salon-jubilee-hills', 'title' => 'Flat ₹200 Off Facial', 'code' => 'FACIAL200',
                'type' => DiscountType::Fixed, 'value' => 200, 'min' => 800, 'max_discount' => null, 'limit' => 150],
            // Chai Point coupons
            ['category' => 'beverages', 'location' => 'chai-point-indiranagar', 'title' => '25% Off Chai Combo', 'code' => 'CHAI25',
                'type' => DiscountType::Percentage, 'value' => 25, 'min' => 100, 'max_discount' => 75, 'limit' => 300],
            ['category' => 'beverages', 'location' => 'chai-point-cg-road', 'title' => 'Free Cookie with Chai', 'code' => 'CHAIFREE',
                'type' => DiscountType::Fixed, 'value' => 30, 'min' => 50, 'max_discount' => null, 'limit' => null],
            // Croma coupons
            ['category' => 'shopping', 'location' => 'croma-electronics-phoenix-mall', 'title' => '15% Off Headphones', 'code' => 'HEADSET15',
                'type' => DiscountType::Percentage, 'value' => 15, 'min' => 1000, 'max_discount' => 500, 'limit' => 50],
            ['category' => 'shopping', 'location' => 'croma-electronics-t-nagar', 'title' => 'Flat ₹500 Off Laptops', 'code' => 'LAPTOP500',
                'type' => DiscountType::Fixed, 'value' => 500, 'min' => 25000, 'max_discount' => null, 'limit' => 20],
            // Fabindia coupons
            ['category' => 'shopping', 'location' => 'fabindia-khan-market', 'title' => '20% Off Kurti Collection', 'code' => 'KURTI20',
                'type' => DiscountType::Percentage, 'value' => 20, 'min' => 800, 'max_discount' => 400, 'limit' => 200],
            ['category' => 'lifestyle', 'location' => 'fabindia-thane-west', 'title' => 'Flat ₹300 Off', 'code' => 'FABFLAT300',
                'type' => DiscountType::Fixed, 'value' => 300, 'min' => 1500, 'max_discount' => null, 'limit' => 100],
            // Cross-store coupons
            ['category' => 'food', 'location' => 'taj-darbar-connaught-place', 'title' => 'Weekend Special 40% Off', 'code' => 'WEEKEND40',
                'type' => DiscountType::Percentage, 'value' => 40, 'min' => 400, 'max_discount' => 250, 'limit' => 50],
            ['category' => 'beverages', 'location' => 'chai-point-indiranagar', 'title' => 'Buy 1 Get 1 Free', 'code' => 'CHAIBOGO',
                'type' => DiscountType::Fixed, 'value' => 80, 'min' => 80, 'max_discount' => null, 'limit' => 100],
            ['category' => 'shopping', 'location' => 'reliance-fresh-mg-road', 'title' => 'New User ₹100 Off', 'code' => 'NEWUSER100',
                'type' => DiscountType::Fixed, 'value' => 100, 'min' => 500, 'max_discount' => null, 'limit' => 500],
        ];

        $coupons = [];
        foreach ($couponDefs as $def) {
            $coupons[] = DiscountCoupon::updateOrCreate(
                ['code' => $def['code']],
                [
                    'coupon_category_id' => $couponCategories[$def['category']]->id,
                    'merchant_location_id' => $locations[$def['location']]->id,
                    'title' => $def['title'],
                    'description' => 'Use code ' . $def['code'] . ' to avail this offer.',
                    'discount_type' => $def['type'],
                    'discount_value' => $def['value'],
                    'min_order_value' => $def['min'],
                    'max_discount_amount' => $def['max_discount'],
                    'usage_limit' => $def['limit'],
                    'usage_per_user' => 1,
                    'starts_at' => now()->subDays(rand(1, 15)),
                    'expires_at' => now()->addDays(rand(30, 90)),
                    'is_active' => true,
                ],
            );
        }

        // ───────────────────────────────────────────────
        // 13. Transactions (hand-crafted + factory)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding transactions...');

        $locationValues = collect($locations)->values();
        $userValues = collect($users)->values();

        // Hand-crafted transactions for traceable test data
        $handCraftedTxns = [
            ['user' => 'user1@kutoot.com', 'location' => 'taj-darbar-connaught-place', 'bill' => 850, 'discount' => 170, 'status' => PaymentStatus::Paid, 'type' => TransactionType::CouponRedemption, 'coupon_idx' => 0],
            ['user' => 'user2@kutoot.com', 'location' => 'reliance-fresh-mg-road', 'bill' => 1200, 'discount' => 120, 'status' => PaymentStatus::Completed, 'type' => TransactionType::CouponRedemption, 'coupon_idx' => 2],
            ['user' => 'user3@kutoot.com', 'location' => 'lakme-salon-koramangala', 'bill' => 2500, 'discount' => 300, 'status' => PaymentStatus::Paid, 'type' => TransactionType::CouponRedemption, 'coupon_idx' => 4],
            ['user' => 'user3@kutoot.com', 'location' => 'chai-point-indiranagar', 'bill' => 350, 'discount' => 75, 'status' => PaymentStatus::Paid, 'type' => TransactionType::CouponRedemption, 'coupon_idx' => 6],
            ['user' => 'user4@kutoot.com', 'location' => 'croma-electronics-phoenix-mall', 'bill' => 5999, 'discount' => 500, 'status' => PaymentStatus::Completed, 'type' => TransactionType::CouponRedemption, 'coupon_idx' => 8],
            ['user' => 'user5@kutoot.com', 'location' => 'fabindia-khan-market', 'bill' => 3200, 'discount' => 400, 'status' => PaymentStatus::Paid, 'type' => TransactionType::CouponRedemption, 'coupon_idx' => 10],
            // Plan purchase transactions
            ['user' => 'user2@kutoot.com', 'location' => 'taj-darbar-connaught-place', 'bill' => 19, 'discount' => 0, 'status' => PaymentStatus::Completed, 'type' => TransactionType::PlanPurchase, 'coupon_idx' => null],
            ['user' => 'user3@kutoot.com', 'location' => 'reliance-fresh-mg-road', 'bill' => 39, 'discount' => 0, 'status' => PaymentStatus::Completed, 'type' => TransactionType::PlanPurchase, 'coupon_idx' => null],
            ['user' => 'user4@kutoot.com', 'location' => 'chai-point-indiranagar', 'bill' => 59, 'discount' => 0, 'status' => PaymentStatus::Completed, 'type' => TransactionType::PlanPurchase, 'coupon_idx' => null],
            ['user' => 'user5@kutoot.com', 'location' => 'croma-electronics-phoenix-mall', 'bill' => 79, 'discount' => 0, 'status' => PaymentStatus::Completed, 'type' => TransactionType::PlanPurchase, 'coupon_idx' => null],
        ];

        $transactions = [];
        foreach ($handCraftedTxns as $txn) {
            $user = $users[$txn['user']];
            $location = $locations[$txn['location']];
            $commission = round($txn['bill'] * $location->commission_percentage / 100, 2);
            $platformFee = round($txn['bill'] * 0.02, 2);
            $gstAmount = round($platformFee * 0.18, 2);
            $amount = $txn['bill'] - $txn['discount'];

            $transactions[] = Transaction::create([
                'user_id' => $user->id,
                'coupon_id' => $txn['coupon_idx'] !== null ? $coupons[$txn['coupon_idx']]->id : null,
                'merchant_location_id' => $location->id,
                'original_bill_amount' => $txn['bill'],
                'discount_amount' => $txn['discount'],
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'gst_amount' => $gstAmount,
                'total_amount' => $amount + $platformFee + $gstAmount,
                'type' => $txn['type'],
                'payment_status' => $txn['status'],
                'commission_amount' => $commission,
                'idempotency_key' => Str::uuid()->toString(),
                'created_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        // Factory transactions for volume
        Transaction::factory()->count(10)->paid()->create([
            'merchant_location_id' => $locationValues->random()->id,
        ]);
        Transaction::factory()->count(3)->completed()->create([
            'merchant_location_id' => $locationValues->random()->id,
        ]);
        Transaction::factory()->count(2)->create([
            'merchant_location_id' => $locationValues->random()->id,
            'payment_status' => PaymentStatus::Pending,
        ]);

        // ───────────────────────────────────────────────
        // 14. Stamps (distributed across campaigns and users)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding stamps...');

        $campaignValues = collect($campaigns)->values();
        $allTransactions = Transaction::all();

        // Hand-crafted stamps for each user per accessible campaigns
        foreach ($users as $email => $user) {
            $userPlan = $user->activeSubscription?->plan;
            if (! $userPlan) {
                continue;
            }

            $userCampaignIds = $userPlan->campaigns()->pluck('campaigns.id')->all();
            foreach ($userCampaignIds as $campaignId) {
                $stampCount = rand(1, 5);
                for ($i = 0; $i < $stampCount; $i++) {
                    Stamp::create([
                        'user_id' => $user->id,
                        'campaign_id' => $campaignId,
                        'transaction_id' => $allTransactions->random()->id,
                        'code' => 'ST-' . strtoupper(Str::random(8)),
                        'source' => collect([StampSource::BillPayment, StampSource::PlanPurchase, StampSource::CouponRedemption])->random(),
                        'status' => StampStatus::Used,
                        'created_at' => now()->subDays(rand(1, 30)),
                    ]);
                }
            }
        }

        // Extra factory stamps for volume
        Stamp::factory()->count(15)->create();

        // ───────────────────────────────────────────────
        // 15. Coupon Redemptions
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding coupon redemptions...');

        // Create redemptions for the hand-crafted coupon transactions
        foreach ($transactions as $txn) {
            if ($txn->coupon_id && $txn->type === TransactionType::CouponRedemption) {
                CouponRedemption::updateOrCreate(
                    ['transaction_id' => $txn->id],
                    [
                        'coupon_id' => $txn->coupon_id,
                        'user_id' => $txn->user_id,
                        'discount_applied' => $txn->discount_amount,
                        'original_bill_amount' => $txn->original_bill_amount,
                        'platform_fee' => $txn->platform_fee,
                        'gst_amount' => $txn->gst_amount,
                        'total_paid' => $txn->total_amount,
                    ],
                );
            }
        }

        // ───────────────────────────────────────────────
        // 16. QR Codes (20 codes, some linked to locations)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding QR codes...');

        // 8 linked QR codes
        $linkedLocations = $locationValues->random(min(8, $locationValues->count()));
        foreach ($linkedLocations as $loc) {
            QrCode::create([
                'unique_code' => 'KUT-' . rand(1000, 9999),
                'token' => Str::random(32),
                'merchant_location_id' => $loc->id,
                'status' => QrCodeStatus::Linked,
                'linked_at' => now()->subDays(rand(1, 60)),
                'linked_by' => $admin->id,
            ]);
        }

        // 12 available QR codes
        for ($i = 0; $i < 12; $i++) {
            QrCode::create([
                'unique_code' => 'KUT-' . rand(1000, 9999),
                'token' => Str::random(32),
                'status' => QrCodeStatus::Available,
            ]);
        }

        // ───────────────────────────────────────────────
        // 17. Loan Tiers (4 tiers)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding loan tiers...');

        $loanTierDefs = [
            ['min_streak_months' => 3, 'max_loan_amount' => 25000.00, 'interest_rate_percentage' => 12.00, 'description' => 'Starter tier — 3-month streak required'],
            ['min_streak_months' => 6, 'max_loan_amount' => 75000.00, 'interest_rate_percentage' => 9.00, 'description' => 'Growth tier — 6-month streak required'],
            ['min_streak_months' => 9, 'max_loan_amount' => 150000.00, 'interest_rate_percentage' => 6.00, 'description' => 'Premium tier — 9-month streak required'],
            ['min_streak_months' => 12, 'max_loan_amount' => 500000.00, 'interest_rate_percentage' => 3.50, 'description' => 'Elite tier — 12-month streak required'],
        ];

        $loanTiers = [];
        foreach ($loanTierDefs as $tier) {
            $loanTiers[] = LoanTier::firstOrCreate(
                ['min_streak_months' => $tier['min_streak_months']],
                array_merge($tier, ['is_active' => true]),
            );
        }

        // ───────────────────────────────────────────────
        // 18. Merchant Location Loans (6 loans)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding merchant loans...');

        $loanLocations = $locationValues->take(6);
        $loanStatuses = [
            LoanStatus::Active, LoanStatus::Active, LoanStatus::Active,
            LoanStatus::Completed, LoanStatus::Completed, LoanStatus::Paused,
        ];

        foreach ($loanLocations as $idx => $loc) {
            $tier = $loanTiers[$idx % count($loanTiers)];
            MerchantLocationLoan::create([
                'merchant_location_id' => $loc->id,
                'loan_tier_id' => $tier->id,
                'amount' => round($tier->max_loan_amount * rand(50, 100) / 100, 2),
                'status' => $loanStatuses[$idx],
                'streak_months_at_approval' => $tier->min_streak_months + rand(0, 3),
                'approved_at' => now()->subDays(rand(30, 180)),
                'notes' => $loanStatuses[$idx] === LoanStatus::Completed ? 'Fully repaid' : null,
            ]);
        }

        // ───────────────────────────────────────────────
        // 19. Monthly Summaries (6 months per location)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding monthly summaries...');

        foreach ($locations as $location) {
            for ($i = 0; $i < 6; $i++) {
                $date = now()->subMonths($i);
                $billAmount = rand(50000, 300000);
                $commissionAmount = round($billAmount * $location->commission_percentage / 100, 2);
                $txnCount = rand(20, 150);

                MerchantLocationMonthlySummary::updateOrCreate(
                    [
                        'merchant_location_id' => $location->id,
                        'year' => (int) $date->format('Y'),
                        'month' => (int) $date->format('m'),
                    ],
                    [
                        'total_bill_amount' => $billAmount,
                        'total_commission_amount' => $commissionAmount,
                        'net_amount' => $billAmount - $commissionAmount,
                        'transaction_count' => $txnCount,
                        'target_met' => $i < 4,  // last 4 months met target, 2 oldest didn't
                    ],
                );
            }
        }

        // ───────────────────────────────────────────────
        // 20. Marketing Banners (6)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding marketing banners...');

        $marketingBannerDefs = [
            ['title' => 'Win an iPhone!', 'subtitle' => 'Collect stamps & win big', 'link_url' => '/campaigns/iphone', 'link_text' => 'Join Now'],
            ['title' => 'BMW Bike Giveaway', 'subtitle' => 'Your dream ride awaits', 'link_url' => '/campaigns/bmw-bike', 'link_text' => 'Learn More'],
            ['title' => 'Tata Sierra SUV', 'subtitle' => 'Built for adventure', 'link_url' => '/campaigns/tata-sierra', 'link_text' => 'Participate'],
            ['title' => 'Gold & Diamond Jewellery', 'subtitle' => 'Timeless beauty awaits', 'link_url' => '/campaigns/jewellery', 'link_text' => 'Start Earning'],
            ['title' => 'Dream Villa', 'subtitle' => 'Luxury living unlocked', 'link_url' => '/campaigns/villa', 'link_text' => 'Explore'],
            ['title' => 'All Access Pass ₹99', 'subtitle' => 'Unlock every campaign', 'link_url' => '/plans', 'link_text' => 'Subscribe Now'],
        ];

        // Images from frontend public folder mapped to banners
        $bannerImages = [
            public_path('images/kutoot-full-logo.png'),
        ];
        $bannerImagePath = $bannerImages[0];

        foreach ($marketingBannerDefs as $idx => $banner) {
            $mb = MarketingBanner::updateOrCreate(
                ['title' => $banner['title']],
                array_merge($banner, ['sort_order' => $idx + 1, 'is_active' => true]),
            );

            if (file_exists($bannerImagePath) && $mb->getFirstMedia('image') === null) {
                $mb->addMedia($bannerImagePath)
                    ->preservingOriginal()
                    ->toMediaCollection('image');
            }
        }

        // ───────────────────────────────────────────────
        // 21. Store Banners (6)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding store banners...');

        $storeBannerDefs = [
            ['title' => 'Taj Darbar — Fine Dining', 'alt_text' => 'Taj Darbar restaurant banner', 'link_url' => '/stores/taj-darbar'],
            ['title' => 'Reliance Fresh — Daily Savings', 'alt_text' => 'Reliance Fresh grocery deals', 'link_url' => '/stores/reliance-fresh'],
            ['title' => 'Lakme Salon — Glow Up', 'alt_text' => 'Lakme salon services', 'link_url' => '/stores/lakme-salon'],
            ['title' => 'Chai Point — Freshly Brewed', 'alt_text' => 'Chai Point cafe', 'link_url' => '/stores/chai-point'],
            ['title' => 'Croma — Tech Deals', 'alt_text' => 'Croma electronics offers', 'link_url' => '/stores/croma-electronics'],
            ['title' => 'Fabindia — Ethnic Fashion', 'alt_text' => 'Fabindia clothing store', 'link_url' => '/stores/fabindia'],
        ];

        foreach ($storeBannerDefs as $idx => $banner) {
            $sb = StoreBanner::updateOrCreate(
                ['title' => $banner['title']],
                array_merge($banner, ['sort_order' => $idx + 1, 'is_active' => true]),
            );

            if (file_exists($bannerImagePath) && $sb->getFirstMedia('image') === null) {
                $sb->addMedia($bannerImagePath)
                    ->preservingOriginal()
                    ->toMediaCollection('image');
            }
        }

        // ───────────────────────────────────────────────
        // 22. Featured Banners (4)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding featured banners...');

        $featuredBannerDefs = [
            ['title' => 'Pro Plan — Most Popular', 'link_url' => '/plans/pro', 'link_text' => 'Upgrade Now'],
            ['title' => 'Weekend Sale — 40% Off', 'link_url' => '/deals', 'link_text' => 'Shop Deals'],
            ['title' => 'New Merchant Partners', 'link_url' => '/stores', 'link_text' => 'Discover Stores'],
            ['title' => 'Refer & Earn Stamps', 'link_url' => '/referral', 'link_text' => 'Invite Friends'],
        ];

        foreach ($featuredBannerDefs as $idx => $banner) {
            $fb = FeaturedBanner::updateOrCreate(
                ['title' => $banner['title']],
                array_merge($banner, ['sort_order' => $idx + 1, 'is_active' => true]),
            );

            if (file_exists($bannerImagePath) && $fb->getFirstMedia('image') === null) {
                $fb->addMedia($bannerImagePath)
                    ->preservingOriginal()
                    ->toMediaCollection('image');
            }
        }

        // ───────────────────────────────────────────────
        // 23. News Articles (4)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding news articles...');

        $newsDefs = [
            ['title' => 'Kutoot Launches iPhone Giveaway Campaign', 'description' => 'Collect stamps from your favourite merchants and stand a chance to win the latest Apple iPhone. Campaign is open to all users.', 'link_url' => '/campaigns/iphone', 'published_at' => now()->subDays(5)],
            ['title' => 'New Merchant Partners Added — Taj Darbar, Lakme Salon, and More', 'description' => 'We are excited to welcome 6 new merchant partners across India. Shop, dine, and earn stamps at these popular locations.', 'link_url' => '/stores', 'published_at' => now()->subDays(10)],
            ['title' => 'Pro Plan Now at Just ₹39 — Best Value!', 'description' => 'Unlock the BMW Bike campaign with our most popular Pro plan. Limited-time pricing available now.', 'link_url' => '/plans', 'published_at' => now()->subDays(3)],
            ['title' => 'Tata Sierra & Villa Campaigns Going Strong', 'description' => 'Over 500 users have joined the Tata Sierra and Villa campaigns. Upgrade to VIP or Elite to participate in these exclusive draws.', 'link_url' => '/campaigns', 'published_at' => now()->subDays(1)],
        ];

        foreach ($newsDefs as $idx => $news) {
            $na = NewsArticle::updateOrCreate(
                ['title' => $news['title']],
                array_merge($news, ['sort_order' => $idx + 1, 'is_active' => true]),
            );

            if (file_exists($bannerImagePath) && $na->getFirstMedia('image') === null) {
                $na->addMedia($bannerImagePath)
                    ->preservingOriginal()
                    ->toMediaCollection('image');
            }
        }

        // ───────────────────────────────────────────────
        // 24. Merchant Notification Settings (1 per location)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding notification settings...');

        foreach ($locations as $location) {
            MerchantNotificationSetting::updateOrCreate(
                ['merchant_location_id' => $location->id],
                [
                    'enabled' => true,
                    'channels' => ['email', 'sms'],
                ],
            );
        }

        // ───────────────────────────────────────────────
        // 25. Merchant Applications (3)
        // ───────────────────────────────────────────────
        $this->command?->info('Seeding merchant applications...');

        MerchantApplication::updateOrCreate(
            ['owner_email' => 'ravi@tikkahouse.com'],
            [
                'store_name' => 'Tikka House',
                'store_type' => 'Restaurant',
                'owner_mobile' => '9300000001',
                'phone_verified' => true,
                'email_verified' => true,
                'address' => 'Sector 18, Noida, Uttar Pradesh',
                'gst_number' => '09AABCU1234D1Z5',
                'status' => MerchantApplicationStatus::Pending,
            ],
        );

        MerchantApplication::updateOrCreate(
            ['owner_email' => 'meena@greenleaf.in'],
            [
                'store_name' => 'Green Leaf Organics',
                'store_type' => 'Grocery',
                'owner_mobile' => '9300000002',
                'phone_verified' => true,
                'email_verified' => true,
                'address' => 'Whitefield, Bengaluru, Karnataka',
                'gst_number' => '29AABCU5678D1Z3',
                'status' => MerchantApplicationStatus::Approved,
                'admin_notes' => 'Verified and approved — strong local presence.',
                'processed_by' => $admin->id,
                'processed_at' => now()->subDays(7),
                'merchant_location_id' => $locationValues->first()->id,
            ],
        );

        MerchantApplication::updateOrCreate(
            ['owner_email' => 'suresh@quickfix.com'],
            [
                'store_name' => 'QuickFix Mobile Repair',
                'store_type' => 'Electronics',
                'owner_mobile' => '9300000003',
                'phone_verified' => false,
                'email_verified' => false,
                'address' => 'Ameerpet, Hyderabad, Telangana',
                'status' => MerchantApplicationStatus::Rejected,
                'admin_notes' => 'Incomplete documentation — phone and email not verified.',
                'processed_by' => $admin->id,
                'processed_at' => now()->subDays(3),
            ],
        );

        // ───────────────────────────────────────────────
        // Done!
        // ───────────────────────────────────────────────
        $this->command?->info('');
        $this->command?->info('✅  Comprehensive seeding complete!');
        $this->command?->info('');
        $this->command?->info('📊  Summary:');
        $this->command?->info('    Plans: ' . SubscriptionPlan::count());
        $this->command?->info('    Campaigns: ' . Campaign::count());
        $this->command?->info('    Merchants: ' . Merchant::count());
        $this->command?->info('    Locations: ' . MerchantLocation::count());
        $this->command?->info('    Coupons: ' . DiscountCoupon::count());
        $this->command?->info('    Users: ' . User::count());
        $this->command?->info('    Transactions: ' . Transaction::count());
        $this->command?->info('    Stamps: ' . Stamp::count());
        $this->command?->info('    QR Codes: ' . QrCode::count());
        $this->command?->info('');
        $this->command?->info('🔑  Test Credentials (password: "password"):');
        $this->command?->info('    Admin:    it@kutoot.com');
        $this->command?->info('    Seller1:  seller1@kutoot.com  (Taj Darbar, Connaught Place)');
        $this->command?->info('    Seller2:  seller2@kutoot.com  (Reliance Fresh, MG Road)');
        $this->command?->info('    Seller3:  seller3@kutoot.com  (Lakme Salon, Koramangala)');
        $this->command?->info('    User1:    user1@kutoot.com    (Free plan)');
        $this->command?->info('    User2:    user2@kutoot.com    (Basic plan)');
        $this->command?->info('    User3:    user3@kutoot.com    (Pro plan)');
        $this->command?->info('    User4:    user4@kutoot.com    (VIP plan)');
        $this->command?->info('    User5:    user5@kutoot.com    (Elite plan)');
    }
}



