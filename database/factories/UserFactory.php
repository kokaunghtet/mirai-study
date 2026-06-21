<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'display_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'bio' => fake()->optional()->sentence(),
            'profile_image' => null,
            'role' => 'user',
            'status' => 'active',
            'email_verified_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function moderator(): static
    {
        return $this->state(['role' => 'moderator']);
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function twoFactor(): static
    {
        return $this->state(['two_factor_enabled' => true]);
    }
}
