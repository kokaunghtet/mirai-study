# Plan: Admin — Add Quiz Questions (cascading taxonomy picker)

## Context

Quizzes already work end-to-end (player, grading, history), but the **question bank is only fillable by seeders** (`ExamSeeder` placeholders + `FeQuestionSeeder` text-file import). `QuestionController` is an empty stub and there is no admin UI to add questions by hand. Admins need a form to author one question at a time, choosing where it lands in the taxonomy:

```
JLPT  → N1..N5 → {Kanji, Vocabulary, Grammar}
ITPEC → IP      → (no section, level-only)
        FE      → {Technology, Strategy}
```

Outcome: a new **/admin/questions** area (list + create + delete) mirroring the existing **/admin/papers** admin CRUD, with a cascading category → level → section picker driven by the single source of truth `config/quiz.php`.

Scope (confirmed with user): **Add + list + delete**. No edit/update.

## Ground truth (already exists — reuse, don't reinvent)

- **`Question` model** `app/Models/Question.php` — `$fillable`: `category_id, level_id, section, text, option_a, option_b, option_c, option_d, answer, explanation`. `answer` is enum `A|B|C|D`. `belongsTo` ExamCategory/ExamLevel; `scopePool()`.
- **Taxonomy source of truth** = `config/quiz.php` `catalog` (category name → levels → sections). JLPT sections = kanji/vocab/grammar for every level; ITPEC IP = `[]` (no section); ITPEC FE = technology/strategy. `QuizController::start()` (lines ~50-75) already validates the cascade against this config — mirror that.
- **DB**: `exam_categories(id,name)`, `exam_levels(id,category_id,code,name)`. Codes: N1..N5, IP, FE. `questions.section` is a nullable string code.
- **Template to copy**: `ExamPaperController::manage/create/store/destroy` + `resources/views/admin/papers/{index,create}.blade.php` + `paperUploader` Alpine (`resources/js/app.js` ~622) + the admin route group (`routes/web.php:126-138`) + `EnsureUserIsAdmin` gating.

## Changes

### 1. `app/Http/Controllers/QuestionController.php` (fill the stub)
Add `use` for `ExamCategory`, `ExamLevel`, `Question`, `Illuminate\Validation\Rule`.

- **`manage()`** — `Question::with(['category','level'])->latest()->paginate(20)` → `view('admin.questions.index', ...)`.
- **`create()`** — build a self-contained cascade payload: DB categories+levels merged with config sections, so the Alpine component needs no second lookup:
  ```php
  $catalog = config('quiz.catalog');
  $categories = ExamCategory::with('levels:id,category_id,code,name')
      ->orderBy('name')->get(['id','name'])
      ->each(fn ($cat) => $cat->levels->each(function ($lvl) use ($catalog, $cat) {
          $lvl->sections = $catalog[$cat->name]['levels'][$lvl->code]['sections'] ?? [];
      }));
  return view('admin.questions.create', compact('categories'));
  ```
  Payload shape to JS: `[{id,name,levels:[{id,code,name,sections:{kanji:'Kanji',...}}]}]`.
- **`store()`** — validate base fields, then validate `section` against config for the chosen category+level (mirror `QuizController::start`):
  ```php
  $data = $request->validate([
      'category_id' => ['required','exists:exam_categories,id'],
      'level_id'    => ['required', Rule::exists('exam_levels','id')->where('category_id',$request->category_id)],
      'section'     => ['nullable','string','max:255'],
      'text'        => ['required','string'],
      'option_a'    => ['required','string'],
      'option_b'    => ['required','string'],
      'option_c'    => ['required','string'],
      'option_d'    => ['required','string'],
      'answer'      => ['required', Rule::in(['A','B','C','D'])],
      'explanation' => ['nullable','string'],
  ]);
  $cat = ExamCategory::find($data['category_id']);
  $lvl = ExamLevel::find($data['level_id']);
  $valid = array_keys(config("quiz.catalog.{$cat->name}.levels.{$lvl->code}.sections", []));
  if ($valid) {                       // JLPT levels + FE → section required & must match
      $request->validate(['section' => ['required', Rule::in($valid)]]);
  } else {                            // IP → level-only
      $data['section'] = null;
  }
  Question::create($data);
  return redirect()->route('admin.questions')->with('success','Question added.');
  ```
  (`config()` keys "JLPT"/"ITPEC" + codes contain no dots, safe for dot-path.)
- **`destroy(Question $question)`** — `$question->delete();` → redirect `admin.questions` with `success` flash. (questions are NOT soft-deleted — hard delete; FK on `user_answers.question_id` is `cascadeOnDelete`, so past attempts referencing it are pruned. Acceptable for admin authoring; note in PR.)

### 2. `routes/web.php` — admin group (after the paper routes, before the group closes at line 138)
```php
// Quiz question management
Route::get('/questions',          [QuestionController::class, 'manage'])->name('questions');
Route::get('/questions/create',   [QuestionController::class, 'create'])->name('questions.create');
Route::post('/questions',         [QuestionController::class, 'store'])->name('questions.store');
Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
```
Ensure `use App\Http\Controllers\QuestionController;` is present at top.

### 3. `resources/js/app.js` — add `questionForm` Alpine component (near `paperUploader`/`quizSetup`)
```js
Alpine.data('questionForm', (cats = []) => ({
    cats,
    categoryId: '', levelId: '', section: '', answer: '',
    init() {
        const d = this.$root.dataset;
        this.categoryId = d.oldCategory || '';
        this.levelId    = d.oldLevel || '';
        this.section    = d.oldSection || '';
        this.answer     = d.oldAnswer || '';
    },
    get levels()  { return this.cats.find(c => c.id == this.categoryId)?.levels ?? []; },
    get sections(){ return this.levels.find(l => l.id == this.levelId)?.sections ?? {}; },
    get needsSection() { return Object.keys(this.sections).length > 0; },
    onCategoryChange() { this.levelId = ''; this.section = ''; },
    onLevelChange()    { this.section = ''; },
}));
```
Run `npm run build` after (admin upload form proves assets are bundled, not CDN).

### 4. Views — `resources/views/admin/questions/` (mirror `admin/papers/`)
- **`index.blade.php`** — `<x-app-layout>`; header "Manage Questions" + a "New question" button → `admin.questions.create`; `session('success')` flash box; list rows each showing a `category · level · section` badge, truncated `text`, the correct-answer letter, and a delete button (`confirm('Delete this question?')`, `@method('DELETE')` form to `admin.questions.destroy`); empty state; `$questions->links()`.
- **`create.blade.php`** — `<x-app-layout>`; form `method="POST"` → `admin.questions.store`, `x-data="questionForm(@js($categories))"`, `@csrf`, with `data-old-*` attributes (`old-category/level/section/answer`) for repopulation. Fields:
  - **Category** select `name="category_id" x-model="categoryId" @change="onCategoryChange"` — options from `$categories`.
  - **Level** select `name="level_id" x-model="levelId" :disabled="!categoryId" @change="onLevelChange"` — `<template x-for="l in levels">`.
  - **Section** select `name="section" x-model="section" x-show="needsSection"` — `<template x-for="[code,label] in Object.entries(sections)">`; hidden + cleared for IP.
  - **Question text** textarea `name="text"`.
  - **Options A–D**: four text inputs `name="option_a..d"`, each with a radio `name="answer" value="A".."D" x-model="answer"` beside it so the admin marks the correct option inline (binds the enum without a separate dropdown).
  - **Explanation** textarea `name="explanation"` (optional).
  - Cancel (→ `admin.questions`) + Submit buttons. Reuse `<x-input-label>`, `<x-input-error>`, `<x-text-input>` and the same Tailwind tokens (`bg-surface`, `border-line`, `text-content`) as `admin/papers/create.blade.php`.

### 5. `resources/views/layouts/app.blade.php` — admin nav
Add a "Manage Questions" link inside the existing `@if (auth()->user()->isAdmin())` block (right after the Manage Papers link, ~line 200), icon `list-checks` (or `clipboard-list`), route `admin.questions`, active on `request()->routeIs('admin.questions*')`. Copy the Manage Papers `<a>` markup verbatim and swap href/route/icon/label.

## Verification

1. `./vendor/bin/pint` (format) and `npm run build` (bundle the new Alpine component) — both clean.
2. Ensure DB seeded: `php artisan migrate:fresh --seed` (creates categories/levels). Log in as the seeded admin user.
3. Sidebar shows **Manage Questions** (admin only) → `/admin/questions` lists existing seeded questions, paginated.
4. **Create — JLPT path**: New question → JLPT → N5 → section select appears → pick Grammar, fill text + 4 options, mark correct radio, submit → redirect to list with "Question added." flash; new row shows `JLPT · N5 · grammar`.
5. **Create — ITPEC IP (level-only)**: ITPEC → IP → section select hidden; submit → row saved with section null.
6. **Create — ITPEC FE**: ITPEC → FE → Technology → submit ok.
7. **Validation**: submit JLPT level with section omitted → "section required" error, form repopulates (old values restored via `data-old-*`). Submit with no `answer` → error.
8. **Delete**: delete a row → confirm dialog → row gone, flash shown.
9. **End-to-end into quiz**: start a quiz for the pool you just added to (e.g. JLPT N5 Grammar) and confirm the new question can surface and grade correctly.
10. `php artisan test` — the trustworthy auth/theme tests still pass (the known-stale Breeze tests remain unaffected).

## Notes / non-goals
- No edit/update, no bulk import (text-file import already covered by `FeQuestionSeeder`).
- Keep config `quiz.php` as the single source of truth — do **not** hardcode sections in JS or the controller.
- Maintain `routes/web.php` static-before-wildcard ordering (these are all static `/admin/questions...` paths inside the admin group, so no conflict).
