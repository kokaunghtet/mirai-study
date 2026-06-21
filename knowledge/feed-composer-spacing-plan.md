# Plan: Feed post-composer — tighten spacing / UI-UX (create + edit)

## Context

The post-composer pages — `resources/views/feed/create.blade.php` and
`resources/views/feed/edit.blade.php` — feel airy and adrift. In the create
screenshot the card is pushed off-centre with large empty gutters, a floating
**Back** button sits at the far top-left detached from the card, and the internal
field spacing is loose and uneven.

**Root cause — double centring + double padding.** The app layout shell already
centres and pads page content:

```
resources/views/layouts/app.blade.php:337
<main class="mx-auto py-8 ... max-w-6xl"> {{ $slot }} </main>   (max-w-7xl when sidebar collapsed)
```

`create.blade.php` then re-centres *inside* that ~1152px area with its own
`flex justify-center px-4 py-6` wrapper and a `max-w-[560px]` card. Result: the card
floats in a much wider container (asymmetric gutters) with a doubled top gap. The
Back `<a>` (`create.blade.php:3-7`) is rendered **outside** the centring wrapper, so
it lands at the far top-left of the wide `<main>`, visually orphaned.

Internally the padding scale is inconsistent — `px-[18px]`, `py-4`, `py-3.5`,
`pb-3`, `pb-2.5` mixed — and the title/tags rows have no grouping or label.

**Decision already taken (user):** remove the floating Back button; rely on the
header **✕** close (already present in both files; create's ✕ → feed, edit's ✕ →
post). This matches the rest of the app — `settings/index.blade.php` and
`profile/edit.blade.php` have no Back button.

**Goal:** a tighter, consistently-spaced composer that aligns to the shell instead
of fighting it, following the existing centred-form convention
(`profile/edit.blade.php:4` → `max-w-[600px] mx-auto`), staying fully themeable.

## Scope — do EXACTLY this, nothing more

**Touch only these files:**
1. `resources/views/feed/create.blade.php` — remove Back, rewrap, repad, Tags label, textarea height.
2. `resources/views/feed/edit.blade.php` — rewrap, repad, Tags label. (No Back button exists here; already ✕-only.)

**In scope (the whole job):**
- Delete the orphan Back link and the double-centring/padding wrapper.
- Align the card to a single `max-w-[600px] mx-auto` column.
- Standardise horizontal padding and vertical rhythm across both cards.
- Add a small "Tags" section label; give the create textarea more presence.

**Out of scope — DO NOT:**
- Do NOT touch `resources/js/app.js`, the Alpine components (`postComposer()` /
  `editComposer()`), or any `name="..."` form field — submit behaviour must stay
  byte-identical (no controller/route/validation impact).
- Do NOT change `PostController`, `routes/web.php`, migrations, or models.
- Do NOT introduce new components, raw hex colours, or non-token utilities — use
  only the existing semantic tokens (`surface`, `surface-muted`, `canvas`,
  `content`, `muted`, `line`, `accent`, `accent-strong`, `accent/15` …).
- Do NOT redesign the tabs, media carousel, file chips, or remove-checkboxes —
  spacing/layout only.
- No drive-by refactors beyond the spacing changes described below.
- If anything seems to need a change outside these 2 files, STOP and ask.

## Ground truth (reuse)

- **Centred-form convention** already exists: `profile/edit.blade.php:4`
  `<div class="max-w-[600px] mx-auto space-y-5 py-2">` — mirror its width + `mx-auto`.
- **Card chrome** is already consistent app-wide:
  `rounded-2xl border border-line bg-surface shadow-sm overflow-hidden` — keep as-is.
- **Uppercase-muted section label** pattern already used in
  `edit.blade.php:89` → `text-[11px] font-semibold text-muted uppercase tracking-wide`
  — reuse verbatim for the new "Tags" caption (both files).
- **Global `[x-cloak]`** is already defined (`resources/css/app.css:174` + the
  `<head>` style in `app.blade.php`) — Alpine panels won't flash; no setup needed.
- The shell `<main>` already supplies `py-8`, so the card needs **no** vertical
  page padding of its own.

## Changes

### 1. Page wrapper (both files)
- `create.blade.php`: delete the standalone Back `<a>` (lines 3-7) entirely.
- Both: collapse the two nested wrappers
  `<div class="flex justify-center px-4 py-6"><div class="w-full max-w-[560px]">`
  into one column → `<div class="mx-auto max-w-[600px] px-4">`. Drop the `py-6`
  (shell `py-8` covers it). Keep `px-4` for mobile gutters. Card div unchanged.

### 2. Standardise horizontal padding
- Replace every `px-[18px]` → `px-5` (20px) across both files — header, author, tabs
  nav, content `<section>`, title, tags, footer. Mechanical per-file replace-all.

### 3. Unify vertical rhythm
- Author section `py-3.5` → `py-3`.
- Keep a single uniform `pb-3` on tabs nav, content `<section>`, title block, and
  tags block so no neighbour is tighter/looser. Footer keeps `py-4 border-t border-line`.

### 4. Title + Tags polish
- Add a "Tags" caption above the checkbox row using the reused label pattern
  (`text-[11px] font-semibold text-muted uppercase tracking-wide mb-2`) — both files.
- Title stays the subtle bottom-border optional input, just inside the `px-5` column.

### 5. Create textarea presence
- `create.blade.php:72` textarea `min-h-[60px]` → `min-h-[88px]` (still one field,
  more inviting). Leave `edit.blade.php`'s `min-h-[90px]` as-is.

## Verification

1. `npm run build` (or `composer dev` for live Vite) — must build clean.
2. `/posts/create`: card centred in its column, no floating Back, even gutters, no
   doubled top gap; header ✕ → feed; padding consistent top-to-bottom; Tags row
   labelled; Text/Media/File tabs switch; media carousel + file chips work; Publish
   creates a post (content required).
3. `/posts/{post}/edit`: same tightened layout; existing media/files render;
   remove-checkboxes work; Save persists.
4. Settings → switch to a non-default accent (e.g. Aurora) + dark mode → composer
   recolours via tokens, no hardcoded colour leaks.
