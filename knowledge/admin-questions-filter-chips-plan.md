# Plan: Admin Questions List — Filter/Group Chips (category · level · section)

## Context

The admin questions list (`/admin/questions`, shipped last task) shows a flat paginated list of every quiz question. With many pools (18 pools × up to 60 questions), an admin can't easily see or manage questions for a specific group. Add a **filter toolbar of chips** so the admin can narrow the list by category (JLPT/ITPEC), level (N1–N5, IP, FE) and section (Kanji, Vocab, Grammar, Technology, Strategy), each chip showing a **count** of matching questions.

Because the list is **paginated**, filtering must be **server-side** (query-string params) — client-side Alpine filtering would only filter the current 20-row page. Layout (confirmed with user): **flat — all chips shown at once in grouped rows**. Counts: **yes, badge per chip**.

Reuse the established server-filter idiom from `PostController::index` (`$request->filled()` + `->when()` + `->paginate()->withQueryString()`, `app/Http/Controllers/PostController.php:16-68`) and render chips from the single source of truth `config/quiz.php` catalog.

## Scope — do EXACTLY this, nothing more

**Touch only these 2 files:**
1. `app/Http/Controllers/QuestionController.php` — edit the `manage()` method only.
2. `resources/views/admin/questions/index.blade.php` — add the filter toolbar + empty-state copy tweak only.

**In scope (the whole job):**
- 3 chip rows (Category / Level / Section) with count badges.
- Server-side `->when()` filters on `category` / `level` / `section`, AND-combined, `->withQueryString()`.
- Toggle-off chips + "Clear filters" link + filtered empty-state copy.

**Out of scope — DO NOT do any of these:**
- Do NOT change routes, migrations, models, `config/quiz.php`, `resources/js/app.js`, or run `npm run build`.
- Do NOT touch the create form, store/destroy logic, or the nav.
- Do NOT add edit/update, search box, sort controls, bulk actions, or AJAX — none were requested.
- Do NOT run repo-wide `./vendor/bin/pint` (it reformats unrelated files → churn). Format only the one changed PHP file: `./vendor/bin/pint app/Http/Controllers/QuestionController.php`.
- Do NOT refactor or "clean up" surrounding code. No drive-by edits.
- Do NOT hardcode taxonomy — section chips come from `config('quiz.catalog')`.

If something seems to need a change outside these 2 files, STOP and ask before doing it.

## Ground truth (reuse)

- **`QuestionController::manage()`** currently: `Question::with(['category','level'])->latest()->paginate(20)` (`app/Http/Controllers/QuestionController.php:13-18`).
- **`questions` columns**: `category_id` (FK→`exam_categories.name`), `level_id` (FK→`exam_levels.code`), `section` (nullable string). Indexed on `(category_id, level_id, section)`.
- **Taxonomy** = `config/quiz.php` `catalog`: categories JLPT/ITPEC; level codes N1–N5/IP/FE; section codes kanji/vocab/grammar (JLPT) + technology/strategy (FE); IP has none.
- **Filter preservation across pages**: `->withQueryString()` on the paginator + Laravel's `request()->fullUrlWithQuery([...])` helper for chip links (no Alpine, no new JS, no build step).
- **Existing chip look** lives as scoped CSS in `exams/index.blade.php`; the admin questions page is Tailwind-utility styled, so chips here use inline Tailwind tokens (`bg-accent`, `text-muted`, `border-line`, `bg-surface`) to match its existing markup.

## Changes

### 1. `app/Http/Controllers/QuestionController.php` — `manage(Request $request)`
Add three independent, AND-combined filters + global per-group counts + the taxonomy payload:

```php
public function manage(Request $request)
{
    $questions = Question::with(['category', 'level'])
        ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('name', $request->category)))
        ->when($request->filled('level'),    fn ($q) => $q->whereHas('level', fn ($l) => $l->where('code', $request->level)))
        ->when($request->filled('section'),  fn ($q) => $q->where('section', $request->section))
        ->latest()
        ->paginate(20)
        ->withQueryString();

    // Categories + their levels for the chip rows.
    $categories = ExamCategory::with('levels:id,category_id,code,name')->orderBy('name')->get(['id', 'name']);

    // Flat unique section map from config (kanji,vocab,grammar,technology,strategy) — union keeps first label per code.
    $sections = [];
    foreach (config('quiz.catalog') as $cat) {
        foreach ($cat['levels'] as $lvl) {
            $sections += $lvl['sections'];
        }
    }

    // Global counts per group (total available, not narrowed by current filter — so empty pools stay visible).
    $counts = [
        'category' => Question::selectRaw('category_id, COUNT(*) c')->groupBy('category_id')->pluck('c', 'category_id'),
        'level'    => Question::selectRaw('level_id, COUNT(*) c')->groupBy('level_id')->pluck('c', 'level_id'),
        'section'  => Question::whereNotNull('section')->selectRaw('section, COUNT(*) c')->groupBy('section')->pluck('c', 'section'),
    ];

    return view('admin.questions.index', compact('questions', 'categories', 'sections', 'counts'));
}
```
`ExamCategory` is already imported in this controller. Filtering by `name`/`code` (not id) keeps the URL human-readable (`?category=JLPT&level=N5&section=grammar`) and avoids hardcoding ids.

### 2. `resources/views/admin/questions/index.blade.php` — filter toolbar
Insert a toolbar between the header and the `@forelse`. Three grouped rows of chips; each chip is an `<a>` whose link **merges** its param into the current query (preserving the other active filters) and **resets pagination** (`'page' => null`); clicking an already-active chip **removes** that param (toggle off).

Chip pattern (level row shown; category and section rows follow the same shape):
```blade
{{-- toolbar wrapper --}}
<div class="mb-5 space-y-2">
    {{-- Category row --}}
    <div class="flex flex-wrap items-center gap-1.5">
        <span class="w-16 text-xs font-semibold text-muted">Category</span>
        @foreach ($categories as $cat)
            @php $on = request('category') === $cat->name; @endphp
            <a href="{{ request()->fullUrlWithQuery(['category' => $on ? null : $cat->name, 'page' => null]) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors {{ $on ? 'bg-accent text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
                {{ $cat->name }}
                <span class="rounded-full px-1.5 {{ $on ? 'bg-white/20' : 'bg-surface-muted text-muted' }}">{{ $counts['category'][$cat->id] ?? 0 }}</span>
            </a>
        @endforeach
    </div>
    {{-- Level row: flatten $categories->levels (N1..N5, IP, FE), key count by $lvl->id, link ['level'=>$lvl->code] --}}
    {{-- Section row: loop $sections (code=>label), key count by code, link ['section'=>$code] --}}
</div>
@if (request()->hasAny(['category', 'level', 'section']))
    <a href="{{ route('admin.questions') }}" class="mb-4 inline-flex items-center gap-1 text-xs font-medium text-muted hover:text-content">
        <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear filters
    </a>
@endif
```
- Level row: `@foreach ($categories as $cat) @foreach ($cat->levels as $lvl)` → label `$lvl->code`, count `$counts['level'][$lvl->id] ?? 0`, link `['level' => $lvl->code]`, active when `request('level') === $lvl->code`.
- Section row: `@foreach ($sections as $code => $label)` → label `$label`, count `$counts['section'][$code] ?? 0`, link `['section' => $code]`, active when `request('section') === $code`.
- **Empty-state tweak**: in the existing `@empty` block, branch the copy — if `request()->hasAny(['category','level','section'])` show "No questions match this filter." else keep "No questions yet".

`x` is the only icon used; confirmed already registered in `resources/js/app.js` (`X` imported + in the icons registry, lines 7/29) → renders as-is, no new icon, no rebuild.

### Not touched
- No routes change (`admin.questions` already maps to `manage`).
- No `resources/js/app.js` change, **no `npm run build`** (pure server-side + Tailwind classes already in the compiled CSS; `bg-accent`/`surface-muted`/`line` tokens already used on this page).
- No migration. Counts use the existing `(category_id, level_id, section)` index.

## Verification

1. `./vendor/bin/pint` — clean.
2. `php artisan migrate:fresh --seed` (populate pools), log in as admin, open `/admin/questions`.
3. Toolbar shows 3 rows — Category (JLPT, ITPEC), Level (N1–N5, IP, FE), Section (Kanji, Vocab, Grammar, Technology, Strategy) — each with a count badge. Spot-check a count against `php artisan tinker` (`Question::whereHas('level', fn($q)=>$q->where('code','N5'))->count()`).
4. Click **JLPT** → list narrows, chip active, URL `?category=JLPT`; pagination links keep `?category=JLPT` (`withQueryString`).
5. Add **N5** then **Grammar** → URL `?category=JLPT&level=N5&section=grammar`, all three chips active, list AND-narrowed, page reset to 1.
6. Click an active chip again → its param drops, list widens (toggle off).
7. **Clear filters** link → back to unfiltered `/admin/questions`.
8. Filter to an empty pool (e.g. ITPEC + N5, an impossible combo the flat layout allows) → `@empty` shows "No questions match this filter."
9. Confirm filters survive page 2 navigation and the delete button still works within a filtered view.

## Notes / non-goals
- Counts are **global per group** (total questions in that category/level/section), not recomputed against the other active filters — intentional, so the admin can still see and jump to thin/empty pools.
- Flat layout permits nonsensical combos (JLPT + technology) that return empty — acceptable per chosen layout; the empty-state copy covers it.
- `config/quiz.php` stays the single source of truth for the section chips — do not hardcode section codes in the controller or view.
