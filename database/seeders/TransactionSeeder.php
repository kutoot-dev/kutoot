<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Seed sample transactions with mixed payment states.
     */
    public function run(): void
    {
        Transaction::factory()->count(10)->paid()->create();
        Transaction::factory()->count(3)->completed()->create();
        Transaction::factory()->count(2)->create(); // pending
    }
}
