<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id'   => Post::factory(),
            'user_id'   => User::factory(),
            'parent_id' => null,
            'content'   => fake()->sentence(),
        ];
    }
}
