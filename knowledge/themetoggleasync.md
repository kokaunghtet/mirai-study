# Theme Mode — Live Sync Between the Sidebar Toggle and the Settings Page

How light/dark mode stays consistent across the two places a user can change it,
and the bug history that shaped the current design.

## The two controls

MiraiStudy lets a user change light/dark mode in **two** places, and both are
visible at the same time on the Settings page:

1. **Sidebar quick toggle** — `resources/views/layouts/app.blade.php` (the
   moon/sun button at the bottom of the sidebar). It's driven by the Alpine
   component `themeToggle` in `resources/js/app.js`.
2. **Settings segmented control** — `resources/views/settings/index.blade.php`
   (the Light / Dark / System buttons). Driven by the page's own inline
   `<script>` (plain JS, not Alpine).

There is a third, non-interactive actor:

3. **First-paint `<head>` script** — in `layouts/app.blade.php`, runs *before*
   CSS/JS load. It resolves dark mode and toggles the `.dark` class on `<html>`
   so there's no flash of the wrong theme. It also **seeds `localStorage` from the
   server preference when `localStorage` is empty** (see Bug 4), so every later
   reader has a concrete value.

## Sources of truth

Light/dark mode is represented in three layers, in priority order at first paint:

| Layer | Holds | Read by | Written by |
| --- | --- | --- | --- |
| `localStorage['themeMode']` | `'light' \| 'dark' \| 'system'` | first-paint script (**first**), settings init | sidebar toggle, settings on Save |
| DB `user_preferences.theme_mode` | same enum | first-paint script (fallback), settings server render | sidebar toggle (async), settings on Save |
| `.dark` class on `<html>` | binary (dark or not) | sidebar `themeToggle` init | first-paint script, sidebar toggle, settings live-preview |

**Key rule:** the first-paint script trusts `localStorage` **over** the DB. So
anything that changes the mode must keep `localStorage` up to date, or the change
will visually revert on the next reload.

> Security note: only non-sensitive UI preferences (the mode string) live in
> `localStorage`. It's readable by any same-origin JS and is treated as untrusted
> input — every consumer either string-compares it or normalizes it against the
> `['light','dark','system']` whitelist. Never store tokens/secrets there.

## Bug history (why the code looks the way it does)

### Bug 1 — Settings page ignored localStorage entirely

Originally the settings page read `theme_mode` only from the server-rendered DB
value and, on Save, wrote only to the DB. Meanwhile the sidebar toggle wrote
**both** localStorage and the DB, and the first-paint script trusts localStorage
first. The two sources drifted, so the visible page (localStorage-driven) and the
settings segmented control (DB-driven) could show opposite modes.

**Fix:**
- Settings now initializes from localStorage first, DB as fallback:
  ```js
  let themeMode = localStorage.getItem('themeMode') || '{{ $preferences->theme_mode }}';
  ```
- Settings writes localStorage on a successful Save (required, because first-paint
  trusts localStorage — a Save that skipped it would revert on reload):
  ```js
  applyThemeToDocument();
  localStorage.setItem('themeMode', themeMode);
  setSaved();
  ```

This fixed cross-**navigation** sync. But the two controls are on screen at the
same time, so a second bug remained.

### Bug 2 — No live sync while both controls are visible

- Toggling the sidebar changed `.dark` + localStorage + DB, but nothing told the
  settings page's JS to restyle its segmented buttons → the highlight stayed on
  the old mode.
- Changing the mode on the settings page flipped the `.dark` class, but the
  sidebar's Alpine `dark` state is read **once** at init
  (`document.documentElement.classList.contains('dark')`) and never re-read →
  the moon/sun icon and "Light/Dark mode" label went stale.

Both are the same root cause: **neither control observes the other's changes at
runtime.** They only reconciled on a full page reload.

## The fix — one shared event channel

Both controls broadcast a `theme-mode-changed` CustomEvent when the user changes
the mode, and both listen for it to update their own UI. The event carries the
explicit mode string so `system` resolves correctly (via `matchMedia`), which a
bare `.dark` class can't express.

```
User clicks sidebar toggle ──▶ dispatch('theme-mode-changed', {mode})
                                     │
                                     ├─▶ sidebar listener: set this.dark (idempotent)
                                     └─▶ settings listener: themeMode = mode; updateSegmentedControl()

User clicks a Settings segment ──▶ applyThemeToDocument() ──▶ dispatch('theme-mode-changed', {mode})
                                     │
                                     ├─▶ sidebar listener: flip this.dark → icon + label update
                                     └─▶ settings listener: mode === themeMode ⇒ guard returns (no loop)
```

### `resources/js/app.js` — `themeToggle`

```js
Alpine.data('themeToggle', (opts = {}) => ({
    dark: document.documentElement.classList.contains('dark'),
    persistUrl: opts.persistUrl || '',

    init() {
        // Reflect mode changes made elsewhere (e.g. the settings segmented control).
        window.addEventListener('theme-mode-changed', (e) => {
            const mode = e.detail?.mode;
            this.dark = mode === 'dark'
                || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        });
    },

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        const mode = this.dark ? 'dark' : 'light';
        localStorage.setItem('themeMode', mode);
        // Tell other on-page controls (settings segmented buttons) to update.
        window.dispatchEvent(new CustomEvent('theme-mode-changed', { detail: { mode } }));

        if (this.persistUrl) {
            fetch(this.persistUrl, { /* PATCH theme_mode → settings.theme-mode */ });
        }
    },
}));
```

### `resources/views/settings/index.blade.php`

Dispatch from the single place that mutates the mode (runs on segment click *and*
on Save):

```js
function applyThemeToDocument() {
    const root = document.documentElement;
    root.setAttribute('data-theme', currentTheme);
    root.setAttribute('data-fill', currentFill);
    const dark = themeMode === 'dark'
        || (themeMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    root.classList.toggle('dark', dark);
    // Notify the sidebar toggle (Alpine) so its icon + label flip live.
    window.dispatchEvent(new CustomEvent('theme-mode-changed', { detail: { mode: themeMode } }));
}
```

Listen so a sidebar toggle updates the segmented control:

```js
// Sidebar toggle changed the mode → mirror it on the segmented control.
// The sidebar already persisted it to the DB, so don't mark the page dirty,
// and don't re-apply/re-dispatch (the equality guard breaks any loop).
window.addEventListener('theme-mode-changed', (e) => {
    const mode = e.detail?.mode;
    if (!['light', 'dark', 'system'].includes(mode) || mode === themeMode) return;
    themeMode = mode;
    updateSegmentedControl();
});
```

### Bug 3 — "System" mode didn't follow the OS live

Selecting **System** resolved correctly *at the moment of the click* and *on
reload* (both the settings page and the first-paint `<head>` script read
`matchMedia('(prefers-color-scheme: dark)').matches`). But an audit found that
**every** `prefers-color-scheme` usage in the app was a one-time `.matches` read —
there was no `matchMedia(...).addEventListener('change', …)` anywhere.

**Symptom:** pick System, leave the tab open, then change the OS appearance
(e.g. macOS auto dark mode at sunset, or System Settings → Appearance). Nothing
happened until you reloaded or clicked a mode button — which defeats the entire
point of a "System" option.

**Fix** — a global listener in `resources/js/app.js`, added right after the
`themeToggle` component (it's app-wide, not tied to any one page):

```js
// Track the *current effective* mode, not the persisted one — seeded at load,
// updated on every theme-mode-changed (which the settings page fires the moment
// "System" is clicked). So live-following works during an unsaved preview too.
let effectiveThemeMode = localStorage.getItem('themeMode') || 'light';
window.addEventListener('theme-mode-changed', (e) => {
    const mode = e.detail?.mode;
    if (['light', 'dark', 'system'].includes(mode)) effectiveThemeMode = mode;
});

// Live "System" mode: follow the OS light/dark setting in real time.
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (effectiveThemeMode !== 'system') return;
    document.documentElement.classList.toggle('dark', e.matches);
    // reuse the sync channel so the sidebar icon + settings buttons follow along
    window.dispatchEvent(new CustomEvent('theme-mode-changed', { detail: { mode: 'system' } }));
});
```

Because it re-emits `theme-mode-changed` with `mode: 'system'`, the sidebar icon
flips and the settings segmented control stays on **System** automatically. On the
settings page the mockup recolors for free too, since `.dark` on `<html>` drives
all the CSS-variable tokens. The settings listener's equality guard
(`mode === themeMode`) makes the re-emit a safe no-op when System is already
selected — it only needs the `.dark` flip, which the global listener already did.

> ⚠️ **The gate must be the effective mode, not `localStorage`.** The first cut
> gated on `localStorage['themeMode'] === 'system'`, which silently failed: the
> settings page is **Save-only**, so clicking *System* without pressing *Save*
> never wrote `localStorage`, and the OS-change listener no-op'd. Tracking the mode
> via `theme-mode-changed` (which fires on the preview click, before Save) fixes it.
> The `matchMedia` `change` event fires only on a real OS flip, never on the custom
> event, so there's no loop with the `effectiveThemeMode` updater.

### Bug 4 — Empty `localStorage` on a fresh device broke live System mode

The live-System listener (Bug 3) keys off `localStorage['themeMode'] === 'system'`.
But `localStorage` is **per-device**: a user who saved `system` on one device and
then opens the app on another (where they've never toggled) has empty
`localStorage`. The listener fell back to `'light'` and wouldn't follow the OS —
even though the server preference said `system`.

**Fix** — the first-paint `<head>` script seeds `localStorage` from the server
preference when (and only when) it's empty:

```js
(function () {
    var stored = localStorage.getItem('themeMode');
    var mode = stored || @json($themeMode);
    // Seed from the saved server preference on a fresh device so the live
    // "System" listener and the settings page have a value to read.
    if (!stored) localStorage.setItem('themeMode', mode);
    var dark = mode === 'dark' ||
        (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
})();
```

**Why "only when empty" matters:** it upholds the *localStorage-wins-over-DB* rule.
A prior local choice on this device is never overwritten; we only fill the gap on
first visit so downstream readers (the live-System listener, the settings init)
always see a real value. This runs for guests too — they get `'light'` (the
server fallback), which is harmless and matches the default.

> Cross-device note: this does **not** make devices converge live. If you change
> the mode on device A, device B keeps its existing `localStorage` value until you
> change it there (localStorage wins). Seeding only ever fills an *empty* slot.

## Why it can't infinite-loop

- The settings listener has an **equality guard** (`mode === themeMode ⇒ return`)
  and only restyles buttons — it never re-dispatches. By the time the settings
  page would re-emit the same mode, `themeMode` already equals it.
- The sidebar listener only sets `this.dark` from the event; setting it to the
  value it already holds is **idempotent** and emits nothing.

So `A → B → A` cannot cycle: every path terminates after one hop.

## Deliberate behavior decisions

- **Sidebar-originated change does not mark the settings page "dirty."** The
  sidebar already persisted `theme_mode` to the DB, so showing an enabled
  "Save Changes" for something already saved would be misleading.
- **Settings is Save-only for persistence.** Clicking a segment live-previews
  (applies `.dark`, flips the sidebar icon) but localStorage/DB are only committed
  on Save. The live preview is intentional; persistence waits for Save.
- **`system` is preserved end-to-end** because the event carries the explicit mode
  string. The binary `.dark` class alone could not distinguish `light` from
  `system`.

## What is NOT involved

No controller, route, validation, or DB-schema change. The sync is purely
front-end: two files (`resources/js/app.js`,
`resources/views/settings/index.blade.php`) communicating over a window
CustomEvent. The existing `settings.theme-mode` / `settings.update` endpoints and
the `EnsureUserIsAdmin`-style auth/CSRF protections are unchanged.

## Touching this later

- Adding a **third** mode control? Have it dispatch `theme-mode-changed` with the
  mode string and (if it persists) write `localStorage['themeMode']`. It will then
  sync with both existing controls for free.
- Remember the first-paint precedence: **localStorage wins over DB.** Any new
  writer must update localStorage or its change will revert on reload.
- **System mode follows the OS live** via the global `matchMedia` `change` listener
  in `app.js`. It keys off `localStorage['themeMode'] === 'system'`, so it only
  fires for users actually in System mode. If you change how the saved mode is
  read, keep that listener's source consistent.
- After editing `resources/js/app.js`, run `npm run build` (or `composer dev`)
  — the Blade inline scripts don't need a build, but the Alpine component does.
