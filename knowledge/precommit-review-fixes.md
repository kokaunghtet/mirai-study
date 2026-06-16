# Pre-Commit Review — Findings & Fixes (2026-06-16)

A correctness/security/performance pass over the uncommitted diff before pushing
(theme-mode live-sync work + the profile-edit redesign). Five issues were found
and fixed. No high-severity security or performance problems: all AJAX endpoints
carry CSRF tokens and sit behind `auth`, `checkUsername` is authed and excludes
the caller's own id, image deletion derives paths from app-controlled URLs (not
user input), output is escaped (`@js` / `x-text`), and no N+1 or blocking work
was introduced.

Severity legend: 🟠 Medium · 🟡 Low · ⚪ Info.

---

## #1 🟠 Username validation locked existing users out of *all* profile edits

**File:** `app/Http/Controllers/ProfileController.php` — `update()`

**Cause.** The profile-edit redesign tightened the username rule from the old
`max:50|alpha_dash` (allowed `_`, `-`, uppercase, ≤50 chars) to
`min:3|max:30|regex:/^[a-z0-9]+$/`. The edit form **pre-fills the current
username**, so it is submitted on every save — even when the user only changed
their bio. Laravel validates every field every time, so a user whose *existing*
handle predates the new rule (e.g. `legacy_user`, `John.Doe`, or a >30-char name)
fails validation on their own unchanged username and **cannot save anything**.

**Confirmed real:** a DB query found **14 users** with usernames that violate
`^[a-z0-9]{3,30}$` (legacy/seeded handles with dots, underscores, etc.). Every
one was locked out of profile updates.

**Fix — grandfather the unchanged handle.** Only enforce the strict *format*
when the username is actually being changed; otherwise just require it and keep
it unique (trivially satisfied by the user's own row). Combined with #2, the
input is lowercased first so the "unchanged?" comparison is case-insensitive and
the strict path matches registration behaviour.

```php
$user = $request->user();

if ($request->filled('username')) {
    $request->merge(['username' => strtolower($request->input('username'))]);
}

$usernameRules = ['required', 'string', 'unique:users,username,'.$user->id];
if ($request->input('username') !== $user->username) {
    $usernameRules = array_merge($usernameRules, ['min:3', 'max:30', 'regex:/^[a-z0-9]+$/']);
}

$validated = $request->validate([
    'display_name' => 'required|string|max:255',
    'username'     => $usernameRules,
    // ...rest unchanged
]);
```

**Verified** (framework-booted check against a `legacy_user` row):

| Submitted | Result |
| --- | --- |
| `legacy_user` (unchanged) | ALLOWED ✓ |
| `bad_name` (changed, invalid) | REJECTED ✓ |
| `johndoe2` (changed, valid) | ALLOWED ✓ |

**Rejected alternatives:** a data migration that rewrites the 14 handles (changes
people's profile URLs and `@mentions`, risks collisions); reverting to the loose
old rule (re-introduces the inconsistency with registration the redesign removed).

---

## #2 🟡 `update()` did not lowercase the username server-side

**File:** `app/Http/Controllers/ProfileController.php` — `update()`

**Cause.** `checkUsername()` lowercases its input, but `update()` saved
`$validated['username']` as typed and relied on the regex to *reject* uppercase.
The UI lowercases via JS, so the normal path was fine, but a JS-disabled or direct
request with `JohnDoe` got a 422 instead of being normalised — inconsistent with
both `checkUsername()` and registration (which lowercase).

**Fix.** `strtolower()` the username before comparing/validating/saving — folded
into the #1 `$request->merge([...])` line above. Now uppercase input normalises
instead of erroring, and matches the canonical lowercase handle everywhere.

---

## #3 🟡 First-paint theme script could abort if `localStorage` throws

**File:** `resources/views/layouts/app.blade.php` — inline `<head>` script

**Cause.** `localStorage.getItem`/`setItem` can throw when storage is unavailable
(Safari Lockdown mode, blocked cookies, zero quota). The IIFE had no `try/catch`,
so a throw would skip the line below it —
`document.documentElement.classList.toggle('dark', dark)` — leaving the theme
unresolved (flash of the wrong appearance). The newly added `setItem` (localStorage
seeding) added a second throw point.

**Fix.** Wrap each storage access in `try/catch`; on failure fall back to the
server-rendered `@json($themeMode)` value and still run the dark-mode resolution.

```js
var stored = null;
try { stored = localStorage.getItem('themeMode'); } catch (e) {}
var mode = stored || @json($themeMode);
if (!stored) { try { localStorage.setItem('themeMode', mode); } catch (e) {} }
var dark = mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
document.documentElement.classList.toggle('dark', dark);
```

---

## #4 🟡 Compiled `.pyc` bytecode caches were tracked for commit

**Files:** `.claude/skills/ui-ux-pro-max/scripts/__pycache__/*.pyc`

**Cause.** Python bytecode caches were committed earlier and kept showing up as
modified (1-byte churn) with no `.gitignore` rule. They are build artifacts with
no source value and cause merge noise.

**Fix.** Added `__pycache__/` and `*.pyc` to `.gitignore`, and untracked the
existing files:

```bash
git rm -r --cached .claude/skills/ui-ux-pro-max/scripts/__pycache__/
```

The files stay on disk (Python regenerates them); they're just no longer tracked.

---

## #5 ⚪ `theme-mode-changed` listener was never detached

**File:** `resources/js/app.js` — `themeToggle` Alpine component

**Cause.** `init()` added a `window` listener with no matching removal. Harmless
for this full-page-navigation app (the JS context resets per load), but it would
accumulate if the component were ever re-initialised (SPA-style navigation).

**Fix.** Store the handler as a named property and detach it in Alpine's
`destroy()` lifecycle hook:

```js
init() {
    this._onThemeModeChanged = (e) => { /* ...sync this.dark... */ };
    window.addEventListener('theme-mode-changed', this._onThemeModeChanged);
},
destroy() {
    window.removeEventListener('theme-mode-changed', this._onThemeModeChanged);
},
```

The module-scope listeners in `app.js` (the `effectiveThemeMode` updater and the
`matchMedia` change listener) are intentionally page-lifetime singletons and need
no teardown.

---

## Verification performed

- `./vendor/bin/pint app/Http/Controllers/ProfileController.php` → passed
- `php -l` on the controller → no syntax errors
- `npm run build` → clean
- `php artisan test --filter="ThemeModeTest|OtpFlowTest|AuthenticationTest|RegistrationTest"`
  → **21 passed, 88 assertions**
- Framework-booted validation check for #1 → all three cases correct (table above)

## Files changed by these fixes

- `app/Http/Controllers/ProfileController.php` (#1, #2)
- `resources/views/layouts/app.blade.php` (#3)
- `.gitignore` + untracked `__pycache__` (#4)
- `resources/js/app.js` (#5)

See [[themetoggleasync]] for the broader theme-mode sync design these fixes build on.
