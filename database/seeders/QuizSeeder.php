<?php

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $users      = User::all();
        $categories = ExamCategory::with('levels')->get();

        for ($i = 0; $i < 15; $i++) {
            $user     = $users->random();
            $category = $categories->random();
            $level    = $category->levels->random();

            $attempt = QuizAttempt::create([
                'user_id'         => $user->id,
                'category_id'     => $category->id,
                'level_id'        => $level->id,
                'total_questions' => 10,
                'score'           => null,
                'started_at'      => now()->subMinutes(rand(10, 60)),
                'completed_at'    => now(),
            ]);

            $questions = Question::query()
                ->where('category_id', '=', $category->id)
                ->where('level_id', '=', $level->id)
                ->inRandomOrder()
                ->take(10)
                ->get();

            // Safety check — skip if not enough questions exist
            if ($questions->isEmpty()) continue;

            $score = 0;

            foreach ($questions as $question) {
                $selected  = Arr::random(['A', 'B', 'C', 'D']);
                $isCorrect = $selected === $question->answer;

                if ($isCorrect) $score++;

                UserAnswer::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'selected_answer' => $selected,
                    'is_correct'      => $isCorrect,
                ]);
            }

            $attempt->update(['score' => $score]);
        }
    }
}
