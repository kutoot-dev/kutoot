<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Enums\CreatorType;
use App\Models\CampaignCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'creator_type' => CreatorType::Merchant,
            'category_id' => CampaignCategory::factory(),
            'reward_name' => $this->faker->words(3, true),
            'status' => CampaignStatus::Active,
            'start_date' => now(),
            'reward_cost_target' => 1000.00,
            'stamp_target' => 10,
            'collected_commission_cache' => 0,
            'issued_stamps_cache' => 0,
            'is_active' => true,
        ];
    }
}
