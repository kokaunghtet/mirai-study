<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
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
