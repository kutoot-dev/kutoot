<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantLocation;
use Illuminate\Database\Seeder;

class MerchantLocationSeeder extends Seeder
{
    /**
     * Seed merchant locations. Creates 2 locations per existing merchant,
     * or 5 locations with auto-generated merchants if none exist.
     */
    public function run(): void
    {
        $merchants = Merchant::all();

        if ($merchants->isEmpty()) {
            MerchantLocation::factory()->count(5)->create();

            return;
        }

        $merchants->each(function (Merchant $merchant) {
            MerchantLocation::factory()
                ->count(2)
                ->for($merchant)
                ->create();
        });
    }
}
