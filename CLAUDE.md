# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**MiraiStudy** — a Laravel 13 study platform (social feed + exam papers + quizzes + a focus timer) for students. Stack: PHP 8.3, Laravel Breeze (Blade auth scaffolding), server-rendered Blade + Alpine.js + Tailwind CSS v3, bundled with Vite 8. Icons via Lucide.

## Commands

```bash
composer dev          # the dev command: runs `php artisan serve`, queue listener, `pail` log tailer, and `npm run dev` (vite) concurrently
composer test         # clears config cache, then `php artisan test`
composer setup        # first-time: install, .env, key:generate, migrate, npm install, build

php artisan test --filter=OtpFlowTest          # single test class
php artisan test tests/Feature/Auth/OtpFlowTest.php   # single file
php artisan migrate                            # apply migrations
php artisan migrate:fresh --seed               # rebuild DB + run seeders (DatabaseSeeder → User/Post/Quiz/Exam)

./vendor/bin/pint     # format / lint (Laravel Pint, default ruleset — no pint.json)
npm run build         # production asset build
```

Mail must be configured (`.env` `MAIL_*`, defaults to Gmail SMTP) for the auth flow to actually deliver OTP codes locally. Tests use `MAIL_MAILER=array` and an in-memory SQLite DB (see `phpunit.xml`); `.env.example` defaults to SQLite.

## Architecture

Standard Laravel MVC. The non-obvious, cross-cutting systems are below — read these before touching auth, theming, or the feed.

### Custom OTP / 2FA auth (layered on Breeze)

This is the biggest deviation from stock Breeze. Login is **not** a single step:

- `AuthenticatedSessionController::store` validates credentials *without* starting a session (via `LoginRequest::authenticateCredentials()`, which accepts username **or** email). It then branches: unverified email → `email_verification` challenge; `two_factor_enabled` → `login_verification` challenge; otherwise log in directly.
- A challenge calls `startChallenge()` which issues an OTP and stashes `otp_challenge` in the session (user is **not** authenticated yet), then redirects to `otp.challenge`.
- `OtpChallengeController::verify` checks the code and only then calls `Auth::login()`.
- `App\Services\OtpService` issues/verifies 6-digit codes (10-min TTL = `OtpService::TTL_MINUTES`); issuing a new code burns older unused codes of the same purpose; verifying consumes the code (no replay). Codes are emailed via `App\Notifications\OtpNotification`.
- **Gotcha:** `User` uses the `MustVerifyEmail` *trait* but deliberately does **not** implement the interface — implementing it would make Breeze's `Registered` event auto-send a link email and double up with the OTP flow. See the comment in `app/Models/User.php`.
- Registration has live username availability/suggestions (`UsernameController`, routes `username.available` / `username.suggestions`), driven by the `portal` Alpine component in `resources/js/app.js`. Usernames are lowercased, `^[a-z0-9]{3,30}$`, unique `withTrashed()`.

### Theming (CSS-variable tokens)

Colors are space-separated RGB channels in `resources/css/app.css` so Tailwind opacity modifiers (`bg-accent/10`) work. Three independent axes are set as attributes on `<html>`:

- `.dark` class → light/dark **surfaces** (`--canvas`, `--surface`, `--content`, …)
- `data-theme` → **accent** palette (venom/aurora/sangria/twilight/inferno)
- `data-fill` → gradient vs solid accent

Tailwind tokens that map to these vars (`accent`, `surface`, `canvas`, `content`, `muted`, `line`, …) are defined in `tailwind.config.js` — **use these tokens for new UI**, not raw colors, so it stays themeable. The attributes are rendered server-side from `auth()->user()->preferences` in `layouts/app.blade.php`, and an inline `<head>` script resolves dark mode from `localStorage` before first paint to avoid a flash. Theme changes persist via dedicated endpoints (`settings.theme-mode`, `settings.two-factor`) and the `themeToggle` Alpine component.

### Layouts & view conventions

- Page layouts are Blade components: `<x-app-layout>` (`layouts/app.blade.php`, the sidebar shell) and `<x-guest-layout>`. The auth pages use a separate `portal` layout.
- **AJAX partial pattern:** list controllers (e.g. `PostController::index`, `CommentController`) detect `$request->ajax()` and return `response()->json(['html' => view(...)->render(), 'next_page_url' => ...])` for infinite scroll / drawers. Partials are named with a leading underscore (`feed/_posts.blade.php`, `feed/_comment-drawer.blade.php`). The matching non-AJAX branch returns the full page view.
- Alpine drives all client interactivity; there's no SPA framework. Global icon helpers `window.renderIcons` / `window.appendWithIcons` re-render Lucide icons inside HTML injected via AJAX.

### Authorization

- `PostPolicy` / `CommentPolicy` enforce ownership; controllers call `$this->authorize('update', $post)`. Posts/comments use **soft deletes**.
- Admin area is gated by the `admin` middleware alias (`EnsureUserIsAdmin`, registered in `bootstrap/app.php`) which checks `User::isAdmin()` (`role === 'admin'`).

### Routing note

In `routes/web.php`, **static routes must precede wildcards** — `/posts/create` is declared before `/posts/{post}`, and the public `/posts/{post}` + `/posts/{post}/comments` wildcards sit *after* the auth group on purpose. Keep this ordering when adding routes.

## Current build state (verify before assuming a feature works)

Working: social feed (CRUD, infinite scroll, search/tag/sort filters), likes/bookmarks/threaded comments (AJAX), follows, profiles, settings (theme + 2FA), the Focus Timer (`timer/index.blade.php`, Web-Audio ambient sounds), and the full OTP/2FA auth flow.

**Empty stubs** (routes exist but error when hit): `ExamCategoryController`, `ExamPaperController`, `QuestionController`, `QuizController`, `AdminController`. `NotificationController@index` renders `notifications.index`, which **does not exist** yet.

**Stale tests:** `tests/Feature/ProfileTest.php` and `tests/Feature/ExampleTest.php` are unmodified Breeze scaffolding that asserts against routes/fields this app changed (`GET /profile`, `$user->name`, `GET /` returning 200 — it now 302s to `feed.index`). They fail; treat as stale, not regressions. Trustworthy current-behavior tests: `Auth/OtpFlowTest`, `Auth/AuthenticationTest`, `Auth/RegistrationTest`, `ThemeModeTest`.

`wiki/MiraiStudy_Project_Context.md` is a thorough hand-written design doc but predates the OTP system, the theming overhaul, and the timer view — verify against code before trusting it.

## Commit messages

After finishing a task, generate a one-line commit message summarizing the change (Conventional Commits style, e.g. `fix(feed): restore scroll position on back nav`). Do this every time work is done, even if not asked.
