<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;

class MerchantSeeder extends Seeder
{
    /**
     * Seed sample merchants.
     */
    public function run(): void
    {
        Merchant::factory()->count(5)->create();
    }
}
