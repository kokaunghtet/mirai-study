<?php

namespace App\Http\Controllers;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    /**
     * Step 1–4 selection wizard (category → level → section → count).
     * The whole tree lives in config/quiz.php and is driven client-side.
     */
    public function index()
    {
        return view('quiz.index', [
            'catalog' => config('quiz.catalog'),
            'counts' => config('quiz.counts'),
        ]);
    }

    /**
     * Validate the selection, draw a random question pool, create the attempt,
     * and remember the chosen question order for show()/submit().
     */
    public function start(Request $request)
    {
        $catalog = config('quiz.catalog');

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys($catalog))],
            'level' => ['required', 'string'],
            'section' => ['nullable', 'string'],
            'count' => ['required', 'integer', Rule::in(config('quiz.counts'))],
        ]);

        $levels = $catalog[$data['category']]['levels'];

        if (! array_key_exists($data['level'], $levels)) {
            throw ValidationException::withMessages(['level' => 'Invalid level for this category.']);
        }

        $sections = $levels[$data['level']]['sections'] ?? [];
        $section = $data['section'] ?? null;

        if (! empty($sections)) {
            if (! $section || ! array_key_exists($section, $sections)) {
                throw ValidationException::withMessages(['section' => 'Please choose a section.']);
            }
        } else {
            $section = null; // level-only quiz (e.g. ITPEC IP) — ignore any stray section
        }

        $category = ExamCategory::where('name', $data['category'])->firstOrFail();
        $level = ExamLevel::where('category_id', $category->id)
            ->where('code', $data['level'])->firstOrFail();

        $questionIds = Question::pool($category->id, $level->id, $section)
            ->inRandomOrder()
            ->limit($data['count'])
            ->pluck('id')
            ->all();

        if (empty($questionIds)) {
            return back()->with('error', 'No questions are available for that selection yet.');
        }

        $attempt = QuizAttempt::create([
            'user_id' => $request->user()->id,
            'category_id' => $category->id,
            'level_id' => $level->id,
            'section' => $section,
            'total_questions' => count($questionIds),   // may be < requested if the pool is small
            'score' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $request->session()->put($this->sessionKey($attempt), $questionIds);

        return redirect()->route('quiz.show', $attempt);
    }

    /**
     * Render the quiz player. Correct answers are deliberately NOT sent to the
     * browser here — grading happens server-side in submit().
     */
    public function show(Request $request, QuizAttempt $attempt)
    {
        $this->authorizeOwner($request, $attempt);

        if ($attempt->isCompleted()) {
            return redirect()->route('quiz.result', $attempt);
        }

        $questionIds = $request->session()->get($this->sessionKey($attempt));

        // Session lost (refresh on another device, cache cleared) — re-draw a pool.
        if (empty($questionIds)) {
            $questionIds = Question::pool($attempt->category_id, $attempt->level_id, $attempt->section)
                ->inRandomOrder()
                ->limit($attempt->total_questions)
                ->pluck('id')
                ->all();
            $request->session()->put($this->sessionKey($attempt), $questionIds);
        }

        $byId = Question::whereIn('id', $questionIds)
            ->get(['id', 'text', 'option_a', 'option_b', 'option_c', 'option_d'])
            ->keyBy('id');

        // Preserve the stored (random) order and shape a leak-free payload.
        $questions = collect($questionIds)
            ->map(fn ($id) => $byId->get($id))
            ->filter()
            ->map(fn ($q) => [
                'id' => $q->id,
                'text' => $q->text,
                'options' => [
                    'A' => $q->option_a,
                    'B' => $q->option_b,
                    'C' => $q->option_c,
                    'D' => $q->option_d,
                ],
            ])
            ->values();

        return view('quiz.show', [
            'attempt' => $attempt,
            'questions' => $questions,
            'heading' => $this->heading($attempt),
        ]);
    }

    /**
     * Grade the submitted answers, persist them, and finalise the attempt.
     */
    public function submit(Request $request, QuizAttempt $attempt)
    {
        $this->authorizeOwner($request, $attempt);

        if ($attempt->isCompleted()) {
            return redirect()->route('quiz.result', $attempt);
        }

        $data = $request->validate([
            'answers' => ['array'],
            'answers.*' => ['nullable', Rule::in(['A', 'B', 'C', 'D'])],
        ]);
        $answers = $data['answers'] ?? [];

        $questionIds = $request->session()->get($this->sessionKey($attempt), []);

        // Grade only questions that belong to this attempt.
        $questions = Question::whereIn('id', $questionIds)->get(['id', 'answer']);

        $score = 0;
        $rows = [];

        foreach ($questions as $question) {
            $selected = $answers[$question->id] ?? null;

            if ($selected === null) {
                continue; // unanswered — no row; still counts against total_questions
            }

            $isCorrect = $selected === $question->answer;
            $score += $isCorrect ? 1 : 0;

            $rows[] = [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_answer' => $selected,
                'is_correct' => $isCorrect,
            ];
        }

        if (! empty($rows)) {
            UserAnswer::insert($rows); // created_at filled by the DB default (useCurrent)
        }

        $attempt->update([
            'score' => $score,
            'completed_at' => now(),
        ]);

        $request->session()->forget($this->sessionKey($attempt));

        return redirect()->route('quiz.result', $attempt);
    }

    /**
     * Score summary + per-question review.
     */
    public function result(Request $request, QuizAttempt $attempt)
    {
        $this->authorizeOwner($request, $attempt);

        if (! $attempt->isCompleted()) {
            return redirect()->route('quiz.show', $attempt);
        }

        $attempt->load(['answers.question']);

        return view('quiz.result', [
            'attempt' => $attempt,
            'heading' => $this->heading($attempt),
        ]);
    }

    /**
     * Only the owner may view/submit an attempt.
     */
    private function authorizeOwner(Request $request, QuizAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
    }

    private function sessionKey(QuizAttempt $attempt): string
    {
        return "quiz.attempt.{$attempt->id}.questions";
    }

    /**
     * Human label like "JLPT N3 · Kanji" or "ITPEC IP" (no section).
     */
    private function heading(QuizAttempt $attempt): string
    {
        $attempt->loadMissing(['category', 'level']);

        $parts = array_filter([
            $attempt->category?->name,
            $attempt->level?->code,
        ]);

        $label = implode(' ', $parts);

        if ($attempt->section) {
            $sectionLabel = config(
                "quiz.catalog.{$attempt->category?->name}.levels.{$attempt->level?->code}.sections.{$attempt->section}",
                ucfirst($attempt->section)
            );
            $label .= ' · '.$sectionLabel;
        }

        return $label;
    }
}
