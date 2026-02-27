<?php

namespace Database\Seeders;

use App\Models\MerchantLocation;
use App\Models\MerchantLocationMonthlySummary;
use Illuminate\Database\Seeder;

class MerchantLocationMonthlySummarySeeder extends Seeder
{
    /**
     * Seed monthly summaries for existing merchant locations over the last 6 months.
     * If no locations exist, creates 3 summaries with auto-generated locations.
     */
    public function run(): void
    {
        $locations = MerchantLocation::all();

        if ($locations->isEmpty()) {
            MerchantLocationMonthlySummary::factory()->count(3)->targetMet()->create();

            return;
        }

        $locations->each(function (MerchantLocation $location) {
            // Create summaries for the last 6 months
            for ($i = 0; $i < 6; $i++) {
                $date = now()->subMonths($i);

                MerchantLocationMonthlySummary::factory()
                    ->for($location)
                    ->create([
                        'year' => (int) $date->format('Y'),
                        'month' => (int) $date->format('m'),
                        'target_met' => $i < 4, // last 4 months met, 2 oldest didn't
                    ]);
            }
        });
    }
}
