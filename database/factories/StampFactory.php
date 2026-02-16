<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Stamp;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stamp>
 */
class StampFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'campaign_id' => Campaign::factory(),
            'transaction_id' => Transaction::factory(),
            'code' => fake()->unique()->numerify('ST-########'),
        ];
    }
}
