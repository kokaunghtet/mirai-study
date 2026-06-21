# Plan: Admin Questions — Edit/Update existing questions

## Context

The admin questions area currently supports **add + list + delete + filter** (`QuestionController` `manage`/`create`/`store`/`destroy`, views in `resources/views/admin/questions/`). There is **no edit** — fixing a typo in a question or option means deleting and re-adding, which burns the question's `id` and cascade-prunes any `user_answers` tied to it (`user_answers.question_id` is `cascadeOnDelete`). Add **edit-in-place**.

Cost is low: the create form already exists and the `questionForm` Alpine component already restores state from `data-old-*` attributes, so edit reuses almost everything. The only real work is wiring an `edit`/`update` controller pair, two routes, an Edit button on each row, and making the form serve both create and edit.

## Scope — do EXACTLY this, nothing more

**Touch only these files:**
1. `app/Http/Controllers/QuestionController.php` — add `edit()` + `update()`; extract the shared validate-and-resolve-section logic into a private method that `store()` also calls.
2. `routes/web.php` — add 2 routes inside the existing `admin` group (next to the other `questions` routes).
3. `resources/views/admin/questions/index.blade.php` — add an Edit link on each list row (before the Delete button).
4. `resources/views/admin/questions/_form.blade.php` — **new** partial: the shared create/edit form.
5. `resources/views/admin/questions/create.blade.php` — refactor to include the `_form` partial (behaviour unchanged).
6. `resources/views/admin/questions/edit.blade.php` — **new** page that includes `_form` in edit mode.

**In scope (the whole job):**
- Edit button per row → pre-filled form → save updates the same row in place.
- Identical validation + section rules as create (config-driven).

**Out of scope — DO NOT:**
- Do NOT change `resources/js/app.js` (the `questionForm` Alpine component already handles edit via `data-old-*`), and **no `npm run build`** (no JS, no new icon — `SquarePen` is already registered).
- Do NOT touch migrations, models, `config/quiz.php`, the filter chips, the nav, or `destroy`.
- Do NOT add soft-deletes, versioning, audit log, or bulk edit — not requested.
- Do NOT run repo-wide `./vendor/bin/pint`. Format only the one changed PHP file: `./vendor/bin/pint app/Http/Controllers/QuestionController.php`.
- No drive-by refactors beyond the one validation-method extraction described below.
- If anything seems to need a change outside these 6 files, STOP and ask.

## Ground truth (reuse)

- **`store()`** already holds the exact validation + config section-resolution logic to reuse (`app/Http/Controllers/QuestionController.php`).
- **`create()`** already builds the `$categories` payload (DB categories+levels merged with config sections) the form needs — `edit()` uses the same builder.
- **`questionForm` Alpine** (`resources/js/app.js`) reads `data-old-category/level/section/answer` on `init()` and drives the cascade — works unchanged for edit if those attrs are seeded with the question's current values.
- **`SquarePen`** icon is already imported + registered in `resources/js/app.js` → `data-lucide="square-pen"` renders, no rebuild.
- **Route model binding**: `{question}` already used by `destroy` — reuse for `edit`/`update`.

## Changes

### 1. `QuestionController.php`
Extract the shared logic, add `edit` + `update`:

```php
public function edit(Question $question)
{
    $catalog = config('quiz.catalog');
    $categories = ExamCategory::with('levels:id,category_id,code,name')
        ->orderBy('name')->get(['id', 'name'])
        ->each(fn ($cat) => $cat->levels->each(function ($lvl) use ($catalog, $cat) {
            $lvl->sections = $catalog[$cat->name]['levels'][$lvl->code]['sections'] ?? [];
        }));

    return view('admin.questions.edit', compact('categories', 'question'));
}

public function update(Request $request, Question $question)
{
    $question->update($this->validatedQuestion($request));

    return redirect()->route('admin.questions')->with('success', 'Question updated.');
}

// Shared by store() and update() — validates and resolves the section per config.
private function validatedQuestion(Request $request): array
{
    $data = $request->validate([
        'category_id' => ['required', 'exists:exam_categories,id'],
        'level_id' => ['required', Rule::exists('exam_levels', 'id')->where('category_id', $request->category_id)],
        'section' => ['nullable', 'string', 'max:255'],
        'text' => ['required', 'string'],
        'option_a' => ['required', 'string'],
        'option_b' => ['required', 'string'],
        'option_c' => ['required', 'string'],
        'option_d' => ['required', 'string'],
        'answer' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        'explanation' => ['nullable', 'string'],
    ]);

    $cat = ExamCategory::find($data['category_id']);
    $lvl = ExamLevel::find($data['level_id']);
    $valid = array_keys(config("quiz.catalog.{$cat->name}.levels.{$lvl->code}.sections", []));

    if ($valid) {
        $request->validate(['section' => ['required', Rule::in($valid)]]);
    } else {
        $data['section'] = null;
    }

    return $data;
}
```
Then **refactor `store()`** to reuse it (behaviour identical):
```php
public function store(Request $request)
{
    Question::create($this->validatedQuestion($request));

    return redirect()->route('admin.questions')->with('success', 'Question added.');
}
```

### 2. `routes/web.php` (admin group, beside the other question routes)
```php
Route::get('/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
```
Keep static `/questions/create` before the `/questions/{question}/edit` wildcard (it already is).

### 3. `index.blade.php` — Edit link per row
In the row actions (the `<li>`, before the existing delete `<form>`), add:
```blade
<a href="{{ route('admin.questions.edit', $question) }}" title="Edit question"
   class="inline-flex items-center gap-1.5 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
    <i data-lucide="square-pen" class="h-4 w-4"></i>
    <span class="hidden sm:inline">Edit</span>
</a>
```
Wrap the Edit + Delete in a `flex items-center gap-2` container so they sit side by side.

### 4. `_form.blade.php` (new) — shared form
Move the existing form body out of `create.blade.php` into this partial. Parameterize with `$action`, `$method` (`POST`/`PUT`), and a nullable `$question`. Seed every value with `old('field', $question?->field)` so it works for both modes:
- `x-data="questionForm(@js($categories))"`, `@csrf`, and `@if (($method ?? 'POST') === 'PUT') @method('PUT') @endif`.
- `data-old-category="{{ old('category_id', $question?->category_id) }}"`, `data-old-level="{{ old('level_id', $question?->level_id) }}"`, `data-old-section="{{ old('section', $question?->section) }}"`, `data-old-answer="{{ old('answer', $question?->answer) }}"`.
- Text/options/explanation: `{{ old('text', $question?->text) }}`, `value="{{ old('option_a', $question?->option_a) }}"`, etc.
- Answer radios: keep `x-model="answer"` and add `@checked(old('answer', $question?->answer) === $letter)` for the no-JS first paint.
- Submit button label: `{{ $submitLabel ?? 'Add Question' }}`.

### 5. `create.blade.php` — include the partial
Replace the inline `<form>…</form>` with:
```blade
@include('admin.questions._form', [
    'action' => route('admin.questions.store'),
    'method' => 'POST',
    'question' => null,
    'submitLabel' => 'Add Question',
])
```
Keep the page header/layout wrapper. Verify create still works after the refactor.

### 6. `edit.blade.php` (new)
Same `<x-app-layout>` shell as create, header "Edit Question", then:
```blade
@include('admin.questions._form', [
    'action' => route('admin.questions.update', $question),
    'method' => 'PUT',
    'question' => $question,
    'submitLabel' => 'Save changes',
])
```

## Verification

1. `./vendor/bin/pint app/Http/Controllers/QuestionController.php` — clean. (No `npm run build` — no JS change.)
2. `php artisan route:list --name=admin.questions` → shows `questions.edit` (GET) + `questions.update` (PUT).
3. Log in as admin → `/admin/questions`. Each row now shows **Edit** + **Delete**.
4. **Create still works** (regression): add a new JLPT/N5/Grammar question → saved. (Confirms the `_form` extraction didn't break create.)
5. **Edit JLPT path**: click Edit on a JLPT question → form pre-filled (category JLPT, level, section, all 4 options, correct radio, explanation). Change the text → Save → redirected to list, "Question updated." flash, same row updated, **id unchanged** (`php artisan tinker` to confirm id stable).
6. **Edit ITPEC IP**: section select hidden, save works (section stays null).
7. **Edit ITPEC FE**: change section Technology→Strategy → saves.
8. **Validation**: clear the question text → Save → error, form repopulates with edited values (via `old(..., $question?->...)`).
9. Quiz still draws the edited question correctly for its pool.

## Notes / non-goals
- `update()` reuses the exact create validation via `validatedQuestion()` — section rules stay config-driven (`config/quiz.php` single source of truth).
- No change to delete/filter/nav/JS. Edit is additive except the one DRY extraction in the controller and the form-partial refactor.
