# MiraiStudy

A full-stack educational social platform for students preparing for JLPT (N1–N5) and ITPEC (FE, IP) exams. Combines a social study feed, Pomodoro focus timer, quiz/mock-test engine, and exam paper browser — all in one Laravel app.

**Stack:** PHP 8.3 · Laravel 13 · Blade · Alpine.js 3 · Tailwind CSS 3 · Lucide icons · Vite 8 · SQLite/MySQL

---

## Features

### ✅ Implemented

| Feature | Description |
|---|---|
| **Custom OTP/2FA Auth** | Login via username or email; email-verification and optional 2FA via 6-digit OTP codes (10-min TTL, no replay). Live username availability/suggestions on registration. Google login via Socialite. |
| **Social Feed** | CRUD posts with images + file attachments. Like, bookmark, threaded comments. Soft deletes. Infinite scroll with scroll-position restore on back navigation. Engagement-weighted ranking. |
| **Follow System** | Follow/unfollow users, remove followers, follower/following lists on profiles. |
| **User Profiles** | Public profile by username with posts/liked tabs. Privacy toggle for liked posts. Avatar upload. Account deletion. |
| **Settings / Theming** | Light/dark/system mode + 5 accent colors (venom, aurora, sangria, twilight, inferno) + gradient/solid fill. CSS-variable tokens with Tailwind opacity support. Live preview mockup. Instant-save 2FA toggle. |
| **Focus Timer** | Pomodoro timer with configurable focus/break durations, SVG ring progress, daily goal tracking. Ambient sounds via Web Audio API (rain, brown noise, binaural beats). Synthesized completion chime. Guest-accessible with lock overlay. |
| **Quiz Engine** | 4-step wizard: category → level → section → question count. Random draws from 60 questions/pool. Server-side grading (answers never sent to client). Per-question review on results. Resume/discard in-progress quizzes. History with pagination. Config-driven catalog (`config/quiz.php`). |
| **Exam Paper Browser** | Category/level/year/session filtering. Folder-style browser. Admin upload with PDF storage, auto-title from filename. |
| **Notifications** | Controller logic complete; view pending. |
| **Admin Area** | Routes and middleware gated by `admin` middleware; dashboard/users/reports views pending. |

### 🔧 Pending

- `notifications/index.blade.php` view
- Admin dashboard / user management / reports views
- `QuestionController` (exam questions list view)

---

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
```

Or use the project setup command:

```bash
composer setup
```

### Dev server

```bash
composer dev
```

Runs `php artisan serve`, queue listener, log tailer, and `npm run dev` concurrently.

### Testing

```bash
composer test
```

Tests use in-memory SQLite and `MAIL_MAILER=array`. Key test files:

- `Auth/OtpFlowTest.php` — OTP/2FA auth flow
- `QuizTest.php` — full quiz lifecycle
- `FeedRankingTest.php` — engagement-weighted feed sorting
- `ThemeModeTest.php` — theme persistence

---

## Architecture Highlights

### Auth flow

Login is multi-step: credentials validated without starting a session → unverified email or 2FA enabled → OTP challenge → only then `Auth::login()`. The `User` model uses `MustVerifyEmail` trait but deliberately does **not** implement the interface — avoids double email from Breeze's `Registered` event.

### Theming

Colors stored as space-separated RGB channels in `resources/css/app.css` for Tailwind opacity modifiers. Three axes on `<html>`:

- `.dark` class → surface palette (`--canvas`, `--surface`, `--content`, …)
- `data-theme` → accent palette (5 options)
- `data-fill` → gradient vs solid

Dark mode resolved from `localStorage` before first paint to prevent flash.

### AJAX partial pattern

List controllers detect `$request->ajax()` and return `response()->json(['html' => ..., 'next_page_url' => ...])` for infinite scroll / drawers. Partials prefixed `_` (`feed/_posts.blade.php`, `feed/_comment-drawer.blade.php`).

### Quiz security

Question order stored in session (not DB). Correct answers never sent to client — grading is server-side only. Quiz config is the single source of truth in `config/quiz.php`.

### Guest vs Auth

Guests can view feed, exam papers, and timer. Interactive actions (like, comment, bookmark, follow, download) trigger an Alpine auth modal via `open-auth-modal` event.

---

## Project Structure

```
app/
├── Http/Controllers/     — 18 controllers (feed, auth, quiz, timer, exams, settings, admin)
├── Models/               — 24 models (User, Post, Comment, QuizAttempt, ExamPaper, …)
├── Policies/             — PostPolicy, CommentPolicy
├── Services/             — OtpService
└── Notifications/        — OtpNotification

resources/views/
├── feed/                 — Social feed (index, show, create, edit + partials)
├── profile/              — Public profile, edit, followers, following
├── bookmarks/            — Bookmarked posts with infinite scroll
├── timer/                — Pomodoro timer view
├── quiz/                 — Quiz wizard, attempt, result, history
├── exams/                — Exam paper browser
├── settings/             — Appearance settings with live preview
├── layouts/              — Sidebar layout (app.blade.php), guest layout
└── components/           — post-card, auth-modal, Breeze UI components
```

---