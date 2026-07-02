<div align="center">

# 🎓 MiraiStudy

**A full-stack educational social platform for JLPT & ITPEC exam preparation**

[![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-3-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Vite](https://img.shields.io/badge/Vite-8-646CFF?style=flat-square&logo=vite&logoColor=white)](https://vitejs.dev)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com)

Combines a social study feed, Pomodoro focus timer, quiz/mock-test engine, and exam paper browser — all in one Laravel app.

</div>

---

## 📖 Table of Contents

- [Features](#-features)
- [Prerequisites](#-prerequisites)
- [Quick Start](#-quick-start)
- [Environment Setup](#-environment-setup)
- [Development](#-development)
- [Architecture](#-architecture-highlights)
- [Documentation](#-documentation)
- [Project Structure](#-project-structure)
- [Testing](#-testing)
- [Contributing](#-contributing)

---

## ✨ Features

### Implemented

| Category | Feature                 | Description                                                                                                                                                                                                                                                                                  |
| :------: | ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
|    🔐    | **Custom OTP/2FA Auth** | Login via username or email; email-verification and optional 2FA via 6-digit OTP codes (10-min TTL, no replay). Live username availability/suggestions on registration. Google login via Socialite.                                                                                          |
|    📱    | **Social Feed**         | CRUD posts with images + file attachments. Like, bookmark, threaded comments. Soft deletes. Infinite scroll with scroll-position restore on back navigation. Engagement-weighted ranking.                                                                                                    |
|    👥    | **Follow System**       | Follow/unfollow users, remove followers, follower/following lists on profiles.                                                                                                                                                                                                               |
|    👤    | **User Profiles**       | Public profile by username with posts/liked tabs. Privacy toggle for liked posts. Avatar upload. Account deletion.                                                                                                                                                                           |
|    🎨    | **Settings / Theming**  | Light/dark/system mode + 5 accent colors (venom, aurora, sangria, twilight, inferno) + gradient/solid fill. CSS-variable tokens with Tailwind opacity support. Live preview mockup. Instant-save 2FA toggle.                                                                                 |
|    ⏱️    | **Focus Timer**         | Pomodoro timer with configurable focus/break durations, SVG ring progress, daily goal tracking. Ambient sounds via Web Audio API (rain, brown noise, binaural beats). Synthesized completion chime. Guest-accessible with lock overlay.                                                      |
|    📝    | **Quiz Engine**         | 4-step wizard: category → level → section → question count. Random draws from 60 questions/pool. Server-side grading (answers never sent to client). Per-question review on results. Resume/discard in-progress quizzes. History with pagination. Config-driven catalog (`config/quiz.php`). |
|    📄    | **Exam Paper Browser**  | Category/level/year/session filtering. Folder-style browser. Admin upload with PDF storage, auto-title from filename. Uploads capped at 20 MB — see [knowledge/compress-pdf-before-upload.md](knowledge/compress-pdf-before-upload.md) to shrink large scans first.                          |
|    🔔    | **Notifications**       | Controller logic complete; view pending.                                                                                                                                                                                                                                                     |
|    👑    | **Admin Area**          | Routes and middleware gated by `admin` middleware; dashboard/users/reports views pending.                                                                                                                                                                                                    |

### 🔧 Pending Implementation

- `notifications/index.blade.php` view
- Admin dashboard / user management / reports views
- `QuestionController` (exam questions list view)

---

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.3 with extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath
- **Composer** >= 2.x
- **Node.js** >= 18.x & **npm** >= 9.x
- **MySQL** >= 8.0
- **Git**

---

## 🚀 Quick Start

### Option 1: Automated Setup (Recommended)

```bash
composer setup
```

This single command handles everything: installs dependencies, copies `.env`, generates app key, runs migrations, and builds frontend assets.

### Option 2: Manual Setup

```bash
# 1. Clone repository
git clone https://github.com/yourusername/mirai-study.git
cd mirai-study

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Setup database
php artisan migrate --seed

# 5. Install & build frontend
npm install && npm run build
```

---

## ⚙️ Environment Setup

Copy `.env.example` to `.env` and configure:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mirai_study
DB_USERNAME=root
DB_PASSWORD=

# Mail (required for OTP delivery)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Google OAuth (optional)
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
```

> **Note:** Mail must be configured for the OTP auth flow to deliver codes locally. Tests use `MAIL_MAILER=array` automatically.

---

## 💻 Development

### Start Development Server

```bash
composer dev
```

Runs concurrently:

- `php artisan serve` — Laravel dev server
- `php artisan queue:listen` — Queue worker
- `php artisan pail` — Real-time log tailer
- `npm run dev` — Vite HMR

### Production Build

```bash
npm run build
```

### Code Formatting

```bash
./vendor/bin/pint
```

---

## 🏗 Architecture Highlights

### Auth Flow

```
┌─────────────────┐     ┌──────────────────┐     ┌───────────────┐
│  Login Request  │────▶│ Validate Creds   │────▶│ OTP Challenge │
│  (user/email)   │     │ (no session yet) │     │ (if required) │
└─────────────────┘     └──────────────────┘     └───────────────┘
                              │                          │
                              ▼                          ▼
                        ┌──────────┐              ┌──────────────┐
                        │ Direct   │              │ Auth::login() │
                        │ Login    │              │ (session)     │
                        └──────────┘              └──────────────┘
```

- Login is multi-step: credentials validated without starting a session
- Unverified email or 2FA enabled → OTP challenge → only then `Auth::login()`
- `User` model uses `MustVerifyEmail` trait but deliberately does **not** implement the interface (avoids double email from Breeze's `Registered` event)

### Theming System

Three independent axes set as attributes on `<html>`:

|   Attribute   | Values                                                  | Controls                                               |
| :-----------: | ------------------------------------------------------- | ------------------------------------------------------ |
| `.dark` class | `light` / `dark` / `system`                             | Surface palette (`--canvas`, `--surface`, `--content`) |
| `data-theme`  | `venom` / `aurora` / `sangria` / `twilight` / `inferno` | Accent palette                                         |
|  `data-fill`  | `gradient` / `solid`                                    | Accent rendering                                       |

Colors stored as space-separated RGB channels in CSS for Tailwind opacity modifiers. Dark mode resolved from `localStorage` before first paint to prevent flash.

### AJAX Partial Pattern

```
Controller::index()
    ├── $request->ajax() → JSON response with rendered HTML
    └── Regular request → Full page view

Partials: _posts.blade.php, _comment-drawer.blade.php
```

### Quiz Security Model

- Question order stored in session (not DB)
- Correct answers **never** sent to client
- Grading is 100% server-side
- Config-driven catalog in `config/quiz.php`

### Guest vs Auth Access

| Feature               |         Guests         | Authenticated |
| --------------------- | :--------------------: | :-----------: |
| View feed             |           ✅           |      ✅       |
| View exam papers      |           ✅           |      ✅       |
| Use timer             | ✅ (with lock overlay) |      ✅       |
| Like/Comment/Bookmark |   ❌ (modal prompt)    |      ✅       |
| Follow users          |   ❌ (modal prompt)    |      ✅       |
| Download papers       |   ❌ (modal prompt)    |      ✅       |

---

## 📚 Documentation

Detailed guides for specific features:

| Document                                                               | Description                                                                                                                 |
| ---------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| [Feed Ranking Algorithm](knowledge/feedalgo.md)                        | How the "For You" feed scores and orders posts — covers recency, engagement, follow boost, jitter, and pagination stability |
| [Compress PDFs Before Upload](knowledge/compress-pdf-before-upload.md) | Step-by-step guide to shrink large scanned exam papers under the 20 MB upload limit using Ghostscript                       |

---

## 📁 Project Structure

```
mirai-study/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # 18 controllers
│   │   │   ├── Feed/        # Social feed CRUD
│   │   │   ├── Auth/        # OTP/2FA authentication
│   │   │   ├── Quiz/        # Quiz engine
│   │   │   ├── Timer/       # Focus timer
│   │   │   ├── Exams/       # Exam paper browser
│   │   │   └── Admin/       # Admin panel
│   │   └── Middleware/      # Auth, admin, theme middleware
│   ├── Models/              # 24 Eloquent models
│   ├── Policies/            # PostPolicy, CommentPolicy
│   ├── Services/            # OtpService
│   └── Notifications/       # OtpNotification
│
├── config/
│   └── quiz.php             # Quiz catalog configuration
│
├── resources/
│   ├── css/
│   │   └── app.css          # Theme tokens & Tailwind config
│   ├── js/
│   │   ├── app.js           # Alpine components & global helpers
│   │   └── timer.js         # Pomodoro timer logic
│   └── views/
│       ├── feed/            # Social feed (index, show, create, edit)
│       ├── profile/         # Public profile, edit, followers
│       ├── bookmarks/       # Bookmarked posts
│       ├── timer/           # Pomodoro timer
│       ├── quiz/            # Quiz wizard, attempt, result
│       ├── exams/           # Exam paper browser
│       ├── settings/        # Appearance settings
│       ├── layouts/         # Sidebar & guest layouts
│       └── components/      # Reusable Blade components
│
├── database/
│   ├── migrations/          # Database schema
│   └── seeders/             # Sample data (users, posts, quizzes, exams)
│
├── tests/
│   └── Feature/
│       ├── Auth/            # OTP flow, authentication, registration
│       ├── QuizTest.php     # Full quiz lifecycle
│       ├── FeedRankingTest.php  # Engagement-weighted sorting
│       └── ThemeModeTest.php    # Theme persistence
│
└── knowledge/               # Project documentation & guides
```

---

## 🧪 Testing

```bash
# Run all tests
composer test

# Run specific test class
php artisan test --filter=OtpFlowTest

# Run specific test file
php artisan test tests/Feature/Auth/OtpFlowTest.php
```

Tests use `MAIL_MAILER=array` for isolation.

### Key Test Coverage

| Test File                     | Coverage                         |
| ----------------------------- | -------------------------------- |
| `Auth/OtpFlowTest.php`        | OTP/2FA authentication flow      |
| `Auth/AuthenticationTest.php` | Login/logout mechanics           |
| `Auth/RegistrationTest.php`   | User registration                |
| `QuizTest.php`                | Full quiz lifecycle              |
| `FeedRankingTest.php`         | Engagement-weighted feed sorting |
| `ThemeModeTest.php`           | Theme persistence                |

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Run tests (`composer test`)
4. Format code (`./vendor/bin/pint`)
5. Commit your changes (`git commit -m 'feat: add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Commit Convention

We follow [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` — New feature
- `fix:` — Bug fix
- `docs:` — Documentation changes
- `style:` — Code style changes (formatting, missing semi-colons, etc)
- `refactor:` — Code refactoring
- `test:` — Adding or updating tests
- `chore:` — Maintenance tasks

---

<div align="center">

**Built with Love for students preparing for their future**

[Report Bug](https://github.com/yourusername/mirai-study/issues) · [Request Feature](https://github.com/yourusername/mirai-study/issues) · [Documentation](knowledge/)

</div>
