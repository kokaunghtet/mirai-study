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
    RotateCcw, Play, Pause, SkipForward, Volume2, ChevronDown, Lock, AudioLines,
} from 'lucide';

const icons = {
    Home, FileText, CircleHelp, Clock, Bell, Bookmark,
    ChevronLeft, ChevronRight, ChevronUp, Menu, X, User,
    SquarePen, Settings, LogOut, LogIn, UserPlus,
    Ellipsis, File, Upload, ThumbsUp, MessageCircle, Send, Check,
    ArrowLeft, AlignLeft, Image, Trash, Sun, Moon, Plus,
    RotateCcw, Play, Pause, SkipForward, Volume2, ChevronDown, Lock, AudioLines
};

window.Alpine = Alpine;

// ── Light/Dark mode toggle (sidebar + guest auth portal) ──
// Applies instantly via the `.dark` class, remembers the choice in localStorage
// (read back before first paint by an inline <head> script), and — when a
// persistUrl is supplied (logged-in users) — saves it to the DB too.
Alpine.data('themeToggle', (opts = {}) => ({
    dark: document.documentElement.classList.contains('dark'),
    persistUrl: opts.persistUrl || '',

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        const mode = this.dark ? 'dark' : 'light';
        localStorage.setItem('themeMode', mode);

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
        const toEl   = to === 'login' ? this.$refs.loginForm : this.$refs.registerForm;

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