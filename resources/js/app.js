import Alpine from "alpinejs";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
window.Pusher = Pusher;

if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
        enabledTransports: ["ws", "wss"],
    });
}
import collapse from "@alpinejs/collapse";
import {
    createIcons,
    // Layout
    Home,
    FileText,
    CircleHelp,
    Clock,
    Bell,
    Bookmark,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    Menu,
    X,
    User,
    SquarePen,
    Settings,
    LogOut,
    LogIn,
    UserPlus,
    // Post card
    Ellipsis,
    File,
    Upload,
    ThumbsUp,
    MessageCircle,
    Send,
    Check,
    // Forms / pages
    ArrowLeft,
    AlignLeft,
    Image,
    Trash,
    Moon,
    Sun,
    Plus,
    // Focus timer
    RotateCcw,
    Play,
    Pause,
    SkipForward,
    Volume2,
    ChevronDown,
    Lock,
    AudioLines,
    Camera,
    // Quiz
    ArrowRight,
    Languages,
    Cpu,
    CircleCheck,
    CircleX,
    Award,
    Trash2,
    Brain,
    // Exams
    Folder,
    FolderOpen,
    Download,
    LoaderCircle,
    Eye,
    EyeOff,
    // Admin upload
    Sparkles,
    TriangleAlert,
    ClipboardList,
    GitBranch,
    Save,
    LayoutDashboard,
    Users,
    Flag,
    BookOpen,
    BarChart2,
    // Admin dashboard
    TrendingUp,
    TrendingDown,
    Activity,
    Inbox,
    CheckCircle,
    Server,
    ShieldBan,
    Shield,
    ShieldCheck,
    ShieldAlert,
    LineChart,
    BarChart3,
    PieChart,
    Ban,
    Clock4,
    ClockArrowUp,
    XCircle,
    ArrowUp,
    ArrowDown,
    Rocket,
    GraduationCap,
    Star,
    Heart,
    Smartphone,
    Calendar,
    Box,
    Navigation,
    Code,
    Monitor,
    Globe,
    PlayCircle,
    GitBranchPlus,
    Timer,
    UserCircle,
    Lightbulb,
    Map,
} from "lucide";

const icons = {
    Home,
    FileText,
    CircleHelp,
    Clock,
    Bell,
    Bookmark,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    Menu,
    X,
    User,
    SquarePen,
    Settings,
    LogOut,
    LogIn,
    UserPlus,
    Ellipsis,
    File,
    Upload,
    ThumbsUp,
    MessageCircle,
    Send,
    Check,
    ArrowLeft,
    AlignLeft,
    Image,
    Trash,
    Sun,
    Moon,
    Plus,
    RotateCcw,
    Play,
    Pause,
    SkipForward,
    Volume2,
    ChevronDown,
    Lock,
    AudioLines,
    Camera,
    ArrowRight,
    Languages,
    Cpu,
    CircleCheck,
    CircleX,
    Award,
    Trash2,
    Brain,
    Folder,
    FolderOpen,
    Download,
    LoaderCircle,
    Eye,
    EyeOff,
    Sparkles,
    TriangleAlert,
    ClipboardList,
    GitBranch,
    Save,
    LayoutDashboard,
    Users,
    Flag,
    BookOpen,
    BarChart2,
    TrendingUp,
    TrendingDown,
    Activity,
    Inbox,
    CheckCircle,
    Server,
    ShieldBan,
    Shield,
    ShieldCheck,
    ShieldAlert,
    LineChart,
    BarChart3,
    PieChart,
    Ban,
    Clock4,
    ClockArrowUp,
    XCircle,
    ArrowUp,
    ArrowDown,
    Rocket,
    GraduationCap,
    Star,
    Heart,
    Smartphone,
    Calendar,
    Box,
    Navigation,
    Code,
    Monitor,
    Globe,
    PlayCircle,
    GitBranchPlus,
    Timer,
    UserCircle,
    Lightbulb,
    Map,
};

Alpine.plugin(collapse);
window.Alpine = Alpine;

// ── Global fetch interceptor: banned-user 403 → appeal modal ──
(function () {
    const _orig = window.fetch;
    window.fetch = async function (...args) {
        const res = await _orig.apply(this, args);
        if (res.status === 403) {
            const clone = res.clone();
            try {
                const data = await clone.json();
                if (data.banned) {
                    window.dispatchEvent(new CustomEvent("open-appeal-modal"));
                }
            } catch (_) {}
        }
        return res;
    };
})();

// ── Light/Dark mode toggle (sidebar + guest auth portal) ──
// Applies instantly via the `.dark` class, remembers the choice in localStorage
// (read back before first paint by an inline <head> script), and — when a
// persistUrl is supplied (logged-in users) — saves it to the DB too.
Alpine.data("themeToggle", (opts = {}) => ({
    dark: document.documentElement.classList.contains("dark"),
    persistUrl: opts.persistUrl || "",

    init() {
        // Reflect mode changes made elsewhere on the page (e.g. the settings
        // page's segmented control) so the icon + label stay in sync live.
        // Kept as a named handler so destroy() can detach it — prevents listener
        // build-up if the component is ever re-initialised (SPA-style nav).
        this._onThemeModeChanged = (e) => {
            const mode = e.detail?.mode;
            this.dark =
                mode === "dark" ||
                (mode === "system" &&
                    window.matchMedia("(prefers-color-scheme: dark)").matches);
        };
        window.addEventListener("theme-mode-changed", this._onThemeModeChanged);
    },

    destroy() {
        window.removeEventListener(
            "theme-mode-changed",
            this._onThemeModeChanged,
        );
    },

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle("dark", this.dark);
        const mode = this.dark ? "dark" : "light";
        localStorage.setItem("themeMode", mode);
        // Tell other on-page controls (the settings segmented buttons) to update.
        window.dispatchEvent(
            new CustomEvent("theme-mode-changed", { detail: { mode } }),
        );

        if (this.persistUrl) {
            fetch(this.persistUrl, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector("meta[name=csrf-token]")
                            ?.content || "",
                    Accept: "application/json",
                },
                body: JSON.stringify({ theme_mode: mode }),
            }).catch(() => {
                /* offline — localStorage still holds the choice */
            });
        }
    },
}));

// ── Live "System" mode: follow the OS light/dark setting in real time ──
// Track the *current effective* mode in-page, not the persisted one. It's seeded
// from the first-paint value and updated on every `theme-mode-changed`, which the
// settings page fires the moment "System" is clicked — so live-following works
// during an unsaved preview too, not only after Save.
let effectiveThemeMode = localStorage.getItem("themeMode") || "light";
window.addEventListener("theme-mode-changed", (e) => {
    const mode = e.detail?.mode;
    if (["light", "dark", "system"].includes(mode)) effectiveThemeMode = mode;
});

// When the OS appearance flips (e.g. macOS auto dark mode at sunset) and we're in
// system mode, re-resolve `.dark` — the first-paint script only reads it once on
// load. Re-emit on the shared channel so the sidebar icon + settings segmented
// control stay in sync. (Fires only on a real OS change, so no loop with above.)
window
    .matchMedia("(prefers-color-scheme: dark)")
    .addEventListener("change", (e) => {
        if (effectiveThemeMode !== "system") return;
        document.documentElement.classList.toggle("dark", e.matches);
        window.dispatchEvent(
            new CustomEvent("theme-mode-changed", {
                detail: { mode: "system" },
            }),
        );
    });

// ── Six-box OTP input (login/2FA challenge + password-reset) ──
// Six separate cells that mirror into one hidden field. Type to auto-advance,
// Backspace to step back, arrows to move, paste a full code to fill them all,
// and (when autosubmit) the form submits as soon as every cell is filled.
Alpine.data("otpInput", (opts = {}) => ({
    length: opts.length || 6,
    autosubmit: opts.autosubmit !== false,
    autofocus: opts.autofocus !== false,
    digits: Array(opts.length || 6).fill(""),

    get code() {
        return this.digits.join("");
    },

    boxes() {
        return [...this.$root.querySelectorAll("[data-otp-box]")];
    },

    init() {
        if (this.autofocus) this.$nextTick(() => this.boxes()[0]?.focus());
    },

    onInput(i, e) {
        // Keep only the last typed digit (handles fast typing / overtype).
        const digit = e.target.value.replace(/\D/g, "").slice(-1);
        this.digits[i] = digit;
        e.target.value = digit; // resync the cell even when a non-digit was rejected
        if (digit && i < this.length - 1) this.boxes()[i + 1]?.focus();
        this.maybeSubmit();
    },

    onKeydown(i, e) {
        if (e.key === "Backspace" && !this.digits[i] && i > 0) {
            e.preventDefault();
            this.digits[i - 1] = "";
            this.boxes()[i - 1]?.focus();
        } else if (e.key === "ArrowLeft" && i > 0) {
            e.preventDefault();
            this.boxes()[i - 1]?.focus();
        } else if (e.key === "ArrowRight" && i < this.length - 1) {
            e.preventDefault();
            this.boxes()[i + 1]?.focus();
        }
    },

    onPaste(e) {
        const chars = (e.clipboardData?.getData("text") || "")
            .replace(/\D/g, "")
            .slice(0, this.length)
            .split("");
        if (!chars.length) return;
        for (let i = 0; i < this.length; i++) this.digits[i] = chars[i] || "";
        const next = Math.min(chars.length, this.length - 1);
        this.boxes()[next]?.focus();
        this.maybeSubmit();
    },

    maybeSubmit() {
        if (this.autosubmit && this.code.length === this.length) {
            const form = this.$root.closest("form");
            if (!form) return;
            // Set the hidden field synchronously — Alpine flushes the :value
            // binding on a later tick, but requestSubmit() fires immediately.
            const hidden = this.$root.querySelector("[data-otp-value]");
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
Alpine.data("otpCountdown", (opts = {}) => ({
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

    get expired() {
        return this.remaining <= 0;
    },

    get display() {
        const m = Math.floor(this.remaining / 60);
        const s = this.remaining % 60;
        return `${m}:${String(s).padStart(2, "0")}`;
    },

    destroy() {
        clearInterval(this.intervalId);
    },
}));

// ── Auth portal: pill toggle, live register validation, and the knowledge tree ──
Alpine.data("portal", (opts = {}) => ({
    mode: opts.mode || "login", // the chosen tab — flips first (pill + card tilt)
    activeForm: opts.mode || "login", // the form actually on screen — flips mid-switch
    switching: false, // true while the tilt/shine window is open
    mounted: false, // gates the container height transition (avoids 0→h flash)
    reg: {
        displayName: opts.displayName || "",
        username: opts.username || "",
        email: opts.email || "",
        password: "",
        confirmation: "",
    },
    showPw: false,
    steps: [false, false, false, false, false],
    strength: 0,

    // username suggestion + availability
    suggestUrl: opts.suggestUrl || "",
    checkUrl: opts.checkUrl || "",
    usernameEdited: !!opts.username,
    usernameStatus: "", // '' | 'checking' | 'available' | 'taken' | 'invalid'
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
        this._activeFormEl =
            this.mode === "login"
                ? this.$refs.loginForm
                : this.$refs.registerForm;
        this.fitHeight();
        this.$refs.formContainer.style.minHeight = "0px"; // exact height now owns it
        this._ro = new ResizeObserver(() => this.fitHeight());
        this._ro.observe(this.$refs.loginForm);
        this._ro.observe(this.$refs.registerForm);

        // Enable the height transition only after the first (instant) measurement.
        requestAnimationFrame(() => {
            this.mounted = true;
        });
    },

    fitHeight() {
        if (this._activeFormEl) {
            this.$refs.formContainer.style.height =
                this._activeFormEl.offsetHeight + "px";
        }
    },

    get strengthLabel() {
        return ["", "Weak", "Fair", "Strong"][this.strength] || "";
    },
    get strengthPct() {
        return [0, 34, 67, 100][this.strength] || 0;
    },

    validate() {
        const r = this.reg;
        const usernameOk =
            /^[a-z0-9]{3,30}$/.test(r.username.trim()) &&
            this.usernameStatus !== "taken";
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
        if (name.length < 2 || !this.suggestUrl) {
            this.suggestions = [];
            return;
        }
        this._dnTimer = setTimeout(() => this.fetchSuggestions(name), 400);
    },

    async fetchSuggestions(name) {
        try {
            const res = await fetch(
                `${this.suggestUrl}?name=${encodeURIComponent(name)}`,
                {
                    headers: { Accept: "application/json" },
                },
            );
            if (!res.ok) return;
            const data = await res.json();
            this.suggestions = data.usernames || [];
            // Auto-fill the first suggestion only if the user hasn't typed their own.
            if (!this.usernameEdited && this.suggestions.length) {
                this.reg.username = this.suggestions[0];
                this.usernameStatus = "available";
                this.validate();
            }
        } catch (e) {
            /* offline — server still enforces uniqueness on submit */
        }
    },

    applySuggestion(name) {
        this.reg.username = name;
        this.usernameEdited = true;
        this.usernameStatus = "available";
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
            this.usernameStatus = u ? "invalid" : "";
            this.validate();
            return;
        }
        if (!this.checkUrl) return;
        this.usernameStatus = "checking";
        try {
            const res = await fetch(
                `${this.checkUrl}?username=${encodeURIComponent(u)}`,
                {
                    headers: { Accept: "application/json" },
                },
            );
            const data = await res.json();
            this.usernameStatus = data.available ? "available" : "taken";
        } catch (e) {
            this.usernameStatus = ""; // unknown — submit will validate
        }
        this.validate();
    },

    // Login ⇄ register cross-slide, choreographed on the prototype's timeline:
    //   t=0    pill slides + card tilts/shines (mode flips now)
    //   t=250  forms swap (old → exit/blur out, new → active/slide in) + stage resizes
    //   t=700  card settles; the parked form resets off to the right (enter)
    go(to) {
        if (to === this.mode || this.switching) return;

        const fromEl =
            this.mode === "login"
                ? this.$refs.loginForm
                : this.$refs.registerForm;
        const toEl =
            to === "login" ? this.$refs.loginForm : this.$refs.registerForm;

        this.switching = true;
        this.mode = to;

        clearTimeout(this._swapT1);
        clearTimeout(this._swapT2);

        this._swapT1 = setTimeout(() => {
            fromEl.classList.remove("active");
            fromEl.classList.add("exit");
            toEl.classList.remove("enter", "exit");
            toEl.classList.add("active");

            this.activeForm = to;
            this._activeFormEl = toEl;
            this.fitHeight();
        }, 250);

        this._swapT2 = setTimeout(() => {
            this.switching = false;
            fromEl.classList.remove("exit");
            fromEl.classList.add("enter");
        }, 700);
    },
}));

// ── Quiz setup wizard (category → level → section → count) ──
// Drives the quiz index selection from the config catalog passed in from Blade.
// Resets downstream choices when an upstream one changes so you can't submit a
// stale level/section. The hidden form inputs mirror this state for the POST.
Alpine.data("quizSetup", (catalog = {}, counts = []) => ({
    catalog,
    counts,
    category: "",
    level: "",
    section: "",
    count: null,

    get levels() {
        return this.category ? this.catalog[this.category]?.levels || {} : {};
    },
    get sections() {
        if (!this.category || !this.level) return {};
        return (
            this.catalog[this.category]?.levels?.[this.level]?.sections || {}
        );
    },
    get needsSection() {
        return Object.keys(this.sections).length > 0;
    },
    get canStart() {
        return (
            !!this.category &&
            !!this.level &&
            (!this.needsSection || !!this.section) &&
            !!this.count
        );
    },

    selectCategory(key) {
        if (this.category === key) return;
        this.category = key;
        this.level = "";
        this.section = "";
        this.count = null;
    },
    selectLevel(key) {
        if (this.level === key) return;
        this.level = key;
        this.section = "";
    },
    selectSection(key) {
        this.section = key;
    },
}));

// ── Quiz player (one question at a time) ──
// Holds the answer map (questionId → A/B/C/D) for the whole quiz; every question
// stays in the DOM (x-show) so a single form submit posts them all. Correct
// answers are never sent here — grading is server-side.
Alpine.data("quizPlayer", (questions = [], attemptId = null) => ({
    questions,
    attemptId,
    current: 0,
    answers: {},
    submitting: false,

    get storageKey() {
        return `quiz-progress-${this.attemptId}`;
    },
    get total() {
        return this.questions.length;
    },
    get answeredCount() {
        return Object.keys(this.answers).length;
    },
    get allAnswered() {
        return this.answeredCount === this.total;
    },
    get progress() {
        return this.total
            ? Math.round(((this.current + 1) / this.total) * 100)
            : 0;
    },

    init() {
        // Restore answers + position saved on this device so navigating away and
        // back (or an accidental tab click) doesn't lose progress.
        try {
            const saved = JSON.parse(
                localStorage.getItem(this.storageKey) || "{}",
            );
            if (saved.answers) this.answers = saved.answers;
            if (Number.isInteger(saved.current) && saved.current < this.total) {
                this.current = saved.current;
            }
        } catch (e) {
            /* no/!corrupt saved progress — start fresh */
        }

        // Warn before leaving mid-quiz (full-page nav from a sidebar tab, refresh,
        // tab close). Skipped once we're intentionally submitting. Named so destroy()
        // can detach it.
        this._onBeforeUnload = (e) => {
            if (this.submitting || this.answeredCount === 0) return;
            e.preventDefault();
            e.returnValue = "";
        };
        window.addEventListener("beforeunload", this._onBeforeUnload);

        // Intercept internal navigation clicks to show a themed leave-quiz modal.
        this._onClickNav = async (e) => {
            if (this.submitting || this.answeredCount === 0) return;
            const link = e.target.closest("a");
            if (!link) return;
            if (link.target === "_blank") return;
            if (link.hasAttribute("download")) return;
            if (link.getAttribute("href") === "#") return;
            if (link.hasAttribute("data-no-nav")) return;
            const href = link.getAttribute("href");
            if (!href || href.startsWith("#") || href.startsWith("javascript:"))
                return;
            try {
                const url = new URL(href, window.location.origin);
                if (url.origin !== window.location.origin) return;
            } catch (_) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            const confirmed = await window.confirmDialog({
                title: "Leave quiz?",
                message:
                    "Your progress is saved on this device, but the quiz stays in progress. Leave anyway?",
                confirmLabel: "Leave",
            });
            if (confirmed) {
                this.submitting = true;
                window.location.href = href;
            }
        };
        document.addEventListener("click", this._onClickNav, { capture: true });
    },

    destroy() {
        window.removeEventListener("beforeunload", this._onBeforeUnload);
        document.removeEventListener("click", this._onClickNav, {
            capture: true,
        });
    },

    persist() {
        try {
            localStorage.setItem(
                this.storageKey,
                JSON.stringify({
                    answers: this.answers,
                    current: this.current,
                }),
            );
        } catch (e) {
            /* storage unavailable — answers still live in memory */
        }
    },

    select(qid, letter) {
        this.answers[qid] = letter;
        this.persist();
    },
    next() {
        if (this.current < this.total - 1) {
            this.current++;
            this.persist();
        }
    },
    prev() {
        if (this.current > 0) {
            this.current--;
            this.persist();
        }
    },
    goTo(i) {
        this.current = i;
        this.persist();
    },

    async onSubmit(e) {
        // Re-entry guard: requestSubmit() below re-fires this @submit handler.
        // Once submitting, let the native POST proceed untouched.
        if (this.submitting) return;

        if (!this.allAnswered) {
            e.preventDefault();
            const left = this.total - this.answeredCount;
            const confirmed = await window.confirmDialog({
                title: "Submit quiz?",
                message: `You have ${left} unanswered question${left === 1 ? "" : "s"}. Submit anyway?`,
                confirmLabel: "Submit anyway",
            });
            if (!confirmed) return;
            // Drop saved progress so a finished quiz doesn't resurface as "in progress".
            this.submitting = true;
            try {
                localStorage.removeItem(this.storageKey);
            } catch (e) {
                /* ignore */
            }
            e.target.requestSubmit();
            return;
        }
        // All answered: let the native submit proceed; just mark state + clear progress.
        this.submitting = true;
        try {
            localStorage.removeItem(this.storageKey);
        } catch (e) {
            /* ignore */
        }
    },
}));

// ── Resume banner (quiz index) ──
// Reads the per-attempt progress saved by quizPlayer so the index can show how many
// questions were answered before the user navigated away.
Alpine.data("resumeBanner", (attemptId, total) => ({
    attemptId,
    total,
    answered: 0,
    _confirmed: false,
    init() {
        try {
            const saved = JSON.parse(
                localStorage.getItem(`quiz-progress-${attemptId}`) || "{}",
            );
            this.answered = Object.keys(saved.answers || {}).length;
        } catch (e) {
            /* no saved progress on this device */
        }
    },
    async abort(e) {
        // Re-entry guard: requestSubmit() below re-fires this @submit handler.
        if (this._confirmed) return;

        e.preventDefault();
        const confirmed = await window.confirmDialog({
            title: "Discard quiz?",
            message: "Discard this quiz? Your progress will be lost.",
            confirmLabel: "Discard",
            danger: true,
        });
        if (!confirmed) return;
        // Drop the saved progress so a discarded quiz leaves nothing behind.
        this._confirmed = true;
        try {
            localStorage.removeItem(`quiz-progress-${this.attemptId}`);
        } catch (e) {
            /* ignore */
        }
        e.target.requestSubmit();
    },
}));

// Exam browser — folder cards → level pop-out → paper detail. The full
// category→level tree (with paper counts) is passed in from the server; the
// paper list for a level is fetched as JSON on demand, then filtered/sorted/
// grouped entirely client-side.
Alpine.data("examBrowser", (payload = {}) => ({
    categories: payload.categories ?? [],
    view: "folders", // 'folders' | 'detail'
    openId: null, // id of the expanded folder (level pop-out)
    curCat: null,
    curLevel: null,
    papers: [],
    loading: false,
    filterPart: "all", // 'all' | 'AM' | 'PM'
    filterSession: "all", // 'all' | category-specific month (April/October | July/December)
    sortDir: "desc", // 'desc' (newest first) | 'asc'

    // Per-category artwork (gradient header + motif + tagline), keyed by name.
    art(cat) {
        const map = {
            JLPT: {
                grad: "linear-gradient(135deg,#1e3a8a,#60a5fa)",
                motif: "⛩️",
                tag: "Japanese proficiency",
            },
            ITPEC: {
                grad: "linear-gradient(135deg,#065f46,#34d399)",
                motif: "🖥️",
                tag: "Industry certified",
            },
        };
        return (
            map[cat.name] || {
                grad: "linear-gradient(135deg,#334155,#64748b)",
                motif: "📁",
                tag: "Past papers",
            }
        );
    },

    // ── Folders ──
    toggleFolder(id) {
        this.openId = this.openId === id ? null : id;
    },
    get openCat() {
        return this.categories.find((c) => c.id === this.openId) || null;
    },

    // ── Open a level → detail view ──
    openLevel(cat, lvl) {
        this.curCat = cat;
        this.curLevel = lvl;
        this.openId = cat.id; // remembered for the breadcrumb
        this.filterPart = "all";
        this.filterSession = "all";
        this.sortDir = "desc";
        this.view = "detail";
        this.loadPapers();
        window.scrollTo({ top: 0, behavior: "smooth" });
    },

    selectLevel(lvl) {
        // tab switch inside detail
        if (this.curLevel && this.curLevel.id === lvl.id) return;
        this.curLevel = lvl;
        this.filterPart = "all";
        this.filterSession = "all";
        this.sortDir = "desc";
        this.loadPapers();
    },

    backToFolders() {
        this.view = "folders";
        if (this.curCat) this.openId = this.curCat.id;
        window.scrollTo({ top: 0, behavior: "smooth" });
    },

    async loadPapers() {
        this.loading = true;
        this.papers = [];
        try {
            const res = await fetch(
                `/exams/${this.curCat.id}/${this.curLevel.id}`,
                {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                },
            );
            const data = await res.json();
            this.papers = data.papers ?? [];
        } catch (e) {
            this.papers = [];
        } finally {
            this.loading = false;
        }
    },

    // ── Filter + sort + group ──
    get hasParts() {
        return this.papers.some((p) => p.part);
    },
    // Category-aware session months: ITPEC runs April/October, JLPT runs July/December.
    get sessionOptions() {
        const map = { ITPEC: ["April", "October"], JLPT: ["July", "December"] };
        return (this.curCat && map[this.curCat.name]) || [];
    },
    get hasSessions() {
        return (
            this.sessionOptions.length > 0 && this.papers.some((p) => p.session)
        );
    },
    get hasAnswers() {
        return this.papers.some((p) => p.doc_type === "answer");
    },
    get hasCombined() {
        return this.papers.some((p) => p.doc_type === "combined");
    },
    // Once any group beyond plain questions exists, render the grouped layout.
    get isGrouped() {
        return this.hasAnswers || this.hasCombined;
    },
    shaped(list) {
        let r =
            this.filterPart === "all"
                ? list
                : list.filter((p) => p.part === this.filterPart);
        if (this.filterSession !== "all")
            r = r.filter((p) => p.session === this.filterSession);
        return r
            .slice()
            .sort((a, b) =>
                this.sortDir === "asc" ? a.year - b.year : b.year - a.year,
            );
    },
    // Plain question papers (and legacy null doc_type); combined/answer split off.
    get questions() {
        return this.shaped(
            this.papers.filter(
                (p) => p.doc_type !== "answer" && p.doc_type !== "combined",
            ),
        );
    },
    get combined() {
        return this.shaped(
            this.papers.filter((p) => p.doc_type === "combined"),
        );
    },
    get answers() {
        return this.shaped(this.papers.filter((p) => p.doc_type === "answer"));
    },
    toggleSort() {
        this.sortDir = this.sortDir === "desc" ? "asc" : "desc";
    },
}));

// Admin paper upload — picks/drops a PDF, decodes its filename
// ({YEAR}{S|A}_{LEVELCODE}_{AM|PM}_{Question|Answer}.pdf) and auto-fills every
// field. The category→level tree (with level `code`s) is passed in from the
// server so we can resolve a code like "FE" to its category + level ids.
Alpine.data("paperUploader", (cats = []) => ({
    cats,
    categoryId: "",
    levelId: "",
    year: "",
    session: "",
    part: "",
    docType: "",
    title: "",
    fileName: "",
    fileSize: 0,
    isDragging: false,
    parsed: false,
    parseOk: false,

    init() {
        // Honour any old() values repopulated by the server after a failed submit.
        const d = this.$root.dataset;
        // Set categoryId first — drives the `levels` and `sessionOptions` x-for loops.
        this.categoryId = d.oldCategory || "";
        this.year = d.oldYear || String(new Date().getFullYear());
        this.part = d.oldPart || "";
        this.docType = d.oldDoctype || "";
        this.title = d.oldTitle || "";

        // Wait one tick for x-for (levels, sessionOptions) to render their <option>
        // elements before setting the dependent selects — otherwise the browser
        // can't match the value and the select stays blank.
        this.$nextTick(() => {
            this.levelId = d.oldLevel || "";
            this.session = d.oldSession || "";

            // Register watch only after all initial values are restored.
            this.$nextTick(() => {
                this.$watch("categoryId", () => {
                    this.levelId = "";
                    if (this.isJlpt) this.part = "";
                    const valid = this.sessionOptions.map((o) => o.v);
                    if (this.session && !valid.includes(this.session))
                        this.session = "";
                });
            });
        });
    },

    get selectedCategoryName() {
        const c = this.cats.find((c) => c.id == this.categoryId);
        return c ? c.name : "";
    },

    get isJlpt() {
        return this.selectedCategoryName === "JLPT";
    },

    get sessionOptions() {
        if (this.isJlpt)
            return [
                { v: "July", l: "July" },
                { v: "December", l: "December" },
            ];
        if (this.selectedCategoryName === "ITPEC")
            return [
                { v: "April", l: "April" },
                { v: "October", l: "October" },
            ];
        return [
            { v: "April", l: "April" },
            { v: "October", l: "October" },
            { v: "December", l: "December" },
            { v: "July", l: "July" },
        ];
    },

    get levels() {
        const c = this.cats.find((c) => c.id == this.categoryId);
        return c ? c.levels : [];
    },

    pickFile(e) {
        const file = e.target.files[0];
        if (file) this.accept(file);
    },

    dropFile(e) {
        this.isDragging = false;
        const file = e.dataTransfer.files[0];
        if (!file) return;
        // Mirror the dropped file into the real <input> so it submits with the form.
        const input = document.getElementById("file");
        if (input) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        }
        this.accept(file);
    },

    clearFile() {
        const input = document.getElementById("file");
        if (input) input.value = "";
        this.fileName = "";
        this.fileSize = 0;
        this.parsed = false;
        this.parseOk = false;
    },

    accept(file) {
        this.fileName = file.name;
        this.fileSize = file.size;
        this.parse(file.name);
    },

    parse(name) {
        const base = name.replace(/\.pdf$/i, "");
        const m = base.match(
            /^(\d{4})([SA])_([A-Za-z0-9]+)_(AM|PM)_(Question|Answer)s?$/i,
        );
        this.parsed = true;
        if (!m) {
            this.parseOk = false;
            return;
        }
        const [, year, sess, code, part, doc] = m;
        this.year = year;
        this.session = sess.toUpperCase() === "S" ? "April" : "October";
        this.part = part.toUpperCase();
        this.docType = doc.toLowerCase();
        for (const c of this.cats) {
            const lvl = (c.levels || []).find(
                (l) => l.code.toUpperCase() === code.toUpperCase(),
            );
            if (lvl) {
                this.categoryId = String(c.id);
                this.$nextTick(() => {
                    this.levelId = String(lvl.id);
                });
                break;
            }
        }
        const label = this.docType === "answer" ? "Answers" : "Questions";
        this.title = `${code.toUpperCase()} — ${this.session} ${year} · ${this.part} · ${label}`;
        this.parseOk = true;
    },

    get levelLabel() {
        const lvl = this.levels.find((l) => l.id == this.levelId);
        return lvl ? lvl.name : "";
    },

    get docLabel() {
        if (this.docType === "answer") return "Answer key";
        if (this.docType === "question") return "Question paper";
        return "";
    },

    formatSize(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + " MB";
        return Math.max(1, Math.round(bytes / 1024)) + " KB";
    },
}));

// ── Admin question form (cascading category → level → section) ──
Alpine.data("questionForm", (cats = []) => ({
    cats,
    categoryId: "",
    levelId: "",
    section: "",
    answer: "",
    init() {
        const d = this.$root.dataset;
        this.answer = d.oldAnswer || "";
        this.categoryId = d.oldCategory || "";

        // Level options are rendered by x-for off `categoryId`; wait one tick so the
        // <option> exists before x-model can select it. Section depends on `levelId`
        // the same way, so nest a second tick.
        this.$nextTick(() => {
            this.levelId = d.oldLevel || "";
            this.$nextTick(() => {
                this.section = d.oldSection || "";
            });
        });
    },
    get levels() {
        return this.cats.find((c) => c.id == this.categoryId)?.levels ?? [];
    },
    get sections() {
        return this.levels.find((l) => l.id == this.levelId)?.sections ?? {};
    },
    get needsSection() {
        return Object.keys(this.sections).length > 0;
    },
    onCategoryChange() {
        this.levelId = "";
        this.section = "";
    },
    onLevelChange() {
        this.section = "";
    },
}));

// ── Report modal ──
Alpine.data("reportModal", () => ({
    open: false,
    targetType: null,
    targetId: null,
    category: "",
    detail: "",
    state: "idle", // 'idle' | 'submitting' | 'success' | 'duplicate' | 'error'
    _closeTimer: null,

    show(type, id) {
        clearTimeout(this._closeTimer);
        this.targetType = type;
        this.targetId = id;
        this.category = "";
        this.detail = "";
        this.state = "idle";
        this.open = true;
    },

    close() {
        clearTimeout(this._closeTimer);
        this.open = false;
    },

    async submit() {
        if (!this.category || this.state === "submitting") return;
        this.state = "submitting";
        try {
            const res = await fetch("/reports", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector("meta[name=csrf-token]")
                            ?.content || "",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    target_type: this.targetType,
                    target_id: this.targetId,
                    category: this.category,
                    reason: this.detail,
                }),
            });
            if (res.status === 409) {
                this.state = "duplicate";
                return;
            }
            if (res.status === 422) {
                const err = await res.json().catch(() => ({}));
                if (err.error === "admin") {
                    this.state = "admin";
                    return;
                }
                this.state = "error";
                return;
            }
            if (!res.ok) {
                this.state = "error";
                return;
            }
            this.state = "success";
            this._closeTimer = setTimeout(() => {
                this.open = false;
            }, 2000);
        } catch (e) {
            this.state = "error";
        }
    },
}));

// ── Appeal modal (shown when banned user attempts a restricted action) ──
Alpine.data("appealModal", (config = {}) => ({
    open: config.autoOpen || false,
    state: config.hasPendingAppeal ? "pending" : "idle",
    message: "",

    init() {
        window.addEventListener("open-appeal-modal", () => this.show());
    },

    show() {
        if (!this.open) {
            if (this.state !== "pending" && this.state !== "success") {
                this.state = config.hasPendingAppeal ? "pending" : "idle";
                this.message = "";
            }
            this.open = true;
        }
    },

    close() {
        this.open = false;
    },

    async submit() {
        if (this.message.length < 10 || this.state === "submitting") return;
        this.state = "submitting";
        try {
            const res = await fetch("/appeal", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector("meta[name=csrf-token]")
                            ?.content || "",
                    Accept: "application/json",
                },
                body: JSON.stringify({ message: this.message }),
            });
            if (res.status === 409) {
                this.state = "pending";
                return;
            }
            if (!res.ok) {
                this.state = "error";
                return;
            }
            this.state = "success";
        } catch (_) {
            this.state = "error";
        }
    },
}));

window.openAppealModal = () =>
    window.dispatchEvent(new CustomEvent("open-appeal-modal"));

// ── Themed confirm dialog (replaces native confirm()) ──
Alpine.data("confirmModal", () => ({
    show: false,
    title: "",
    message: "",
    confirmLabel: "Confirm",
    danger: false,
    _resolve: null,

    open(opts) {
        this.title = opts.title || "Confirm";
        this.message = opts.message || "";
        this.confirmLabel = opts.confirmLabel || "Confirm";
        this.danger = opts.danger || false;
        this._resolve = opts.onConfirm || opts.resolve || null;
        this.show = true;
        this.$nextTick(() => this.$refs.confirmBtn?.focus());
    },

    handleConfirm() {
        const fn = this._resolve;
        this._resolve = null;
        this.show = false;
        if (fn) fn(true);
    },

    handleCancel() {
        const fn = this._resolve;
        this._resolve = null;
        this.show = false;
        if (fn) fn(false);
    },
}));

window.confirmDialog = (opts = {}) =>
    new Promise((resolve) => {
        window.dispatchEvent(
            new CustomEvent("open-confirm", {
                detail: {
                    title: opts.title || "Confirm",
                    message: opts.message || "",
                    confirmLabel: opts.confirmLabel || "Confirm",
                    danger: opts.danger || false,
                    resolve,
                },
            }),
        );
    });

// ── Global capture-phase handler for <form data-confirm> ──
// Intercepts form submits, shows the themed modal, re-submits only on confirm.
document.addEventListener(
    "submit",
    async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        const msg = form.dataset.confirm;
        if (!msg) return;

        // Confirmed re-submit: let it pass through untouched so bubble-phase
        // handlers (loading spinner, AJAX drawer delete) still run.
        if (form.dataset.confirmed) {
            delete form.dataset.confirmed;
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const confirmed = await window.confirmDialog({
            title: form.dataset.confirmTitle || "Confirm",
            message: msg,
            confirmLabel: form.dataset.confirmLabel || "Confirm",
            danger: true,
        });

        if (!confirmed) return;

        form.dataset.confirmed = "1";
        form.requestSubmit();
    },
    { capture: true },
);

// ── Notification bell (sidebar) ──
// Listens on the authenticated user's private Reverb channel.
// userId and initial unreadCount are seeded from Blade.
Alpine.data("notificationBell", (opts = {}) => ({
    unread: opts.unread ?? 0,
    userId: opts.userId ?? null,
    _channel: null,

    init() {
        if (!this.userId || !window.Echo) return;
        this._channel = window.Echo.private(
            `App.Models.User.${this.userId}`,
        ).listen(".notification.created", () => {
            this.unread++;
        });
    },

    destroy() {
        if (this._channel) {
            window.Echo.leave(`App.Models.User.${this.userId}`);
        }
    },
}));

document.addEventListener("alpine:initialized", () => {
    createIcons({ icons });
});

Alpine.store("commentCounts", {});
Alpine.start();

window.renderIcons = (root = document) => createIcons({ icons, root });
window.appendWithIcons = (container, html) => {
    const marker = container.lastElementChild;
    container.insertAdjacentHTML("beforeend", html);

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
    btn.dataset.loadingActive = "1";
    btn.dataset.loadingPrev = btn.innerHTML;
    btn.disabled = true;
    btn.setAttribute("aria-busy", "true");
    const label = (text ?? btn.dataset.loadingText ?? "").trim();
    btn.innerHTML =
        '<span class="inline-flex items-center justify-center gap-2">' +
        LEAF_SPIN_SVG +
        (label ? `<span>${label}</span>` : "") +
        "</span>";
};

window.resetButtonLoading = (btn) => {
    if (!btn || !btn.dataset.loadingActive) return;
    btn.innerHTML = btn.dataset.loadingPrev || "";
    btn.disabled = false;
    btn.removeAttribute("aria-busy");
    delete btn.dataset.loadingActive;
    delete btn.dataset.loadingPrev;
    window.renderIcons(btn); // re-render any restored Lucide <i> (e.g. the trash icon)
};

// Any <form data-loading> shows the spinner on its submit button when it submits.
// Skips submits already cancelled (confirm()) or handled elsewhere (drawer AJAX
// preventDefault), since those set defaultPrevented before this bubbles to document.
document.addEventListener("submit", (e) => {
    if (e.defaultPrevented) return;
    const form = e.target;
    if (
        !(form instanceof HTMLFormElement) ||
        !form.hasAttribute("data-loading")
    )
        return;
    window.showButtonLoading(
        form.querySelector("[type=submit], button:not([type])"),
    );
});

// bfcache back-nav can restore a page with a button still spinning — un-stick them.
window.addEventListener("pageshow", () => {
    document
        .querySelectorAll("[data-loading-active]")
        .forEach((b) => window.resetButtonLoading(b));
});
