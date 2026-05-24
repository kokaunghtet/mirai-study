<?php

namespace Database\Factories;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        $started = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'user_id'         => null,
            'category_id'     => null,
            'level_id'        => null,
            'total_questions' => 10,
            'score'           => null,
            'started_at'      => $started,
            'completed_at'    => fake()->dateTimeBetween($started, 'now'),
        ];
    }
}
