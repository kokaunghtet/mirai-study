# Quiz Section — ITPEC & JLPT

The interactive practice-quiz feature. A student picks a track, drills down to a
specific pool of questions, chooses how many to answer, works through them one at
a time, and gets a graded result with a per-question review.

- **Config (single source of truth):** `config/quiz.php`
- **Controller:** `app/Http/Controllers/QuizController.php`
- **Models:** `Question`, `QuizAttempt`, `UserAnswer` (+ `ExamCategory`, `ExamLevel`)
- **Views:** `resources/views/quiz/{index,show,result}.blade.php`
- **JS (Alpine):** `quizSetup` + `quizPlayer` in `resources/js/app.js`
- **Routes:** the `quiz.*` group in `routes/web.php` (auth-only)
- **Tests:** `tests/Feature/QuizTest.php`

---

## The selection tree

Two tracks, each with its own depth:

```
JLPT  → N1 / N2 / N3 / N4 / N5   → kanji / vocab / grammar      → [20|40|60] questions
ITPEC → IP                        → (no 3rd tier)                → [20|40|60] questions
        FE                        → technology / strategy        → [20|40|60] questions
```

The "3rd tier" (subject for JLPT, field for FE) is called a **section** throughout
the code. IP has no sections, so its `section` is `null`.

---

## `config/quiz.php` — why it exists

The section list is domain knowledge, **not** its own DB table. To stop the tree
from drifting between the parts that need it, the whole catalog lives in one config
file that **three places** read:

1. **`ExamSeeder`** — iterates it to create categories, levels, and one question
   pool per (level, section).
2. **`QuizController::start()`** — validates the submitted category/level/section
   against it.
3. **`quiz/index.blade.php`** — `@js($catalog)` feeds the Alpine wizard.

Shape:

```php
'counts'        => [20, 40, 60],
'seed_per_pool' => 60,   // questions per pool; must be >= max(counts)
'pass_mark'     => 60,   // % for the "Passed" label
'catalog' => [
    'JLPT'  => ['label','blurb','levels' => ['N1' => ['label','sections' => ['kanji'=>'Kanji',...]], ...]],
    'ITPEC' => ['label','blurb','levels' => ['IP' => ['label','sections' => []], 'FE' => ['label','sections' => [...]]]],
],
```

**Section codes** (`kanji`, `technology`, …) are stored in the DB; the **labels**
here are display-only. Category names (`JLPT`/`ITPEC`) and level codes
(`N3`/`FE`/`IP`) in the catalog match the values seeded into `exam_categories.name`
and `exam_levels.code`, which is how `start()` resolves a selection back to model rows.

---

## Schema

The base tables (`exam_categories → exam_levels → questions → quiz_attempts →
user_answers`) already existed. Building the quiz needed **one fix**: there was no
column for the 3rd tier. Two original `create_*` migrations were edited in place
(the project rebuilds with `migrate:fresh --seed`, no prod data to preserve):

- `questions` — added `section` (nullable string) + index `(category_id, level_id, section)`.
- `quiz_attempts` — added `section` (nullable string) so results can show what was taken.

A pool is selected with the scope added to `Question`:

```php
Question::pool($categoryId, $levelId, $section)   // null $section = "level only" (IP)
```

`ExamSeeder` seeds `seed_per_pool` (60) placeholder questions **per pool** so any
20/40/60 choice can always be filled — ~1080 questions total (JLPT 5×3×60 + ITPEC
FE 2×60 + IP 60). They're Faker placeholders, tagged in the text (e.g.
`"[N3 Kanji] …?"`) so seeded data is recognisable; replace with real content later.

---

## Request flow

The five `quiz.*` routes map to a deliberate lifecycle. (Note the route order in
`web.php`: `/quiz/{attempt}/result` is declared **before** `/quiz/{attempt}` so the
more specific path wins.)

| Step | Route | What happens |
|---|---|---|
| 1 | `GET /quiz` → `index` | Renders the selection wizard; passes the catalog + counts. |
| 2 | `POST /quiz/start` → `start` | Validates the selection, draws a random pool, creates the `QuizAttempt`, stashes the chosen question-id order in the **session**, redirects to the player. |
| 3 | `GET /quiz/{attempt}` → `show` | Renders the player. Loads the question set from the session (re-draws if it was lost). |
| 4 | `POST /quiz/{attempt}/submit` → `submit` | Grades server-side, writes `user_answers`, sets `score` + `completed_at`, clears the session, redirects to the result. |
| 5 | `GET /quiz/{attempt}/result` → `result` | Shows the score ring + per-question review. |

**Why the session holds the question set:** between `start` and `submit` the chosen
questions must stay fixed, but `quiz_attempts` doesn't store them and we can't
pre-create `user_answers` rows (`selected_answer` is a NOT-NULL enum). So `start`
puts the ordered ids under `quiz.attempt.{id}.questions` and `show`/`submit` read
them back. If the session is gone (different device, cleared cache), `show`
re-draws a fresh pool of the same size — harmless because nothing's been answered yet.

---

## Security: answers never reach the client mid-quiz

`show()` builds a **leak-free payload** — only `id`, `text`, and the four option
strings. The correct `answer` and `explanation` columns are deliberately withheld,
so you can't read the key out of the page source or network tab. Grading is
**entirely server-side** in `submit()`, and correct answers are only revealed by
`result.blade.php` (rendered server-side, after completion).

Each attempt is owner-scoped: `authorizeOwner()` does
`abort_unless($attempt->user_id === $request->user()->id, 403)` on show/submit/result.

---

## Grading

`submit()` validates `answers` as a map of `questionId => A|B|C|D` (each nullable),
then iterates **only** the questions that belong to the attempt (from the session).
For each answered question it inserts a `UserAnswer` with
`is_correct = selected === question->answer`. Unanswered questions get **no row** —
they simply don't count toward the score, while `total_questions` stays the
denominator. So:

```
percentage = round(score / total_questions * 100)   // QuizAttempt::percentage()
passed     = percentage >= config('quiz.pass_mark')  // QuizAttempt::passed()
skipped    = total_questions - answers->count()      // shown on the result page
```

`UserAnswer::insert()` (bulk) is used; `user_answers.created_at` is `useCurrent()`
so the DB fills it even though the bulk insert skips model timestamps.

---

## Front end

Two Alpine components, registered next to the existing ones in `resources/js/app.js`:

- **`quizSetup(catalog, counts)`** — drives `quiz/index`. Holds `category`, `level`,
  `section`, `count`. Computed getters (`levels`, `sections`, `needsSection`,
  `canStart`) walk the catalog. Selecting an upstream value **resets downstream**
  choices so you can't submit a stale level/section. Hidden form inputs mirror the
  state; the "Start" button is the form's `type=submit` and is `:disabled="!canStart"`.

- **`quizPlayer(questions)`** — drives `quiz/show`. Holds `current` (index) and
  `answers` (questionId → letter). Every question stays in the DOM via `x-show`, and
  a hidden `<input :name="answers[id]">` per question means a **single form submit**
  posts the whole map. A question **palette** lets you jump around; cells tint when
  answered. `onSubmit()` `confirm()`s if any question is unanswered (and
  `preventDefault()`s on cancel — the global `data-loading` submit hook in `app.js`
  respects `defaultPrevented`).

The result page draws the score as an SVG ring using `stroke-dasharray` /
`stroke-dashoffset`, themed with `rgb(var(--accent))` / `rgb(var(--surface-muted))`
so it tracks the active theme. All three views use the shared `bg-surface` /
`border-line` / `text-content` / `bg-accent` tokens — no raw colors — so the section
stays themeable. The sidebar "Quiz" nav link (icon `circle-help`, active on
`quiz.*`) already existed in `layouts/app.blade.php`.

New Lucide icons registered for these views: `ArrowRight`, `Languages`, `Cpu`,
`CircleCheck`, `CircleX`, `Award`.

---

## Touching this later

- **Real questions:** swap the Faker placeholders in `ExamSeeder` for real content,
  or build an admin/import path. Keep ≥ `max(counts)` per pool or a 60-question quiz
  on a thin pool will silently cap to what's available (`start` uses
  `total_questions = min(requested, pool size)`).
- **New track / level / section:** edit `config/quiz.php` only — the seeder,
  validation, and wizard all follow. Re-run `migrate:fresh --seed`.
- **Per-section pass marks** (JLPT vs ITPEC differ in reality): `pass_mark` is
  currently one global number; move it into the catalog per level if needed.
- **Resumable attempts across devices:** the question set lives in the session, so an
  in-progress quiz doesn't survive a device switch (it re-draws). Persist the id list
  on the attempt (e.g. a JSON column) if you want true resume.
- **Skipped-question review:** the result page reviews only *answered* questions
  (those are the `user_answers` rows). To review skipped ones too, you'd need the
  full question set persisted (see point above).
