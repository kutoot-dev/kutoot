<?php

namespace Database\Factories;

use App\Models\MerchantCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantCategory>
 */
class MerchantCategoryFactory extends Factory
{
    protected $model = MerchantCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Restaurant', 'Grocery', 'Salon', 'Cafe', 'Electronics',
                'Fashion', 'Pharmacy', 'Laundry', 'Fitness', 'Travel',
            ]),
            'serial' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes): array => [
        'is_active' => false,
        ]);
    }
}
