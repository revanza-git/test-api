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
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'user',
            'active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * Note: This project does not implement email verification (no `email_verified_at` column),
     * so this state is intentionally a no-op and kept only for compatibility with the default
     * Laravel factory API.
     */
    public function unverified(): static
    {
        return $this;
    }
}
