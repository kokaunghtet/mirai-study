<?php

namespace Database\Factories;

use App\Models\ExamCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamLevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => ExamCategory::factory(),
            'code' => fake()->bothify('??#'),
            'name' => fake()->word(),
        ];
    }
}
