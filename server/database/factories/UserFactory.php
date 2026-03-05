<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
            'password' => static::$password ??= Hash::make('password'),
            'role' => 2,
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'street' => fake()->streetName(),
            'house_number' => (string) fake()->buildingNumber(),
            'zip_code' => fake()->postcode(),
            'billing_phone' => fake()->phoneNumber(),
            'billing_city' => fake()->city(),
            'billing_street' => fake()->streetName(),
            'billing_house_number' => (string) fake()->buildingNumber(),
            'billing_zip_code' => fake()->postcode(),
        ];
    }
}
