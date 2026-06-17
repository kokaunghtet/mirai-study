<?php

namespace Tests\Feature;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a JLPT N3 Kanji pool of $count questions that all answer "A",
     * so a fully-known submission can be graded deterministically.
     */
    private function seedKanjiPool(int $count = 20): array
    {
        $category = ExamCategory::create(['name' => 'JLPT']);
        $level = ExamLevel::create([
            'category_id' => $category->id,
            'code' => 'N3',
            'name' => 'N3',
        ]);

        Question::factory($count)->create([
            'category_id' => $category->id,
            'level_id' => $level->id,
            'section' => 'kanji',
            'answer' => 'A',
        ]);

        return [$category, $level];
    }

    public function test_start_creates_an_attempt_and_redirects_to_the_player(): void
    {
        $this->seedKanjiPool(25);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('quiz.start'), [
            'category' => 'JLPT',
            'level' => 'N3',
            'section' => 'kanji',
            'count' => 20,
        ]);

        $attempt = QuizAttempt::first();

        $this->assertNotNull($attempt);
        $this->assertSame(20, $attempt->total_questions);
        $this->assertSame('kanji', $attempt->section);
        $this->assertNull($attempt->completed_at);
        $response->assertRedirect(route('quiz.show', $attempt));
    }

    public function test_submit_grades_and_finalises_the_attempt(): void
    {
        $this->seedKanjiPool(20); // pool == count, so all 20 are in the attempt
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('quiz.start'), [
            'category' => 'JLPT',
            'level' => 'N3',
            'section' => 'kanji',
            'count' => 20,
        ]);

        $attempt = QuizAttempt::first();

        // Every question answers "A"; answer them all correctly.
        $answers = Question::pluck('answer', 'id')->all(); // [id => 'A', ...]

        $response = $this->actingAs($user)->post(route('quiz.submit', $attempt), [
            'answers' => $answers,
        ]);

        $attempt->refresh();

        $this->assertSame(20, $attempt->score);
        $this->assertNotNull($attempt->completed_at);
        $this->assertDatabaseCount('user_answers', 20);
        $response->assertRedirect(route('quiz.result', $attempt));
    }

    public function test_a_user_cannot_view_someone_elses_attempt(): void
    {
        $this->seedKanjiPool(20);
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($owner)->post(route('quiz.start'), [
            'category' => 'JLPT',
            'level' => 'N3',
            'section' => 'kanji',
            'count' => 20,
        ]);

        $attempt = QuizAttempt::first();

        $this->actingAs($intruder)->get(route('quiz.show', $attempt))->assertForbidden();
    }

    public function test_result_redirects_to_player_until_completed(): void
    {
        $this->seedKanjiPool(20);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('quiz.start'), [
            'category' => 'JLPT',
            'level' => 'N3',
            'section' => 'kanji',
            'count' => 20,
        ]);

        $attempt = QuizAttempt::first();

        $this->actingAs($user)->get(route('quiz.result', $attempt))
            ->assertRedirect(route('quiz.show', $attempt));
    }

    public function test_index_player_and_result_pages_render(): void
    {
        $this->seedKanjiPool(20);
        $user = User::factory()->create();

        // Index (selection wizard)
        $this->actingAs($user)->get(route('quiz.index'))->assertOk();

        // Player
        $this->actingAs($user)->post(route('quiz.start'), [
            'category' => 'JLPT',
            'level' => 'N3',
            'section' => 'kanji',
            'count' => 20,
        ]);
        $attempt = QuizAttempt::first();
        $this->actingAs($user)->get(route('quiz.show', $attempt))
            ->assertOk()
            ->assertSee('JLPT N3 · Kanji');

        // Result (after completion)
        $this->actingAs($user)->post(route('quiz.submit', $attempt), [
            'answers' => Question::pluck('answer', 'id')->all(),
        ]);
        $this->actingAs($user)->get(route('quiz.result', $attempt))
            ->assertOk()
            ->assertSee('100%');
    }

    public function test_section_is_required_when_the_level_has_sections(): void
    {
        $this->seedKanjiPool(20);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('quiz.start'), [
            'category' => 'JLPT',
            'level' => 'N3',
            'count' => 20,
        ])->assertSessionHasErrors('section');

        $this->assertDatabaseCount('quiz_attempts', 0);
    }
}
