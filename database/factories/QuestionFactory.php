<?php

namespace Database\Factories;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => null,
            'level_id'    => null,
            'section'     => null,
            'text'        => fake()->sentence() . '?',
            'option_a'    => fake()->word(),
            'option_b'    => fake()->word(),
            'option_c'    => fake()->word(),
            'option_d'    => fake()->word(),
            'answer'      => fake()->randomElement(['A', 'B', 'C', 'D']),
            'explanation' => fake()->optional()->sentence(),
        ];
    }
}
