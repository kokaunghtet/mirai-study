<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            // Faker uses Lorem Ipsum by default for methods like paragraph(), sentence(), and text().
            // 'title' => fake()->optional()->sentence(),
            // 'content' => fake()->paragraph(3),

            // Faker includes a Markov-chain text generator that outputs realistic-sounding English sentences based on Alice in Wonderland.
            'title' => fake()->optional()->realText(30),
            'content' => fake()->realText(150),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Post $post) {
            $tagIds = Tag::query()->pluck('id');

            if ($tagIds->isNotEmpty()) {
                $post->tags()->attach(
                    $tagIds->random(min($tagIds->count(), rand(1, 3)))
                );
            }
        });
    }
}
