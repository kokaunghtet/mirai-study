# Welcome Page Implementation Plan

## Overview
Convert `knowledge/welcomepage_prototype.html` into `resources/views/welcome.blade.php` — a full landing page for unauthenticated visitors.

## Architecture Decisions
- **Standalone page** — does NOT use `app.blade.php` layout (has its own sidebar/nav)
- **CSS inline** — welcome-specific styles in `<style>` tag to avoid polluting `app.css`
- **Lucide icons** — uses `<i data-lucide="...">` rendered by app.js `createIcons()`
- **Dark mode** — `.dark` class on `<html>`, resolved from localStorage before paint (same as app)
- **Alpine.js** — loaded via `@vite` for lightweight interactivity
- **Scoped selectors** — all CSS prefixed with `.welcome-page` wrapper to prevent conflicts with app styles

---

## Part 1: Route Change ✅ DONE
**File:** `routes/web.php` line 26

Changed from always redirecting `/` → `feed.index` to:
```php
Route::get('/', fn () => auth()->check()
    ? redirect()->route('feed.index')
    : view('welcome')
)->name('home');
```
Guests see the welcome page; authenticated users redirect to feed.

---

## Part 2: CSS (inline in welcome.blade.php)
All prototype CSS adapted:

| Prototype | Adapted To |
|-----------|-----------|
| `body.dark` | `.dark` (class on `<html>`) |
| Font Awesome icon CSS | Removed (Lucide handles sizing) |
| `.sidebar` | `.welcome-sidebar` (scoped) |
| `.sidebar-nav a` | `.welcome-sidebar-nav a` |
| `.sidebar-logo` | `.welcome-sidebar-logo` |
| `.bg-grid` | `.welcome-grid-bg` |
| `.scroll-progress` | `.welcome-scroll-progress` |
| `.mobile-hamburger` | `.welcome-mobile-hamburger` |
| `.sidebar-overlay` | `.welcome-sidebar-overlay` |
| `.main-content` | `.welcome-main-content` |

### CSS Sections
1. **Base** — body, dark mode bg
2. **Grid background** — fixed 60px grid with green lines
3. **Scroll progress** — fixed top bar
4. **Sidebar** — transparent glass, blur, logo, nav links, footer, theme toggle
5. **Mobile hamburger** — hidden on desktop
6. **Sidebar overlay** — backdrop for mobile
7. **Main content** — margin-left offset for sidebar
8. **Reveal animations** — `.reveal`, `.reveal-hero`, `.reveal-stagger` with IntersectionObserver
9. **Buttons** — `.btn-primary`, `.btn-secondary`, `.btn-white`, `.btn-dark`
10. **Card hover** — rise + green border for all card types
11. **Glass card** — frosted glass effect
12. **Resource tag** — small pill badges
13. **Badge pulse** — glowing animation
14. **Stat number** — pop animation on scroll
15. **CTA glow** — hover shadow effect
16. **Footer link** — underline animation
17. **Icon spin** — rotate on group hover
18. **Responsive** — breakpoints at 1024px and 640px
19. **Dark mode overrides** — text, bg, border adjustments for all elements

---

## Part 3: Lucide Icons Registration ✅ DONE
**File:** `resources/js/app.js`

Added imports + icon object entries:
- `Rocket` — CTA buttons, "Get Started"
- `GraduationCap` — "Start Learning Free"
- `Star` — testimonial ratings
- `Heart` — footer copyright
- `Smartphone` — mobile-friendly feature card
- `Calendar` — resource date tags
- `Box` — sidebar "Features" nav (replaces FA cube)
- `Navigation` — sidebar "How It Works" nav (replaces FA route)
- `Code` — ITPEC hub card icon
- `Monitor` — Analytics hub card icon

---

## Part 4: Sidebar + Mobile (Blade)
### Elements
- **Logo** — `M` icon square + "MiraiStudy" brand text
- **Nav links** — Home, Features (#features), How It Works (#how-it-works), Resources (#resources), Testimonials (#testimonials), GitHub (external)
- **Theme toggle** — moon/sun icon + label + toggle track/thumb
- **Footer button** — "Get Started" → `route('register')` for guests, `route('feed.index')` for auth
- **Mobile hamburger** — fixed top-left, shows on ≤1024px
- **Sidebar overlay** — backdrop blur for mobile drawer

### Auth Integration
```blade
@auth
    <a href="{{ route('feed.index') }}" class="btn-get-started">
        <i data-lucide="rocket"></i> Go to Feed
    </a>
@else
    <a href="{{ route('register') }}" class="btn-get-started">
        <i data-lucide="rocket"></i> Get Started
    </a>
@endauth
```

Login/Register links in top-right area (or sidebar footer) for guests.

---

## Part 5: Content Sections (Blade)

### 5.1 Hero Section
- Left column (7/12): badge pill, h1 headline, description, 2 CTA buttons, 3 trust indicators
- Right column (5/12): glass card with 3 hub items (ITPEC Prep, JLPT Bank, Analytics Hub)
- CTA: "Start Learning Free" → `route('register')`, "View on GitHub" → GitHub repo

### 5.2 Why Choose Us
- Section heading + subtitle
- 4-column grid: Smart Practice, Real Timers, Detailed Explanations, Community Driven
- Each card: icon circle, title, description

### 5.3 Features
- Section heading + subtitle
- 3-column grid, 6 cards: Past Paper Engine, Exam Simulation, JLPT Vocabulary Bank, Performance Analytics, Instant Feedback, Mobile Friendly
- Each card: icon, title, description, stat badge

### 5.4 How It Works
- 3-step numbered flow: Choose Your Path → Practice Past Exams → Analyze & Improve
- Step circles with hover animation

### 5.5 Resources
- 6 resource cards with date tags, titles, descriptions, "Start Practice" links
- Data: static for now (future: pull from DB)

### 5.6 Testimonials
- 3 testimonial cards: star ratings, quote text, avatar initials, name, credential

### 5.7 Stats
- 4 stat cards: 12K+ Students, 240+ Past Exams, 97% Satisfaction, 4.9★ Rating
- Pop animation on scroll

### 5.8 CTA Section
- Green gradient background
- "Ready to Take the Next Step?" headline
- "Launch Hub" + "Learn More" buttons

### 5.9 Footer
- 4-column grid: brand + social links, Platform links, Resources links, Company links
- Copyright bar at bottom

---

## Part 6: JavaScript (inline `<script>`)

### 6.1 Theme Toggle
- Read `localStorage.getItem('themeMode')` on load
- Toggle `.dark` on `<html>`
- Update icon (moon↔sun), label text, toggle thumb position
- Persist to localStorage

### 6.2 Dark Mode Flash Prevention
- Inline `<script>` in `<head>` (before body) to resolve theme from localStorage before first paint
- Same pattern as `app.blade.php`

### 6.3 Mobile Sidebar
- Hamburger click → add/remove `.open` class on sidebar + overlay
- Close on overlay click, Escape key, nav link click
- Close on resize if window > 1024px

### 6.4 Scroll Progress Bar
- `window.addEventListener('scroll')` → update width % of `.welcome-scroll-progress`

### 6.5 Scroll Reveal (IntersectionObserver)
- Observe `.reveal`, `.reveal-hero`, `.reveal-stagger` elements
- Add `.active` on intersect, remove on exit (bidirectional)
- Threshold: 0.15, rootMargin: -30px bottom

### 6.6 Stat Counter Pop
- IntersectionObserver on `#stats-grid .stat-card`
- Add `.pop` to `.stat-number` on intersect, remove after 500ms

### 6.7 Smooth Scroll
- Click handlers on `.welcome-sidebar-nav a[href^="#"]`
- `scrollIntoView({ behavior: 'smooth' })` with 16px offset

### 6.8 Coordinated Nav Hover
- Nav link mouseenter → add `.nav-hover` to sidebar (logo turns green)
- mouseleave → remove class

### 6.9 Hero Reveal on Load
- `setTimeout` 200ms → trigger `.active` on visible `.reveal-hero` and `.reveal` elements

---

## Part 7: Build & Polish
1. `npm run build` — verify Vite compiles without errors
2. Test dark/light mode toggle
3. Test mobile responsive (sidebar drawer, hamburger)
4. Test all anchor links scroll to correct sections
5. Test auth-aware buttons (guest → register, auth → feed)
6. Verify no CSS conflicts with app pages

---

## File Changes Summary

| File | Change |
|------|--------|
| `routes/web.php` | ✅ Line 26: conditional redirect |
| `resources/js/app.js` | ✅ Added 10 Lucide icon imports |
| `resources/views/welcome.blade.php` | 🔨 Full rewrite (prototype → Blade) |

## Dependencies
- Tailwind CSS (via Vite)
- Alpine.js (via Vite)
- Lucide (via app.js)
- No additional npm packages needed
