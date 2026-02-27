<?php

namespace Database\Seeders;

use App\Models\LoanTier;
use Illuminate\Database\Seeder;

class LoanTierSeeder extends Seeder
{
    /**
     * Seed loan tiers with progressive streak requirements and loan limits.
     */
    public function run(): void
    {
        $tiers = [
            [
                'min_streak_months' => 3,
                'max_loan_amount' => 25000.00,
                'interest_rate_percentage' => 12.00,
                'description' => 'Starter tier — 3-month streak required',
                'is_active' => true,
            ],
            [
                'min_streak_months' => 6,
                'max_loan_amount' => 75000.00,
                'interest_rate_percentage' => 9.00,
                'description' => 'Growth tier — 6-month streak required',
                'is_active' => true,
            ],
            [
                'min_streak_months' => 9,
                'max_loan_amount' => 150000.00,
                'interest_rate_percentage' => 6.00,
                'description' => 'Premium tier — 9-month streak required',
                'is_active' => true,
            ],
            [
                'min_streak_months' => 12,
                'max_loan_amount' => 500000.00,
                'interest_rate_percentage' => 3.50,
                'description' => 'Elite tier — 12-month streak required',
                'is_active' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            LoanTier::firstOrCreate(
                ['min_streak_months' => $tier['min_streak_months']],
                $tier,
            );
        }
    }
}
