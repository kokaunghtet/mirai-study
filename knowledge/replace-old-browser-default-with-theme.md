# Replace Native confirm() / Leave-Prompt Dialogs with a Themed Alpine Modal

---

## Context

The app currently uses native browser dialogs in two situations:

### 1. Delete Buttons (7 forms)

`onsubmit="return confirm('Delete this X?')"` shows the OS-native confirm dialog.

### 2. Quiz Taking (`resources/js/app.js`)

Three native prompts currently exist:

- `quizPlayer.onSubmit` (`app.js:488`) — "You have N unanswered questions. Submit anyway?"
- `resumeBanner.abort` (`app.js:514`) — "Discard this quiz? Your progress will be lost."
- `beforeunload` guard (`app.js:459–464`) — native "Leave site?" prompt on tab close, refresh, or navigation away.

---

## Goal

Replace native browser dialogs with a single reusable themed modal matching the app's design system and visual style used by `resources/views/components/auth-modal.blade.php`.

---

## Browser Constraint

Native `beforeunload` dialogs cannot be themed due to browser restrictions.

Therefore:

| Trigger | After Change |
| --- | --- |
| Tab close / refresh | Native "Leave site?" prompt (unchanged) |
| Sidebar / internal link click during quiz | Themed "Leave quiz?" modal |
| Discard button | Themed modal |
| Submit with unanswered questions | Themed modal |
| All 7 delete buttons | Themed modal |

---

## Design

Use one reusable modal component with two invocation methods to avoid duplication.

### Declarative Usage (Delete Forms)

Replace:

```html
onsubmit="return confirm('...')"
```

with:

```html
data-confirm="..."
```

A delegated submit listener intercepts the form submission, opens the modal, and re-submits only after confirmation.

No per-form JavaScript required.

---

### Imperative Usage (Quiz Logic)

Provide:

```js
window.confirmDialog(opts)
```

which returns:

```ts
Promise<boolean>
```

This is used for quiz flows requiring dynamic text and side effects.

Both approaches share the same Alpine modal instance and theme tokens.

---

## Files to Modify

### 1. New File — `resources/views/components/confirm-modal.blade.php`

Create a reusable centered modal using the same design language as `auth-modal.blade.php`.

#### Requirements

- Backdrop: `bg-black/50`
- Smooth transitions
- `@click.self` closes
- Escape closes
- Dynamic:
  - `title`
  - `message`
  - `confirmLabel`
  - `danger` state
- Accent button by default
- Red button for destructive actions

#### Skeleton

```html
<div x-data="confirmModal()"
     x-on:open-confirm.window="open($event.detail)"
     x-show="show"
     x-cloak
     x-transition.opacity
     class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 px-4"
     @click.self="cancel()"
     @keydown.escape.window="cancel()">
    <div class="w-full max-w-sm rounded-xl bg-surface p-6 shadow-xl">
        <h2 class="text-lg font-bold text-content" x-text="title"></h2>
        <p class="mt-2 text-sm text-muted" x-text="message"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button"
                    @click="cancel()"
                    class="rounded-lg border border-line px-4 py-2 text-sm font-semibold text-content hover:bg-surface-muted">
                Cancel
            </button>
            <button type="button"
                    x-ref="confirmBtn"
                    @click="confirm()"
                    :class="danger
                        ? 'bg-red-600 hover:bg-red-700'
                        : 'bg-accent hover:bg-accent-strong'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white">
                <span x-text="confirmLabel"></span>
            </button>
        </div>
    </div>
</div>
```

---

### 2. `resources/views/layouts/app.blade.php`

Include the modal globally.

#### Requirement

Add:

```blade
@include('components.confirm-modal')
```

Place it outside the existing `@guest` block so it is available on all authenticated pages.

---

### 3. `resources/js/app.js`

#### A. Register Alpine Modal Factory

Add:

```js
Alpine.data('confirmModal', () => ({
    show: false,
    title: '',
    message: '',
    confirmLabel: 'Confirm',
    danger: false,
    open(opts) {},
    confirm() {},
    cancel() {},
}));
```

##### Behavior

**`open(opts)`**

- Store incoming options
- Store resolver callback
- Show modal
- Focus confirm button via `$nextTick`

**`confirm()`**

- Resolve promise with `true`
- Close modal

**`cancel()`**

- Resolve promise with `false`
- Close modal

---

#### B. Global Helper

Add:

```js
window.confirmDialog(opts)
```

Returns:

```ts
Promise<boolean>
```

Implementation dispatches:

```js
window.dispatchEvent(
    new CustomEvent('open-confirm', {
        detail: {
            ...opts,
            resolve: res,
        },
    })
);
```

Defaults:

```js
confirmLabel: 'Confirm'
danger: false
```

---

#### C. Global Delegated Submit Handler

Intercept:

```html
<form data-confirm="...">
```

Use a capture-phase listener.

Logic:

```js
if (form.dataset.confirmed) {
    delete form.dataset.confirmed;
    return;
}
e.preventDefault();
const confirmed = await confirmDialog(...);
if (!confirmed) return;
form.dataset.confirmed = '1';
form.requestSubmit();
```

> **Important:** Register with `{ capture: true }`

This preserves compatibility with:

- Existing loading spinner logic
- AJAX comment drawer delete actions

The bubble-phase handlers won't run until the confirmed submit occurs.

---

#### D. Quiz Submit Confirmation

Refactor `quizPlayer.onSubmit()` to async.

##### Behavior

```js
if (this.submitting) return;
```

If unanswered questions remain:

```js
e.preventDefault();
const confirmed = await confirmDialog({
    title: 'Submit quiz?',
    message: `You have ${count} unanswered questions. Submit anyway?`,
    confirmLabel: 'Submit anyway',
});
```

If cancelled:

```js
return;
```

If confirmed:

- Set `submitting = true`
- Clear `localStorage`
- Re-submit using:

```js
e.target.requestSubmit();
```

---

#### E. Quiz Leave Guard

Keep existing `beforeunload` logic unchanged.

Add a capture-phase `document` click handler that intercepts internal navigation.

##### Intercept

- Same-origin links
- Not `target="_blank"`
- Not downloads
- Not hash-only links
- Not links inside the active quiz root

If quiz progress exists:

```js
questionCount > 0 && !submitting
```

show:

```js
await confirmDialog({
    title: 'Leave quiz?',
    message: 'Your progress is saved on this device, but the quiz stays in progress. Leave anyway?',
    confirmLabel: 'Leave',
});
```

If confirmed:

```js
this.submitting = true;
window.location = href;
```

Store the listener reference and remove it during `destroy`.

---

#### F. Resume Banner Discard

Refactor `resumeBanner.abort()` to async.

##### Behavior

```js
if (this._confirmed) return;
```

Then:

```js
e.preventDefault();
const confirmed = await confirmDialog({
    title: 'Discard quiz?',
    message: 'Discard this quiz? Your progress will be lost.',
    confirmLabel: 'Discard',
    danger: true,
});
```

If confirmed:

- Clear `localStorage`
- Submit via:

```js
e.target.requestSubmit();
```

---

### 4. Replace Delete Form Confirmations

Replace:

```html
onsubmit="return confirm(...)"
```

with:

```html
data-confirm="..."
```

Optionally:

```html
data-confirm-title="Delete item?"
data-confirm-label="Delete"
```

#### Files

**`resources/views/admin/papers/index.blade.php`**

> Delete this paper? This cannot be undone.

**`resources/views/admin/questions/index.blade.php`**

> Delete this question?

**`resources/views/components/post-card.blade.php`**

> Delete this post?

**`resources/views/feed/show.blade.php`**

> Delete this post?

**`resources/views/feed/_comments.blade.php`**

- Comment delete: Delete this comment?
- Reply delete: Delete this reply?

The capture-phase submit handler preserves existing AJAX drawer behavior.

---

## Out of Scope / Unchanged

### Native Browser Leave Prompt

Keep `beforeunload` for:

- Tab close
- Browser refresh

---

### Settings Page

Leave untouched:

```
settings/index.blade.php:653
```

unless specifically requested later.

---

### Backend

No changes to:

- Controllers
- Routes
- Quiz grading
- Business logic

---

## Verification

### Build

```bash
npm run build
```

must succeed.

---

### Manual Testing

#### Server-Side Deletes

Verify:

- Feed card delete
- Feed detail page delete
- Admin paper delete
- Admin question delete

Expected:

- Themed danger modal
- Cancel = no request
- Confirm = delete occurs
- Loading spinner appears only after confirmation

---

#### AJAX Drawer Deletes

Open comment drawer.

Delete:

- Comment
- Reply

Expected:

- Themed modal appears
- AJAX delete still updates drawer in place
- No full-page reload

---

#### Quiz Submit with Unanswered Questions

Leave at least one question unanswered.

Click Submit.

Expected:

- Themed "Submit quiz?" modal
- Shows unanswered count
- Cancel stays on page
- Confirm submits quiz

---

#### Quiz Discard

Open resume banner.

Click Discard.

Expected:

- Themed "Discard quiz?" modal
- Confirm removes stored attempt

---

#### Quiz Navigation Guard

Start a quiz and answer at least one question.

Click a sidebar/internal navigation link.

Expected:

- Themed "Leave quiz?" modal
- Confirm navigates away

Refresh or close the tab.

Expected:

- Native browser "Leave site?" prompt still appears

---

### Code Quality

Run:

```bash
./vendor/bin/pint
```

and ensure:

- Formatting passes
- No new test failures
- Existing stale Breeze test issues remain unchanged