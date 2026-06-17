import Alpine from 'alpinejs';
import {
    createIcons,
    // Layout
    Home, FileText, CircleHelp, Clock, Bell, Bookmark,
    ChevronLeft, ChevronRight, ChevronUp, Menu, X, User,
    SquarePen, Settings, LogOut, LogIn, UserPlus,
    // Post card
    Ellipsis, File, Upload, ThumbsUp, MessageCircle, Send, Check,
    // Forms / pages
    ArrowLeft, AlignLeft, Image, Trash,
    Moon,
    Sun,
    Plus,
    // Focus timer
    RotateCcw, Play, Pause, SkipForward, Volume2, ChevronDown, Lock, AudioLines, Camera,
    // Quiz
    ArrowRight, Languages, Cpu, CircleCheck, CircleX, Award
} from 'lucide';

const icons = {
    Home, FileText, CircleHelp, Clock, Bell, Bookmark,
    ChevronLeft, ChevronRight, ChevronUp, Menu, X, User,
    SquarePen, Settings, LogOut, LogIn, UserPlus,
    Ellipsis, File, Upload, ThumbsUp, MessageCircle, Send, Check,
    ArrowLeft, AlignLeft, Image, Trash, Sun, Moon, Plus,
    RotateCcw, Play, Pause, SkipForward, Volume2, ChevronDown, Lock, AudioLines, Camera,
    ArrowRight, Languages, Cpu, CircleCheck, CircleX, Award
};

window.Alpine = Alpine;

// ── Light/Dark mode toggle (sidebar + guest auth portal) ──
// Applies instantly via the `.dark` class, remembers the choice in localStorage
// (read back before first paint by an inline <head> script), and — when a
// persistUrl is supplied (logged-in users) — saves it to the DB too.
Alpine.data('themeToggle', (opts = {}) => ({
    dark: document.documentElement.classList.contains('dark'),
    persistUrl: opts.persistUrl || '',

    init() {
        // Reflect mode changes made elsewhere on the page (e.g. the settings
        // page's segmented control) so the icon + label stay in sync live.
        // Kept as a named handler so destroy() can detach it — prevents listener
        // build-up if the component is ever re-initialised (SPA-style nav).
        this._onThemeModeChanged = (e) => {
            const mode = e.detail?.mode;
            this.dark = mode === 'dark'
                || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        };
        window.addEventListener('theme-mode-changed', this._onThemeModeChanged);
    },

    destroy() {
        window.removeEventListener('theme-mode-changed', this._onThemeModeChanged);
    },

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        const mode = this.dark ? 'dark' : 'light';
        localStorage.setItem('themeMode', mode);
        // Tell other on-page controls (the settings segmented buttons) to update.
        window.dispatchEvent(new CustomEvent('theme-mode-changed', { detail: { mode } }));

        if (this.persistUrl) {
            fetch(this.persistUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ theme_mode: mode }),
            }).catch(() => { /* offline — localStorage still holds the choice */ });
        }
    },
}));

// ── Live "System" mode: follow the OS light/dark setting in real time ──
// Track the *current effective* mode in-page, not the persisted one. It's seeded
// from the first-paint value and updated on every `theme-mode-changed`, which the
// settings page fires the moment "System" is clicked — so live-following works
// during an unsaved preview too, not only after Save.
let effectiveThemeMode = localStorage.getItem('themeMode') || 'light';
window.addEventListener('theme-mode-changed', (e) => {
    const mode = e.detail?.mode;
    if (['light', 'dark', 'system'].includes(mode)) effectiveThemeMode = mode;
});

// When the OS appearance flips (e.g. macOS auto dark mode at sunset) and we're in
// system mode, re-resolve `.dark` — the first-paint script only reads it once on
// load. Re-emit on the shared channel so the sidebar icon + settings segmented
// control stay in sync. (Fires only on a real OS change, so no loop with above.)
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (effectiveThemeMode !== 'system') return;
    document.documentElement.classList.toggle('dark', e.matches);
    window.dispatchEvent(new CustomEvent('theme-mode-changed', { detail: { mode: 'system' } }));
});

// ── Six-box OTP input (login/2FA challenge + password-reset) ──
// Six separate cells that mirror into one hidden field. Type to auto-advance,
// Backspace to step back, arrows to move, paste a full code to fill them all,
// and (when autosubmit) the form submits as soon as every cell is filled.
Alpine.data('otpInput', (opts = {}) => ({
    length: opts.length || 6,
    autosubmit: opts.autosubmit !== false,
    autofocus: opts.autofocus !== false,
    digits: Array(opts.length || 6).fill(''),

    get code() { return this.digits.join(''); },

    boxes() { return [...this.$root.querySelectorAll('[data-otp-box]')]; },

    init() {
        if (this.autofocus) this.$nextTick(() => this.boxes()[0]?.focus());
    },

    onInput(i, e) {
        // Keep only the last typed digit (handles fast typing / overtype).
        const digit = e.target.value.replace(/\D/g, '').slice(-1);
        this.digits[i] = digit;
        e.target.value = digit; // resync the cell even when a non-digit was rejected
        if (digit && i < this.length - 1) this.boxes()[i + 1]?.focus();
        this.maybeSubmit();
    },

    onKeydown(i, e) {
        if (e.key === 'Backspace' && !this.digits[i] && i > 0) {
            e.preventDefault();
            this.digits[i - 1] = '';
            this.boxes()[i - 1]?.focus();
        } else if (e.key === 'ArrowLeft' && i > 0) {
            e.preventDefault();
            this.boxes()[i - 1]?.focus();
        } else if (e.key === 'ArrowRight' && i < this.length - 1) {
            e.preventDefault();
            this.boxes()[i + 1]?.focus();
        }
    },

    onPaste(e) {
        const chars = (e.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, this.length).split('');
        if (!chars.length) return;
        for (let i = 0; i < this.length; i++) this.digits[i] = chars[i] || '';
        const next = Math.min(chars.length, this.length - 1);
        this.boxes()[next]?.focus();
        this.maybeSubmit();
    },

    maybeSubmit() {
        if (this.autosubmit && this.code.length === this.length) {
            const form = this.$root.closest('form');
            if (!form) return;
            // Set the hidden field synchronously — Alpine flushes the :value
            // binding on a later tick, but requestSubmit() fires immediately.
            const hidden = this.$root.querySelector('[data-otp-value]');
            if (hidden) hidden.value = this.code;
            form.requestSubmit();
        }
    },
}));

// ── OTP expiry countdown (challenge screen) ──
// Counts down the seconds left on the current code, server-computed so it's
// immune to client-clock skew. At 0 it flips `expired` so the view can show an
// "expired" message and steer the user to resend. A resend reloads the page,
// which re-seeds a fresh countdown.
Alpine.data('otpCountdown', (opts = {}) => ({
    remaining: Math.max(0, opts.seconds || 0),
    intervalId: null,

    init() {
        if (this.remaining > 0) {
            this.intervalId = setInterval(() => this.tick(), 1000);
        }
    },

    tick() {
        if (this.remaining > 0) this.remaining--;
        if (this.remaining <= 0) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    },

    get expired() { return this.remaining <= 0; },

    get display() {
        const m = Math.floor(this.remaining / 60);
        const s = this.remaining % 60;
        return `${m}:${String(s).padStart(2, '0')}`;
    },

    destroy() { clearInterval(this.intervalId); },
}));

// ── Auth portal: pill toggle, live register validation, and the knowledge tree ──
Alpine.data('portal', (opts = {}) => ({
    mode: opts.mode || 'login',         // the chosen tab — flips first (pill + card tilt)
    activeForm: opts.mode || 'login',   // the form actually on screen — flips mid-switch
    switching: false,                   // true while the tilt/shine window is open
    mounted: false,                     // gates the container height transition (avoids 0→h flash)
    reg: {
        displayName: opts.displayName || '',
        username: opts.username || '',
        email: opts.email || '',
        password: '',
        confirmation: '',
    },
    showPw: false,
    steps: [false, false, false, false, false],
    strength: 0,

    // username suggestion + availability
    suggestUrl: opts.suggestUrl || '',
    checkUrl: opts.checkUrl || '',
    usernameEdited: !!opts.username,
    usernameStatus: '',          // '' | 'checking' | 'available' | 'taken' | 'invalid'
    suggestions: [],
    _dnTimer: null,
    _unTimer: null,

    // transition internals
    _activeFormEl: null,
    _ro: null,
    _swapT1: null,
    _swapT2: null,

    init() {
        this.validate();
        if (this.reg.username) this.checkUsername();

        // Size the stage to the active form, then keep it synced as that form's
        // content grows/shrinks (suggestion chips, strength meter, errors…).
        this._activeFormEl = this.mode === 'login' ? this.$refs.loginForm : this.$refs.registerForm;
        this.fitHeight();
        this.$refs.formContainer.style.minHeight = '0px';   // exact height now owns it
        this._ro = new ResizeObserver(() => this.fitHeight());
        this._ro.observe(this.$refs.loginForm);
        this._ro.observe(this.$refs.registerForm);

        // Enable the height transition only after the first (instant) measurement.
        requestAnimationFrame(() => { this.mounted = true; });
    },

    fitHeight() {
        if (this._activeFormEl) {
            this.$refs.formContainer.style.height = this._activeFormEl.offsetHeight + 'px';
        }
    },

    get strengthLabel() { return ['', 'Weak', 'Fair', 'Strong'][this.strength] || ''; },
    get strengthPct() { return [0, 34, 67, 100][this.strength] || 0; },

    validate() {
        const r = this.reg;
        const usernameOk = /^[a-z0-9]{3,30}$/.test(r.username.trim()) && this.usernameStatus !== 'taken';
        this.steps = [
            r.displayName.trim().length >= 2,
            usernameOk,
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(r.email.trim()),
            r.password.length >= 8,
            r.password.length >= 8 && r.confirmation === r.password,
        ];
        let s = 0;
        if (r.password.length >= 8) s++;
        if (/[a-z]/.test(r.password) && /[A-Z]/.test(r.password)) s++;
        if (/\d/.test(r.password) || /[^A-Za-z0-9]/.test(r.password)) s++;
        this.strength = r.password ? Math.max(1, s) : 0;
    },

    // Display name → debounced username suggestions
    onDisplayName() {
        this.validate();
        clearTimeout(this._dnTimer);
        const name = this.reg.displayName.trim();
        if (name.length < 2 || !this.suggestUrl) { this.suggestions = []; return; }
        this._dnTimer = setTimeout(() => this.fetchSuggestions(name), 400);
    },

    async fetchSuggestions(name) {
        try {
            const res = await fetch(`${this.suggestUrl}?name=${encodeURIComponent(name)}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();
            this.suggestions = data.usernames || [];
            // Auto-fill the first suggestion only if the user hasn't typed their own.
            if (!this.usernameEdited && this.suggestions.length) {
                this.reg.username = this.suggestions[0];
                this.usernameStatus = 'available';
                this.validate();
            }
        } catch (e) { /* offline — server still enforces uniqueness on submit */ }
    },

    applySuggestion(name) {
        this.reg.username = name;
        this.usernameEdited = true;
        this.usernameStatus = 'available';
        this.validate();
    },

    // Username edits → force lowercase, then debounced availability check
    onUsername() {
        this.reg.username = this.reg.username.toLowerCase();
        this.usernameEdited = true;
        this.validate();
        clearTimeout(this._unTimer);
        this._unTimer = setTimeout(() => this.checkUsername(), 400);
    },

    async checkUsername() {
        const u = this.reg.username.trim();
        if (!/^[a-z0-9]{3,30}$/.test(u)) {
            this.usernameStatus = u ? 'invalid' : '';
            this.validate();
            return;
        }
        if (!this.checkUrl) return;
        this.usernameStatus = 'checking';
        try {
            const res = await fetch(`${this.checkUrl}?username=${encodeURIComponent(u)}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            this.usernameStatus = data.available ? 'available' : 'taken';
        } catch (e) {
            this.usernameStatus = '';   // unknown — submit will validate
        }
        this.validate();
    },

    // Login ⇄ register cross-slide, choreographed on the prototype's timeline:
    //   t=0    pill slides + card tilts/shines (mode flips now)
    //   t=250  forms swap (old → exit/blur out, new → active/slide in) + stage resizes
    //   t=700  card settles; the parked form resets off to the right (enter)
    go(to) {
        if (to === this.mode || this.switching) return;

        const fromEl = this.mode === 'login' ? this.$refs.loginForm : this.$refs.registerForm;
        const toEl = to === 'login' ? this.$refs.loginForm : this.$refs.registerForm;

        this.switching = true;
        this.mode = to;

        clearTimeout(this._swapT1);
        clearTimeout(this._swapT2);

        this._swapT1 = setTimeout(() => {
            fromEl.classList.remove('active');
            fromEl.classList.add('exit');
            toEl.classList.remove('enter', 'exit');
            toEl.classList.add('active');

            this.activeForm = to;
            this._activeFormEl = toEl;
            this.fitHeight();
        }, 250);

        this._swapT2 = setTimeout(() => {
            this.switching = false;
            fromEl.classList.remove('exit');
            fromEl.classList.add('enter');
        }, 700);
    },
}));

// ── Quiz setup wizard (category → level → section → count) ──
// Drives the quiz index selection from the config catalog passed in from Blade.
// Resets downstream choices when an upstream one changes so you can't submit a
// stale level/section. The hidden form inputs mirror this state for the POST.
Alpine.data('quizSetup', (catalog = {}, counts = []) => ({
    catalog,
    counts,
    category: '',
    level: '',
    section: '',
    count: null,

    get levels() {
        return this.category ? (this.catalog[this.category]?.levels || {}) : {};
    },
    get sections() {
        if (!this.category || !this.level) return {};
        return this.catalog[this.category]?.levels?.[this.level]?.sections || {};
    },
    get needsSection() {
        return Object.keys(this.sections).length > 0;
    },
    get canStart() {
        return !!this.category && !!this.level
            && (!this.needsSection || !!this.section)
            && !!this.count;
    },

    selectCategory(key) {
        if (this.category === key) return;
        this.category = key;
        this.level = '';
        this.section = '';
        this.count = null;
    },
    selectLevel(key) {
        if (this.level === key) return;
        this.level = key;
        this.section = '';
    },
    selectSection(key) {
        this.section = key;
    },
}));

// ── Quiz player (one question at a time) ──
// Holds the answer map (questionId → A/B/C/D) for the whole quiz; every question
// stays in the DOM (x-show) so a single form submit posts them all. Correct
// answers are never sent here — grading is server-side.
Alpine.data('quizPlayer', (questions = []) => ({
    questions,
    current: 0,
    answers: {},

    get total() { return this.questions.length; },
    get answeredCount() { return Object.keys(this.answers).length; },
    get allAnswered() { return this.answeredCount === this.total; },
    get progress() {
        return this.total ? Math.round((this.current + 1) / this.total * 100) : 0;
    },

    select(qid, letter) { this.answers[qid] = letter; },
    next() { if (this.current < this.total - 1) this.current++; },
    prev() { if (this.current > 0) this.current--; },
    goTo(i) { this.current = i; },

    onSubmit(e) {
        if (this.allAnswered) return;
        const left = this.total - this.answeredCount;
        if (!confirm(`You have ${left} unanswered question${left === 1 ? '' : 's'}. Submit anyway?`)) {
            e.preventDefault();
        }
    },
}));

document.addEventListener('alpine:initialized', () => {
    createIcons({ icons });
});

Alpine.start();


window.renderIcons = (root = document) => createIcons({ icons, root });
window.appendWithIcons = (container, html) => {
    const marker = container.lastElementChild;
    container.insertAdjacentHTML('beforeend', html);

    let node = marker ? marker.nextElementSibling : container.firstElementChild;
    while (node) {
        createIcons({ icons, root: node });
        node = node.nextElementSibling;
    }
};

// ── Button loading state ──────────────────────────────────────────────
// Swaps a submit button's contents for a small spinning leaf (.leaf-spin in
// app.css) and disables it while a request is in flight. Shared by the global
// submit hook below (native full-page form submits) and the comment drawer's
// AJAX submit handler.
const LEAF_SPIN_SVG =
    '<svg class="leaf-spin h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"' +
    ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>' +
    '<path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>';

window.showButtonLoading = (btn, text) => {
    if (!btn || btn.dataset.loadingActive) return;
    btn.dataset.loadingActive = '1';
    btn.dataset.loadingPrev = btn.innerHTML;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    const label = (text ?? btn.dataset.loadingText ?? '').trim();
    btn.innerHTML =
        '<span class="inline-flex items-center justify-center gap-2">' +
        LEAF_SPIN_SVG +
        (label ? `<span>${label}</span>` : '') +
        '</span>';
};

window.resetButtonLoading = (btn) => {
    if (!btn || !btn.dataset.loadingActive) return;
    btn.innerHTML = btn.dataset.loadingPrev || '';
    btn.disabled = false;
    btn.removeAttribute('aria-busy');
    delete btn.dataset.loadingActive;
    delete btn.dataset.loadingPrev;
    window.renderIcons(btn); // re-render any restored Lucide <i> (e.g. the trash icon)
};

// Any <form data-loading> shows the spinner on its submit button when it submits.
// Skips submits already cancelled (confirm()) or handled elsewhere (drawer AJAX
// preventDefault), since those set defaultPrevented before this bubbles to document.
document.addEventListener('submit', (e) => {
    if (e.defaultPrevented) return;
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-loading')) return;
    window.showButtonLoading(form.querySelector('[type=submit], button:not([type])'));
});

// bfcache back-nav can restore a page with a button still spinning — un-stick them.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-loading-active]').forEach((b) => window.resetButtonLoading(b));
});