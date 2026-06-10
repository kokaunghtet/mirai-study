# MiraiStudy — Project Context & Development History

## Project Overview

MiraiStudy is a full-stack educational social media platform combining social learning, productivity tools, and exam preparation. It targets JLPT (N1–N5) and ITPEC (FE, IP) exam content.

**Stack:** Laravel 13.x (`composer` requires `laravel/framework ^13.8`, `php ^8.3`; started on Laravel 11 and upgraded during dev), Laravel Breeze auth scaffolding, Blade templates, Alpine.js, Tailwind CSS, Vite, MySQL.
**Dev environment:** Mac (Apple Silicon), PHP 8.5.5.
**Project directory:** `mirai-study`

---

## Platform Sections

1. **Learning Social Feed** — Users create/share study-related posts with text, images, and file attachments. Like, comment, bookmark, follow.
2. **Focus Timer (Pomodoro)** — Dedicated Pomodoro timer tab. Auth users save sessions/settings. Guests get browser-only timer with no storage or customization.
3. **Old Questions Section** — Past exam papers (JLPT, ITPEC) browsable by category, level, year. Auth users can download.
4. **Quiz / Mock Test Section** — Quiz system based on JLPT/ITPEC questions with score tracking.

---

## Guest vs Authenticated User Access

| Feature | Guest | Auth User |
|---|---|---|
| View feed | ✅ | ✅ |
| Like / Comment / Bookmark | ❌ → auth modal | ✅ |
| View exam papers | ✅ | ✅ |
| Download papers | ❌ → auth modal | ✅ |
| Use timer | ✅ browser only | ✅ |
| Save timer sessions | ❌ | ✅ |
| Customize timer settings | ❌ | ✅ |

Guest interactions trigger an Alpine.js auth modal (`open-auth-modal` event) prompting login/register.

---

## Database Schema — Final State

### Enums

- `user_role`: admin, moderator, user
- `user_status`: active, suspended, banned
- `report_target_type`: post, user, comment
- `report_status`: pending, reviewed, resolved, rejected
- `notification_type`: like_post, comment_post, follow_user, system, report_reviewed
- `answer_option`: A, B, C, D
- `media_type`: image, document (no video — explicitly excluded)
- `theme_mode`: light, dark, system
- `accent_color`: venom, aurora, sangria, twilight, inferno
- `follow_status`: accepted, pending, blocked
- `otp_purpose`: email_verification, password_reset, login_verification

### Tables (22 custom + 3 Laravel default)

**Core:**
- `users` — username, display_name, email, password, bio, profile_image, role, status, soft deletes
- `user_preferences` — theme_mode, accent_color, show_liked_posts (boolean, default true)
- `otps` — otp_code, purpose, expires_at, used_at

**Social:**
- `posts` — user_id, title (nullable), content, soft deletes
- `post_media` — post_id, url, type, filename (nullable), filesize (nullable)
- `tags` — name (unique)
- `post_tags` — composite PK (post_id, tag_id)
- `comments` — post_id, user_id, parent_id (self-referencing, nullable, nullOnDelete), soft deletes
- `post_likes` — composite PK (user_id, post_id), created_at only
- `comment_likes` — composite PK (user_id, comment_id), created_at only
- `follows` — composite PK (follower_id, following_id), status, created_at only
- `bookmarks` — composite PK (user_id, post_id), created_at only
- `reports` — reporter_id, target_type, target_id, reason, status, reviewed_by
- `notifications` — user_id, sender_id, type, title, content, url, read_at (not is_read boolean)

**Focus:**
- `pomodoro_settings` — user_id (unique), focus_minutes, short_break_minutes, long_break_minutes, sessions_before_long_break, daily_goal_sessions
- `timer_sessions` — user_id, planned_duration, actual_duration, completed, started_at, ended_at

**Exam:**
- `exam_categories` — name (JLPT, ITPEC)
- `exam_levels` — category_id, code, name
- `exam_papers` — category_id, level_id, uploaded_by, title, year, session, description, file_url, file_type (NO download_count column — use withCount('downloads') instead)
- `paper_downloads` — user_id, paper_id, downloaded_at
- `questions` — category_id, level_id, text, option_a/b/c/d, answer, explanation
- `quiz_attempts` — user_id, category_id, level_id, total_questions, score, started_at, completed_at
- `user_answers` — attempt_id, question_id, selected_answer, is_correct

### Key Schema Decisions

- **No `updated_at` on pivot tables** (bookmarks, post_likes, comment_likes, follows) — these are append-only. No `withTimestamps()` on their relationships.
- **`read_at` timestamp** instead of `is_read` boolean on notifications.
- **`deleted_at` (soft deletes)** on users, posts, comments.
- **`parent_id` on comments** for threaded replies with `nullOnDelete()`.
- **`show_liked_posts`** on user_preferences for privacy toggle.
- **`filename` and `filesize`** (both nullable) on post_media for document metadata — defined inline in the create migration. No separate alter migrations exist in the project.

---

## Models — Key Relationship Notes

### User.php

- `posts()`, `comments()` — hasMany
- `preferences()` — hasOne UserPreference
- `pomodoroSettings()` — hasOne PomodoroSetting
- `timerSessions()`, `otps()`, `reports()` — hasMany
- `appNotifications()` — hasMany Notification (**renamed from `notifications()` to avoid conflict with Laravel's Notifiable trait**)
- `likedPosts()` — belongsToMany Post via post_likes (**no withTimestamps**)
- `bookmarkedPosts()` — belongsToMany Post via bookmarks (**no withTimestamps**)
- `following()` — belongsToMany User via follows (follower_id → following_id), withPivot('status'), **no withTimestamps**
- `followers()` — belongsToMany User via follows (following_id → follower_id), withPivot('status'), **no withTimestamps**
- Helper methods: `isAdmin()`, `isModerator()`, `isActive()`
- Uses: HasFactory, Notifiable, SoftDeletes

### PostMedia.php

- **Class name issue:** Laravel's `make:model` generated `PostMedium` (singularized). Manually renamed to `PostMedia` to match filename. PSR-4 requires class name = filename.

### TimerSession.php

- Had wrong class content (PomodoroSetting was pasted in). Fixed by verifying class name matches filename.

### Report.php

- `target()` uses `match()` on target_type to resolve polymorphic-style relationship dynamically.

### Notification.php

- Has `isRead()`, `markAsRead()` helper methods using `read_at` timestamp.

### Tag.php

- `$timestamps = false` — tags table has no timestamp columns.
- Must include `HasFactory` trait for seeding.

---

## Seeders & Factories

### Fixed Data

- **Users:** 1 admin (admin@example.com), 1 moderator (mod@example.com), 1 test user (test@example.com), 20 random users. All password: `password`.
- **Tags:** 6 fixed study-related tags: JLPT, ITPEC, Programming, Study Tips, Productivity, Notes.
- **Exam Categories:** JLPT (levels N1-N5, 10 questions each = 50), ITPEC (levels FE, IP, 10 questions each = 20). Total: 70 questions.
- **Each user creates 3 posts** with random tags, comments, replies, and likes.
- **15 quiz attempts** with randomly selected answers and computed scores.
- **Random follows** between users.

### Factory Design Decisions

- QuestionFactory and QuizAttemptFactory use `null` for FK fields — always passed explicitly from seeders. Don't query DB inside `definition()`.
- TagFactory uses a static array with an index counter, not Faker.
- `fake()` helper doesn't work reliably in Seeders — use `Arr::random()` instead.

---

## Routes — Final Structure

### Public Routes

```
GET  /                          → redirect to feed.index
GET  /feed                      → PostController@index
GET  /users/{user:username}     → ProfileController@show
GET  /users/{user:username}/followers → ProfileController@followers
GET  /users/{user:username}/following → ProfileController@following
GET  /exams                     → ExamCategoryController@index
GET  /exams/{category}          → ExamCategoryController@show
GET  /exams/{category}/{level}  → ExamPaperController@index
GET  /questions                 → QuestionController@index
GET  /timer                     → TimerController@index
```

### Auth Routes

```
GET    /profile/edit            → ProfileController@edit
PATCH  /profile                 → ProfileController@update
PATCH  /profile/preferences     → ProfileController@updatePreferences (privacy toggle, JSON)
DELETE /profile                 → ProfileController@destroy

GET    /posts/create            → PostController@create (BEFORE wildcard)
POST   /posts                   → PostController@store
GET    /posts/{post}/edit       → PostController@edit
PATCH  /posts/{post}            → PostController@update
DELETE /posts/{post}            → PostController@destroy

POST   /posts/{post}/comments   → CommentController@store
PATCH  /comments/{comment}      → CommentController@update
DELETE /comments/{comment}      → CommentController@destroy

POST   /posts/{post}/like       → PostLikeController@toggle
POST   /comments/{comment}/like → CommentLikeController@toggle

POST   /posts/{post}/bookmark   → BookmarkController@toggle
GET    /bookmarks               → BookmarkController@index

POST   /users/{user}/follow            → FollowController@toggle
POST   /users/{user}/remove-follower   → FollowController@removeFollower

GET    /exams/papers/{paper}/download → ExamPaperController@download

GET    /quiz                    → QuizController@index
POST   /quiz/start              → QuizController@start
GET    /quiz/{attempt}/result   → QuizController@result (BEFORE wildcard)
GET    /quiz/{attempt}          → QuizController@show
POST   /quiz/{attempt}/submit   → QuizController@submit

POST   /timer/sessions          → TimerController@store
PATCH  /timer/settings          → TimerController@updateSettings

GET    /notifications           → NotificationController@index
PATCH  /notifications/read-all  → NotificationController@markAllRead (BEFORE wildcard)
PATCH  /notifications/{notification}/read → NotificationController@markRead

GET    /settings                → SettingsController@index
PATCH  /settings                → SettingsController@update
```

### Public Wildcard (AFTER all static /posts/* routes)

```
GET  /posts/{post}/comments     → CommentController@index  (public; comments partial for feed drawer)
GET  /posts/{post}              → PostController@show
```

### Admin Routes (auth + admin middleware, `prefix('admin')->name('admin.')`)

```
GET    /admin                     → AdminController@index            (name: admin.dashboard)
GET    /admin/users               → AdminController@users            (name: admin.users)
PATCH  /admin/users/{user}/status → AdminController@updateUserStatus (name: admin.users.status)
GET    /admin/reports             → AdminController@reports          (name: admin.reports)
PATCH  /admin/reports/{report}    → AdminController@updateReport     (name: admin.reports.update)
```

### Route Ordering Rules Applied

- Static routes (`/posts/create`) always before wildcards (`/posts/{post}`).
- `/quiz/{attempt}/result` before `/quiz/{attempt}`.
- `/notifications/read-all` before `/notifications/{notification}/read`.

---

## Controllers — Implementation Status

### Fully Implemented

- **PostController** — CRUD with file uploads (images + documents), eager loading optimized for guest vs auth, AJAX support for infinite scroll, uses PostPolicy for authorization.
- **CommentController** — index (renders `feed._comments` partial for the AJAX comment drawer), store, update, destroy with CommentPolicy.
- **PostLikeController** — Toggle via fetch (JSON response), no page reload.
- **CommentLikeController** — Toggle via fetch (JSON response).
- **BookmarkController** — Toggle via fetch (JSON response), index with infinite scroll, AJAX support.
- **FollowController** — toggle via fetch (JSON response), prevents self-following; removeFollower detaches a follower (JSON response).
- **NotificationController** — index, markRead, markAllRead. Uses `appNotifications()` relationship. **Caveat:** `index()` returns `view('notifications.index')`, but that view does not exist yet — the page is not functional end-to-end.
- **TimerController** — index (guest-safe), store sessions, update settings. **Caveat:** `index()` returns `view('timer.index')`, but that view does not exist yet — the page is not functional end-to-end.
- **ProfileController** — Public show with tabs (posts/liked), followers list, following list, edit profile with image upload, updatePreferences (AJAX privacy toggle → `/profile/preferences`, JSON), account deletion.
- **SettingsController** — Appearance settings (theme mode + accent color), AJAX save.

### Generated But Empty (Not Yet Implemented)

- **ExamCategoryController**
- **ExamPaperController**
- **QuestionController**
- **QuizController**
- **AdminController**

---

## Middleware

### EnsureUserIsAdmin

- Registered as `'admin'` alias in `bootstrap/app.php`.
- Checks `auth()->check()` && `auth()->user()->isAdmin()`, otherwise `abort(403)`.

---

## Policies

### PostPolicy

- `update()` — owner only
- `delete()` — owner OR admin OR moderator

### CommentPolicy

- `update()` — owner only
- `delete()` — owner OR admin OR moderator

---

## Views — File Structure

```
resources/views/
├── layouts/
│   ├── app.blade.php           ← Sidebar layout (not top nav) — app shell
│   ├── guest.blade.php         ← Breeze guest layout
│   └── navigation.blade.php    ← Breeze top-nav (not used by app layout)
├── components/
│   ├── post-card.blade.php     ← Reusable post card with AJAX like/bookmark
│   ├── auth-modal.blade.php    ← Guest prompt modal
│   └── …                       ← Breeze UI components (modal, dropdown, text-input, buttons, input-label/error, nav-link, etc.)
├── feed/
│   ├── index.blade.php         ← Feed with infinite scroll + back button restore
│   ├── _posts.blade.php        ← Partial for AJAX loading
│   ├── _comments.blade.php     ← Reusable comments section (used by show page + AJAX comment drawer)
│   ├── show.blade.php          ← Single post with comments + replies
│   ├── create.blade.php        ← Create post with tab UI (Text/Media/File)
│   └── edit.blade.php          ← Edit post with existing media management
├── bookmarks/
│   ├── index.blade.php         ← Bookmarked posts with infinite scroll
│   └── _posts.blade.php        ← Partial for AJAX loading
├── profile/
│   ├── show.blade.php          ← Public profile with tabs (Posts/Liked)
│   ├── _posts.blade.php        ← Partial for AJAX loading
│   ├── edit.blade.php          ← Profile settings + privacy + delete account
│   ├── followers.blade.php     ← Followers list
│   ├── following.blade.php     ← Following list
│   └── partials/               ← Breeze profile partials (update info / update password / delete user)
├── settings/
│   └── index.blade.php         ← Appearance settings with live preview mockup
└── auth/                       ← Breeze auth views (login, register, forgot/reset password, verify email, confirm password)
```

> **Not yet created:** `notifications/index.blade.php` and `timer/index.blade.php` do **not** exist, even though `NotificationController@index` and `TimerController@index` reference them. Hitting `/notifications` or `/timer` would currently throw a "view not found" error.

---

## Layout — app.blade.php

- **Sidebar navigation** (not top nav) — fixed left sidebar on desktop, slide-out on mobile with overlay.
- Active state uses `request()->routeIs()` with green highlighting.
- User menu at bottom of sidebar with upward dropdown (My Profile, Settings, Logout).
- Guest users see Login/Sign up buttons instead.
- Includes `@stack('scripts')` before `</body>` for page-specific JavaScript.
- Includes auth-modal for guests via `@guest @include('components.auth-modal') @endguest`.
- Brand color: **green** (`green-600` primary).

---

## Post Card — Design & Behavior

### Design (from custom post-card.html reference)

- `rounded-2xl`, `border-gray-200`, `shadow-sm`, `hover:shadow-md`
- Header: Avatar + display name + timestamp + Follow button + more menu (three dots)
- Content: Dynamic text size (large for short posts, small for long), `Str::limit(300)` with "see more" link
- Tags: Rounded pills with green accent
- Footer: Like (thumbs up icon) + Comment (speech bubble) + Bookmark (flag) + Share (paper plane with clipboard copy)
- Media: Image carousel with prev/next arrows, dots navigation, counter badge
- File attachments: File chips with icon, filename, filesize, download link

### Interaction Behavior

- **Like**: AJAX fetch toggle, no page reload, count updates instantly, fill changes on state
- **Bookmark**: AJAX fetch toggle, no page reload, icon fills on state
- **Follow**: AJAX fetch toggle on profile page and post cards
- **Share**: Alpine.js clipboard copy with checkmark feedback (2 second timeout)
- **More menu**: Alpine.js dropdown — Edit/Delete for owner, Report for others
- **Guest clicks**: All interactive buttons dispatch `open-auth-modal` event

### Eager Loading for Post Queries (Auth vs Guest)

```php
// Auth user
$with = ['user', 'tags', 'media'];
$with['bookmarks'] = fn($q) => $q->where('user_id', $userId);
$with['likes']     = fn($q) => $q->where('user_id', $userId);

// Guest
$with = ['user', 'tags', 'media'];
// No bookmarks or likes loaded — prevents loading ALL records
```

---

## Infinite Scroll Implementation

Used on: Feed, Bookmarks, Profile (posts tab, liked tab).

### Architecture

- IntersectionObserver watches a sentinel div at the bottom.
- Fetches pages via AJAX with `X-Requested-With: XMLHttpRequest` header.
- Controller returns JSON `{ html, next_page_url }` for AJAX, full view for normal requests.
- Uses `_posts.blade.php` partials to render post cards server-side.
- `Promise.all` with `sleep(1000)` prevents jarring flash on fast connections.

### Back Button Scroll Restoration

- On click: saves `scrollY` and `currentPage` to `sessionStorage` (per-page keys to avoid conflicts).
- On return: detects via `pageshow` event, reloads all pages 2→savedPage, then scrolls to saved position.
- Handles `event.persisted` (bfcache) separately — content already in DOM, just scroll.
- Double `requestAnimationFrame` ensures browser has painted before scrolling.

### Performance Controls

- Page cap at 10 pages (configurable via `MAX_PAGES`) with "Back to top" suggestion.
- `loading="lazy"` on all images.
- Error message visibility fixed with success flag pattern (avoids `finally` block hiding errors).

### Session Storage Keys (per page to avoid conflicts)

- Feed: `feed_scroll_y`, `feed_last_page`
- Bookmarks: `bookmarks_scroll_y`, `bookmarks_last_page`
- Profile: `profile_scroll_{username}_{tab}`, `profile_page_{username}_{tab}`

---

## Post Creation — Tab UI

### Create Page (`feed/create.blade.php`)

Three tabs: Text / Media / File — matching the reference post-card.html design.

- **Text tab**: Always visible textarea.
- **Media tab**: Drop zone → preview carousel with prev/next/dots/counter/change button.
- **File tab**: Drop zone → file chips with name, size, remove button.
- Optional title input below content area.
- Tag checkboxes.
- Publish button with send icon.

### Critical Implementation Details

- **Persistent file inputs** (`<input type="file">`) are placed **outside** `x-if`/`x-show` templates and referenced via `id`/`for` attributes. `x-if` destroys DOM nodes including file inputs and their selected files.
- `URL.revokeObjectURL()` called on media change to prevent memory leaks.
- `enctype="multipart/form-data"` on form for file uploads.

### Edit Page (`feed/edit.blade.php`)

- Shows existing media in a grid with checkbox-to-remove pattern (red border + X icon on check).
- Shows existing files with checkbox-to-remove.
- New upload zones for adding more media/files.
- `remove_media[]` hidden checkboxes send IDs of media to delete.
- Controller handles: remove existing → add new → sync tags.

---

## File Upload System

### Storage

- `php artisan storage:link` creates public symlink.
- `FILESYSTEM_DISK=public` in `.env`.
- Images stored at `posts/media/`, files at `posts/files/`, profile images at `profiles/`.
- URLs generated via `Storage::url($path)`.

### Validation

- Images: `mimes:jpg,jpeg,png,gif,webp|max:20480` (20MB).
- Files: `max:20480` (20MB, any type).
- Profile images: `mimes:jpg,jpeg,png,gif,webp|max:5120` (5MB).
- **No video uploads** — explicitly excluded.

---

## Profile Pages

### Public Profile (`/users/{username}`)

- Avatar (80px, supports uploaded images or initial fallback), display name, @username, bio.
- Stats row: Posts count, Followers (clickable → list), Following (clickable → list).
- Action button: Edit Profile (own) or Follow/Following toggle (others) or auth modal (guests).
- Tabs: Posts | Liked (Liked tab hidden if user's `show_liked_posts` is false AND not own profile).
- Own profile always sees Liked tab with "(Private)" label if setting is off.
- Infinite scroll on both tabs.

### Profile Edit (`/profile/edit`)

- Profile image upload with instant preview via `URL.createObjectURL()`.
- Display name, username (with @ prefix), bio fields.
- Email shown as read-only disabled input.
- Privacy section: "Show liked posts" toggle (custom Alpine switch) → AJAX `PATCH /profile/preferences` (`ProfileController@updatePreferences`), returns JSON `{ show_liked_posts }`. Independent of the profile-info form (its own endpoint, no full-page submit).
- Delete account section: confirmation flow with password input.

### Followers / Following Lists

- Back arrow linking to profile.
- User cards with avatar, display name, @username.
- Follow button on each user (not shown for own account).
- On your own followers list, a "Remove" action detaches a follower via `POST /users/{user}/remove-follower` (`FollowController@removeFollower`, JSON).
- Pagination (standard, not infinite scroll).

---

## Settings / Appearance Page

### Design

- Two-column layout: Left controls (~60%) + Right live preview mockup (~40%).
- Theme section: Light / Dark (disabled, "coming soon") / System segmented control.
- Primary Color section: 5 options — Venom (green gradient), Aurora (blue), Sangria (violet), Twilight (rose), Inferno (amber).
- Save Changes button: full-width, cycles through Save Changes → Saving... → Saved → Save Changes.
- Live mockup preview shows a mini version of the app with sidebar nav, post cards, and all pages.

### Technical Implementation

- Preferences loaded from database via `SettingsController` (`$preferences` passed to view).
- Changes update preview instantly but **only save to DB on explicit Save button click**.
- `isDirty` flag tracks unsaved changes; `beforeunload` warns on navigation away.
- AJAX PATCH to `/settings` saves `theme_mode` and `accent_color`.
- `safeCreateIcons()` wrapper for Lucide — catches errors, retries after 300ms.
- Init sequence: buttons pre-selected first (no Lucide dependency), mockup loaded second, Lucide third with retry.
- Fallback guards for stale DB values: defaults to `venom` and `light`.

### Database

- `user_preferences.theme_mode` — enum: light, dark, system (default: light)
- `user_preferences.accent_color` — enum: venom, aurora, sangria, twilight, inferno (default: venom)

---

## Key Lessons & Patterns Learned

1. **`withTimestamps()` rule:** If a pivot table has no `updated_at` column, remove `withTimestamps()` from the relationship. Don't add `updated_at` to the table to fix the error.
2. **PSR-4 autoloading:** Class name inside the file must exactly match the filename. `PostMedia.php` must contain `class PostMedia`.
3. **Route ordering:** Static routes (`/posts/create`) must come before wildcard routes (`/posts/{post}`).
4. **`notifications()` name conflict:** Laravel's Notifiable trait reserves this method. Rename custom notification relationships (e.g., `appNotifications()`).
5. **`x-if` destroys DOM nodes:** Don't use `x-if` around file inputs. Use persistent hidden inputs with `id`/`for` attributes.
6. **`finally` block timing:** Runs immediately after `catch`, which can hide error messages. Use a success flag pattern.
7. **Guest eager loading:** Don't use `when(auth()->check())` inside eager load constraints — when false, it loads ALL records. Build the `$with` array conditionally instead.
8. **`fake()` in Seeders:** Unreliable across Laravel versions. Use `Arr::random()` instead.
9. **`composer dump-autoload`:** Always run after creating/renaming models. Check for PSR-4 warnings.
10. **`migrate:fresh`:** Safe during solo early development. Use alter migrations once others are on the project.

---

## What's Built vs What's Remaining

### ✅ Completed

- Environment setup + Laravel Breeze
- All 22 migrations + models + relationships
- Seeders with realistic test data
- Full route architecture with proper ordering
- Social feed with CRUD, infinite scroll, back button restore
- Post creation with Text/Media/File tab UI
- Post editing with existing media management
- Like toggle (AJAX, no reload)
- Bookmark toggle (AJAX, no reload) + bookmarks page
- Comment system with threaded replies
- Follow/Unfollow toggle (AJAX)
- Profile pages (public view, edit, followers, following)
- Liked posts privacy toggle
- Settings/Appearance page with live preview + DB persistence
- Auth modal for guest users
- Sidebar layout with mobile responsiveness
- Admin middleware
- Post and Comment policies

### 🔲 Not Yet Built

- Exam section — `ExamCategoryController`, `ExamPaperController`, `QuestionController` are still empty stubs (no logic, no views).
- Quiz system — `QuizController` is an empty stub (no logic, no views, no flow).
- Admin section — `AdminController` is an empty stub (no dashboard, user management, or reports queue).
- Timer page — `TimerController` is implemented, but `timer/index.blade.php` view does not exist yet.
- Notifications page — `NotificationController` is implemented, but `notifications/index.blade.php` view does not exist yet.
- Notification system (event-driven creation, real-time updates) — no events/listeners wired up.
- Report submission flow.
- Dark mode (planned, `dark:` variants needed across all views).
- Color theme application to actual app (currently only affects the settings preview).
- Search functionality.
