# Plan: Fix — Edit form Level & Section not auto-selected

## Context

On the admin **Edit Question** page (`/admin/questions/{question}/edit`), opening a question pre-fills the **Category** correctly but leaves **Level** (and **Section**, when the level has one) on the placeholder "Select a level…" instead of the question's saved values. The admin must re-pick them every edit.

### Root cause (confirmed)

`questionForm` Alpine component (`resources/js/app.js:734-749`) sets all fields synchronously in `init()`:

```js
init() {
    const d = this.$root.dataset;
    this.categoryId = d.oldCategory || '';
    this.levelId    = d.oldLevel || '';   // <- set before options exist
    this.section    = d.oldSection || '';  // <- set before options exist
    this.answer     = d.oldAnswer || '';
}
```

The **Category** `<select>` has **static** Blade `<option>`s (already in the DOM), so `x-model="categoryId"` matches immediately → works.

The **Level** and **Section** `<select>`s get their `<option>`s from `<template x-for="lvl in levels">` / `x-for="[code,label] in Object.entries(sections)"`, which depend on `categoryId` / `levelId`. At `init()` time those options have **not rendered yet**, so binding `levelId`/`section` to a select with no matching `<option>` is dropped by the browser, and Alpine does not re-sync once `x-for` fills the options a tick later. → Level/Section show the placeholder.

This is the same render-timing class as the earlier paperUploader session-dropdown fix (claude-mem obs 617). The fix is to assign the dependent values **after** their options render, via `$nextTick` chaining.

## Scope — do EXACTLY this, nothing more

**Touch only this file:**
1. `resources/js/app.js` — rewrite the `questionForm` `init()` only (lines 737-743).

Then rebuild assets: `npm run build`.

**Out of scope — DO NOT:**
- Do NOT change the controller, routes, Blade views (`_form`/`create`/`edit`/`index`), or `config/quiz.php`. The `data-old-*` attributes and `x-model` bindings are already correct — only the JS init timing is wrong.
- Do NOT touch any other Alpine component (`paperUploader`, `quizSetup`, etc.).
- Do NOT run repo-wide `./vendor/bin/pint` (no PHP changed anyway).
- No new dependencies, no refactors beyond `init()`.
- If the fix appears to need a change outside `app.js`, STOP and ask.

## Change

### `resources/js/app.js` — `questionForm.init()`
Set `categoryId` synchronously, then set `levelId` after the level options render, then `section` after the section options render:

```js
init() {
    const d = this.$root.dataset;
    this.answer = d.oldAnswer || '';
    this.categoryId = d.oldCategory || '';

    // Level options are rendered by x-for off `categoryId`; wait one tick so the
    // <option> exists before x-model can select it. Section depends on `levelId`
    // the same way, so nest a second tick.
    this.$nextTick(() => {
        this.levelId = d.oldLevel || '';
        this.$nextTick(() => {
            this.section = d.oldSection || '';
        });
    });
}
```

Notes:
- Leave the getters (`levels`, `sections`) and `onCategoryChange`/`onLevelChange` unchanged — they already use loose `==`, so number(option)/string(dataset) compare fine.
- `answer` and `categoryId` can stay synchronous (radios + static options already in the DOM).
- This also fixes the **create** form's repopulation after a failed validation submit (it uses the same `data-old-*` + `init()` path, so Level/Section were silently dropping there too).

## Verification

1. `npm run build` — succeeds (JS change requires a rebuild; no new icon).
2. `php artisan migrate:fresh --seed`, log in as admin → `/admin/questions`.
3. **Edit JLPT question** (e.g. N5 · Grammar): click Edit → Category=JLPT, **Level=N5 pre-selected**, **Section=Grammar pre-selected**, correct answer radio + options + explanation filled.
4. **Edit ITPEC FE** (Technology/Strategy): Level=FE and Section pre-selected.
5. **Edit ITPEC IP** (level-only): Level=IP pre-selected, Section row hidden (no section), saves fine.
6. Change nothing → Save → values persist unchanged (no accidental null section).
7. **Create still works**: add a new question from blank → cascade behaves, no stuck placeholder.
8. **Validation repopulation**: on the create form submit with a missing field → after the error, Level/Section retain the chosen values (regression now also fixed).

## Notes / non-goals
- Pure front-end timing fix; no data-model or server change.
- Keeps `data-old-*` as the single mechanism feeding the cascade for both create and edit.
