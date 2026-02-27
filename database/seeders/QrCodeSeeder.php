<?php

namespace Database\Seeders;

use App\Models\QrCode;
use Illuminate\Database\Seeder;

class QrCodeSeeder extends Seeder
{
    /**
     * Seed available QR codes for stamp collection.
     */
    public function run(): void
    {
        QrCode::factory()->count(20)->create();
    }
}
