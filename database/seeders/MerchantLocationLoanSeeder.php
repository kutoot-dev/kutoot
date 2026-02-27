<?php

namespace Database\Seeders;

use App\Models\MerchantLocationLoan;
use Illuminate\Database\Seeder;

class MerchantLocationLoanSeeder extends Seeder
{
    /**
     * Seed sample merchant location loans with mixed statuses.
     */
    public function run(): void
    {
        MerchantLocationLoan::factory()->count(3)->create();
        MerchantLocationLoan::factory()->count(2)->completed()->create();
        MerchantLocationLoan::factory()->count(1)->paused()->create();
    }
}
