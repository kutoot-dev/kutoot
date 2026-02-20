<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->unique()->numerify('9#########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
        'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user has no mobile number.
     */
    public function withoutMobile(): static
    {
        return $this->state(fn(array $attributes) => [
        'mobile' => null,
        ]);
    }
    /**
     * Indicate that the user only has a mobile number.
     */
    public function mobileOnly(): static
    {
        return $this->state(fn(array $attributes) => [
        'email' => null,
        'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user only has an email address.
     */
    public function emailOnly(): static
    {
        return $this->state(fn(array $attributes) => [
        'mobile' => null,
        ]);
    }
}
