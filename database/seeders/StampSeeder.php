<?php

namespace Database\Seeders;

use App\Models\Stamp;
use Illuminate\Database\Seeder;

class StampSeeder extends Seeder
{
    /**
     * Seed sample stamps for testing.
     */
    public function run(): void
    {
        Stamp::factory()->count(20)->create();
    }
}
