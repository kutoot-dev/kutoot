<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QrCode>
 */
class QrCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unique_code' => 'KUT-' . $this->faker->unique()->numberBetween(1000, 9999),
            'token' => \Illuminate\Support\Str::random(32),
            'status' => true,
        ];
    }
}
