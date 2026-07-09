<x-app-layout>
    <x-slot:title>Focus Timer</x-slot:title>

    <div class="px-4" x-data="pomodoroTimer()" x-cloak>
        <div class="grid gap-6 lg:grid-cols-[1fr_280px]">

            {{-- ════════════════════════════════════════════
                 LEFT COLUMN — Timer
            ════════════════════════════════════════════ --}}
            <div class="bg-surface border border-line rounded-2xl px-6 py-8 sm:px-8 flex flex-col items-center">

                {{-- Phase pill --}}
                <span class="text-xs font-medium uppercase tracking-widest px-3.5 py-1 rounded-full transition-colors"
                      :class="phasePill.classes"
                      x-text="phasePill.label">Ready</span>

                {{-- Timer ring --}}
                <div class="relative mt-6 w-[180px] h-[180px] lg:w-[220px] lg:h-[220px]">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 220 220">
                        {{-- Background ring --}}
                        <circle cx="110" cy="110" r="100" fill="none"
                                stroke="rgb(var(--line))" stroke-width="6"></circle>
                        {{-- Progress ring --}}
                        <circle cx="110" cy="110" r="100" fill="none" stroke-width="6"
                                stroke-linecap="round"
                                :stroke="ringColor"
                                :stroke-opacity="isPaused ? 0.4 : 1"
                                :stroke-dasharray="circumference"
                                :stroke-dashoffset="ringOffset"
                                style="transition: stroke-dashoffset 0.4s linear, stroke 0.3s ease"></circle>
                    </svg>

                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-4xl lg:text-[42px] leading-none font-medium tabular-nums text-content"
                             x-text="displayTime">25:00</div>
                        <div class="text-xs text-muted mt-1.5" x-show="!transitionMessage">
                            of <span x-text="totalDisplay">25:00</span>
                        </div>
                        <div class="text-sm font-medium text-accent mt-1.5"
                             x-show="transitionMessage"
                             x-text="transitionMessage"
                             x-cloak></div>
                    </div>
                </div>

                {{-- Session progress dots --}}
                <div class="flex items-center gap-2.5 mt-7">
                    <template x-for="i in sessionsBeforeLongBreak" :key="i">
                        <span class="w-3 h-3 rounded-full transition-all duration-300"
                              :class="dotClass(i - 1)"></span>
                    </template>
                </div>
                <div class="text-sm text-muted mt-2.5" x-text="sessionLabel">Session 1 of 4</div>

                {{-- Controls --}}
                <div class="flex items-center gap-5 mt-7">
                    {{-- Reset --}}
                    <button type="button" @click="reset()" aria-label="Reset phase"
                            class="w-[38px] h-[38px] grid place-items-center rounded-full border border-line text-muted hover:text-content hover:bg-surface-muted transition-colors">
                        <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                    </button>

                    {{-- Play / Pause --}}
                    <button type="button" @click="toggle()" aria-label="Start or pause timer"
                            class="w-[52px] h-[52px] grid place-items-center rounded-full bg-gradient-to-tr from-accent-from to-accent-to text-white shadow-sm hover:bg-accent-strong transition-colors">
                        <i data-lucide="play" x-show="!isRunning" class="w-6 h-6 translate-x-[1px]"></i>
                        <i data-lucide="pause" x-show="isRunning" x-cloak class="w-6 h-6"></i>
                    </button>

                    {{-- Skip --}}
                    <button type="button" @click="skip()" aria-label="Skip to next phase"
                            class="w-[38px] h-[38px] grid place-items-center rounded-full border border-line text-muted hover:text-content hover:bg-surface-muted transition-colors">
                        <i data-lucide="skip-forward" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Daily goal progress (auth only) --}}
                @auth
                    <div class="w-full max-w-xs mt-9">
                        <div class="flex justify-between items-baseline text-xs mb-1.5">
                            <span class="text-muted">Daily goal</span>
                            <span class="text-content font-medium">
                                <span x-text="todaySessions">0</span> / <span x-text="dailyGoalSessions">8</span> sessions
                            </span>
                        </div>
                        <div class="h-1.5 rounded-full bg-surface-muted overflow-hidden">
                            <div class="h-full rounded-full bg-accent transition-[width] duration-500"
                                 :style="`width: ${goalPercent}%`"></div>
                        </div>
                    </div>
                @endauth
            </div>

            {{-- ════════════════════════════════════════════
                 RIGHT COLUMN — Panels
            ════════════════════════════════════════════ --}}
            <div class="flex flex-col gap-4">

                {{-- ── Sounds ── --}}
                <div class="rounded-xl border border-line bg-surface overflow-hidden @guest lg:flex-1 lg:flex lg:flex-col @endguest"
                     x-data="{ open: window.matchMedia('(min-width: 1024px)').matches }"
                     x-init="window.matchMedia('(min-width: 1024px)').addEventListener('change', e => { if (e.matches) open = true })">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 border-b border-line text-sm font-medium text-content lg:pointer-events-none lg:cursor-default">
                        <span class="flex items-center gap-2">
                            <i data-lucide="volume-2" class="w-4 h-4 text-muted"></i>
                            Sounds
                        </span>
                        <span class="inline-flex w-4 h-4 transition-transform lg:hidden"
                              :class="open ? 'rotate-180' : ''">
                            <i data-lucide="chevron-down" class="w-4 h-4 text-muted"></i>
                        </span>
                    </button>

                    <div x-show="open" class="lg:!block p-2">
                        <template x-for="s in sounds" :key="s.key">
                            <button type="button" @click="setSound(s.key)"
                                    class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm transition-colors"
                                    :class="activeSound === s.key ? 'bg-accent/10 text-accent font-medium' : 'text-content hover:bg-surface-muted'">
                                <i data-lucide="audio-lines" class="w-4 h-4 shrink-0"></i>
                                <span class="flex-1 text-left" x-text="s.label"></span>
                                <span x-show="activeSound === s.key" class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                            </button>
                        </template>

                        {{-- Volume --}}
                        <div class="flex items-center gap-2.5 px-2.5 pt-3 pb-1">
                            <i data-lucide="volume-2" class="w-4 h-4 text-muted shrink-0"></i>
                            <input type="range" min="0" max="100" step="5"
                                   x-model.number="volume" @input="setVolume(volume)"
                                   class="flex-1 accent-accent cursor-pointer">
                            <span class="text-xs text-muted tabular-nums w-9 text-right" x-text="volume + '%'">65%</span>
                        </div>
                    </div>
                </div>

                {{-- ── Today (auth only) ── --}}
                @auth
                    <div class="rounded-xl border border-line bg-surface overflow-hidden lg:flex-1 lg:flex lg:flex-col"
                         x-data="{ open: window.matchMedia('(min-width: 1024px)').matches }"
                         x-init="window.matchMedia('(min-width: 1024px)').addEventListener('change', e => { if (e.matches) open = true })">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 border-b border-line text-sm font-medium text-content lg:pointer-events-none lg:cursor-default">
                            <span>Today</span>
                            <span class="inline-flex w-4 h-4 transition-transform lg:hidden"
                                  :class="open ? 'rotate-180' : ''">
                                <i data-lucide="chevron-down" class="w-4 h-4 text-muted"></i>
                            </span>
                        </button>
                        <div x-show="open" class="lg:!flex flex flex-col gap-3 p-3 lg:flex-1">
                            <div class="p-4 rounded-lg bg-surface-muted flex flex-col justify-center gap-1.5 lg:flex-1">
                                <div class="text-xl font-semibold text-content" x-text="todaySessions">0</div>
                                <div class="text-xs text-muted">Sessions</div>
                            </div>
                            <div class="p-4 rounded-lg bg-surface-muted flex flex-col justify-center gap-1.5 lg:flex-1">
                                <div class="text-xl font-semibold text-content" x-text="todayFocusLabel">0m</div>
                                <div class="text-xs text-muted">Focus time</div>
                            </div>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        {{-- ════════════════════════════════════════════
             SETTINGS — full width, horizontal
        ════════════════════════════════════════════ --}}
        <div class="mt-6 rounded-xl border border-line bg-surface overflow-hidden"
             x-data="{ open: window.matchMedia('(min-width: 1024px)').matches }"
             x-init="window.matchMedia('(min-width: 1024px)').addEventListener('change', e => { if (e.matches) open = true })">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 border-b border-line text-sm font-medium text-content lg:pointer-events-none lg:cursor-default">
                <span class="flex items-center gap-2">
                    <i data-lucide="settings" class="w-4 h-4 text-muted"></i>
                    Settings
                </span>
                <span class="inline-flex w-4 h-4 transition-transform lg:hidden"
                      :class="open ? 'rotate-180' : ''">
                    <i data-lucide="chevron-down" class="w-4 h-4 text-muted"></i>
                </span>
            </button>

            <div x-show="open" x-collapse.duration.300ms>
                @auth
                    {{-- Suspended users can't persist settings (not-banned middleware blocks
                         the PATCH), so dim + disable the inputs and point them at the appeal. --}}
                    <div class="relative">
                        <div class="px-4 py-2 sm:p-3 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-1 sm:gap-3 {{ $suspended ? 'opacity-50 select-none pointer-events-none' : '' }}">
                            <label class="flex items-center justify-between py-2 sm:py-0 text-sm sm:flex-col sm:items-start sm:justify-start sm:gap-1">
                                <span class="text-content sm:text-muted sm:text-xs">Focus</span>
                                <span class="flex items-center gap-1.5">
                                    <input type="number" min="1" max="120" @disabled($suspended)
                                           x-model.number="focusMinutes" @input="onSettingChange()"
                                           class="w-16 text-right text-sm rounded-md border-line bg-surface text-content focus:border-accent focus:ring-accent py-1">
                                    <span class="text-xs text-muted w-6">min</span>
                                </span>
                            </label>
                            <label class="flex items-center justify-between py-2 sm:py-0 text-sm sm:flex-col sm:items-start sm:justify-start sm:gap-1">
                                <span class="text-content sm:text-muted sm:text-xs">Short break</span>
                                <span class="flex items-center gap-1.5">
                                    <input type="number" min="1" max="60" @disabled($suspended)
                                           x-model.number="shortBreakMinutes" @input="onSettingChange()"
                                           class="w-16 text-right text-sm rounded-md border-line bg-surface text-content focus:border-accent focus:ring-accent py-1">
                                    <span class="text-xs text-muted w-6">min</span>
                                </span>
                            </label>
                            <label class="flex items-center justify-between py-2 sm:py-0 text-sm sm:flex-col sm:items-start sm:justify-start sm:gap-1">
                                <span class="text-content sm:text-muted sm:text-xs">Long break</span>
                                <span class="flex items-center gap-1.5">
                                    <input type="number" min="1" max="120" @disabled($suspended)
                                           x-model.number="longBreakMinutes" @input="onSettingChange()"
                                           class="w-16 text-right text-sm rounded-md border-line bg-surface text-content focus:border-accent focus:ring-accent py-1">
                                    <span class="text-xs text-muted w-6">min</span>
                                </span>
                            </label>
                            <label class="flex items-center justify-between py-2 sm:py-0 text-sm sm:flex-col sm:items-start sm:justify-start sm:gap-1">
                                <span class="text-content sm:text-muted sm:text-xs">Sessions / cycle</span>
                                <span class="flex items-center gap-1.5">
                                    <input type="number" min="1" max="10" @disabled($suspended)
                                           x-model.number="sessionsBeforeLongBreak" @input="onSettingChange()"
                                           class="w-16 text-right text-sm rounded-md border-line bg-surface text-content focus:border-accent focus:ring-accent py-1">
                                    <span class="w-6" aria-hidden="true"></span>
                                </span>
                            </label>
                            <label class="flex items-center justify-between py-2 sm:py-0 text-sm sm:flex-col sm:items-start sm:justify-start sm:gap-1">
                                <span class="text-content sm:text-muted sm:text-xs">Daily goal</span>
                                <span class="flex items-center gap-1.5">
                                    <input type="number" min="1" max="24" @disabled($suspended)
                                           x-model.number="dailyGoalSessions" @input="onSettingChange()"
                                           class="w-16 text-right text-sm rounded-md border-line bg-surface text-content focus:border-accent focus:ring-accent py-1">
                                    <span class="w-6" aria-hidden="true"></span>
                                </span>
                            </label>
                        </div>
                        @if ($suspended)
                            <div class="absolute inset-0 grid place-items-center bg-surface/40">
                                <button type="button"
                                        @click="$dispatch('open-appeal-modal')"
                                        class="flex items-center gap-2 text-sm font-medium text-content bg-surface border border-line rounded-lg px-3.5 py-2 shadow-sm hover:border-accent/40 transition-colors">
                                    <i data-lucide="lock" class="w-4 h-4 text-muted"></i>
                                    Account suspended — settings locked
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Guests: static values + lock --}}
                    <div class="relative">
                        <div class="px-4 py-2 sm:p-3 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-1 sm:gap-3 opacity-50 select-none pointer-events-none">
                            <div class="flex items-center justify-between py-2 sm:py-0 text-sm sm:flex-col sm:items-start sm:justify-start sm:gap-1">
                                <span class="text-content sm:text-muted sm:text-xs">Focus</span>                                <span class="text-muted">25 min</span>
                            </div>
                            <div class="flex items-center justify-between py-2 sm:py-0 text-sm sm:flex-col sm:items-start sm:justify-start sm:gap-1">
                                <span class="text-content sm:text-muted sm:text-xs">Short break</span>                                <span class="text-muted">5 min</span>
                            </div>
                            <div class="flex items-center justify-between py-2 sm:py-0 text-sm sm:flex-col sm:items-start sm:justify-start sm:gap-1">
                                <span class="text-content sm:text-muted sm:text-xs">Long break</span>                                <span class="text-muted">15 min</span>
                            </div>
                            <div class="flex items-center justify-between py-2 sm:py-0 text-sm sm:flex-col sm:items-start sm:justify-start sm:gap-1">
                                <span class="text-content sm:text-muted sm:text-xs">Daily goal</span>                                <span class="text-muted">8 sessions</span>
                            </div>
                        </div>
                        <div class="absolute inset-0 grid place-items-center bg-surface/40">
                            <button type="button"
                                    @click="$dispatch('open-auth-modal')"
                                    class="flex items-center gap-2 text-sm font-medium text-content bg-surface border border-line rounded-lg px-3.5 py-2 shadow-sm hover:border-accent/40 transition-colors">
                                <i data-lucide="lock" class="w-4 h-4 text-muted"></i>
                                Log in to customize
                            </button>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        {{-- Ambient sound is synthesised in-browser via the Web Audio API
             (shared AmbientSound engine in resources/js/ambient-sound.js,
             also used by the floating focus pill). --}}
    </div>

    @push('scripts')
        <script>
            function pomodoroTimer() {
                return {
                    // ── Config (from server or guest defaults) ──
                    focusMinutes: {{ $settings?->focus_minutes ?? 25 }},
                    shortBreakMinutes: {{ $settings?->short_break_minutes ?? 5 }},
                    longBreakMinutes: {{ $settings?->long_break_minutes ?? 15 }},
                    sessionsBeforeLongBreak: {{ $settings?->sessions_before_long_break ?? 4 }},
                    dailyGoalSessions: {{ $settings?->daily_goal_sessions ?? 8 }},

                    // ── Timer state ──
                    phase: 'ready',            // ready | focus | short_break | long_break
                    isRunning: false,
                    isPaused: false,
                    remainingSeconds: 25 * 60,
                    totalSeconds: 25 * 60,
                    currentSession: 0,         // completed focus sessions in the current cycle
                    intervalId: null,
                    endsAt: null,              // epoch-ms deadline while running — survives
                                               // background-tab throttling and page navigations
                    sessionStartedAt: null,
                    transitionMessage: '',
                    circumference: 2 * Math.PI * 100,

                    // ── Daily stats (auth only) ──
                    todaySessions: {{ (int) ($todaySessions ?? 0) }},
                    todayFocusMinutes: {{ (int) ($todayFocusTime ?? 0) }},
                    isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
                    uid: {{ auth()->id() ?? 0 }},

                    // ── Sound ──
                    activeSound: 'silent',
                    volume: 65,
                    sounds: [
                        { key: 'silent',       label: 'Silent' },
                        { key: 'quietplease', label: 'Quiet Please' },
                        { key: 'ocean',        label: 'Ocean waves' },
                        { key: 'forest',       label: 'Forest' },
                        { key: 'binaural',     label: 'Binaural' },
                        { key: 'lofi',         label: 'Lo-fi pad' },
                        { key: 'piano',        label: 'Piano' },
                    ],

                    // ── Internal ──
                    csrf: '',
                    _settingsTimer: null,

                    // ── Lifecycle ──
                    init() {
                        this.csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                        // Restore a session that survived a page navigation
                        // (see FocusSession in app.js). A session started by
                        // a different account is discarded.
                        const stored = window.FocusSession.read();
                        if (stored && Number(stored.uid || 0) !== this.uid) window.FocusSession.clear();
                        const restored = stored ? window.FocusSession.sync() : null;
                        if (restored) {
                            this.restoreSession(restored);
                            return;
                        }

                        this.totalSeconds = this.focusMinutes * 60;
                        this.remainingSeconds = this.totalSeconds;
                    },
                    restoreSession(s) {
                        // The stored snapshot wins over server-rendered settings
                        // so mid-phase math stays consistent with the anchor.
                        this.focusMinutes = s.focusMinutes;
                        this.shortBreakMinutes = s.shortBreakMinutes;
                        this.longBreakMinutes = s.longBreakMinutes;
                        this.sessionsBeforeLongBreak = s.sessionsBeforeLongBreak;
                        this.activeSound = s.activeSound;
                        this.volume = s.volume;
                        this.phase = s.live.phase;
                        this.currentSession = s.live.currentSession;
                        this.totalSeconds = s.live.totalSeconds;
                        this.remainingSeconds = s.live.remainingSeconds;
                        const started = s.live.sessionStartedAt ?? s.sessionStartedAt;
                        this.sessionStartedAt = started ? new Date(started) : null;
                        if (s.isRunning) {
                            this.isRunning = true;
                            this.endsAt = s.live.endsAt;
                            this.startTimer();
                            // Sound stays muted on return, even mid-session —
                            // only an explicit tap (clicking the active sound
                            // in the list) resumes it.
                        } else {
                            this.isPaused = true;
                        }
                    },
                    destroy() {
                        clearInterval(this.intervalId);
                        clearTimeout(this._settingsTimer);
                    },

                    // ── Computed ──
                    get displayTime() { return this.format(this.remainingSeconds); },
                    get totalDisplay() { return this.format(this.totalSeconds); },
                    get ringOffset() {
                        if (this.totalSeconds <= 0) return 0;
                        const remaining = Math.max(0, this.remainingSeconds);
                        return this.circumference * (1 - remaining / this.totalSeconds);
                    },
                    get ringColor() {
                        switch (this.phase) {
                            case 'focus':       return 'rgb(var(--accent))';
                            case 'short_break': return '#3b82f6';
                            case 'long_break':  return '#8b5cf6';
                            default:            return 'rgb(var(--muted))';
                        }
                    },
                    get phasePill() {
                        if (this.isPaused) return { label: 'Paused', classes: 'bg-amber-100 text-amber-700' };
                        switch (this.phase) {
                            case 'focus':       return { label: 'Focus',       classes: 'bg-accent/10 text-accent' };
                            case 'short_break': return { label: 'Short break', classes: 'bg-blue-100 text-blue-700' };
                            case 'long_break':  return { label: 'Long break',  classes: 'bg-violet-100 text-violet-700' };
                            default:            return { label: 'Ready',       classes: 'bg-surface-muted text-muted' };
                        }
                    },
                    get sessionLabel() {
                        if (this.phase === 'short_break') return 'Short break';
                        if (this.phase === 'long_break')  return 'Long break';
                        const n = Math.min(this.currentSession + 1, this.sessionsBeforeLongBreak);
                        return `Session ${n} of ${this.sessionsBeforeLongBreak}`;
                    },
                    get goalPercent() {
                        if (this.dailyGoalSessions <= 0) return 0;
                        return Math.min(100, Math.round((this.todaySessions / this.dailyGoalSessions) * 100));
                    },
                    get todayFocusLabel() {
                        const m = this.todayFocusMinutes;
                        if (m < 60) return `${m}m`;
                        return `${Math.floor(m / 60)}h ${m % 60}m`;
                    },

                    // ── Helpers ──
                    format(totalSec) {
                        const s = Math.max(0, totalSec);
                        const mm = Math.floor(s / 60);
                        const ss = s % 60;
                        return `${mm}:${String(ss).padStart(2, '0')}`;
                    },
                    dotClass(index) {
                        if (index < this.currentSession) return 'bg-accent';
                        if (index === this.currentSession && this.phase === 'focus')
                            return 'bg-accent ring-4 ring-accent/30';
                        return 'bg-transparent border-2 border-accent/40';
                    },

                    // ── Session persistence (survives page navigations) ──
                    persistState(over = {}) {
                        const s = {
                            uid: this.uid,
                            auth: this.isAuthenticated,
                            phase: this.phase,
                            isRunning: this.isRunning,
                            endsAt: this.endsAt,
                            remainingSeconds: this.remainingSeconds,
                            totalSeconds: this.totalSeconds,
                            currentSession: this.currentSession,
                            sessionStartedAt: this.sessionStartedAt ? this.sessionStartedAt.getTime() : null,
                            focusMinutes: this.focusMinutes,
                            shortBreakMinutes: this.shortBreakMinutes,
                            longBreakMinutes: this.longBreakMinutes,
                            sessionsBeforeLongBreak: this.sessionsBeforeLongBreak,
                            activeSound: this.activeSound,
                            volume: this.volume,
                            ...over,
                        };
                        if (s.phase === 'ready') { window.FocusSession.clear(); return; }
                        window.FocusSession.write(s);
                    },

                    // ── Timer actions ──
                    toggle() {
                        if (this.transitionMessage) return;
                        if (this.isRunning) { this.pause(); return; }
                        if (this.phase === 'ready') { this.beginPhase('focus'); return; }
                        this.resume();
                    },
                    beginPhase(phase) {
                        this.phase = phase;
                        this.isPaused = false;
                        this.isRunning = true;
                        const mins = phase === 'focus' ? this.focusMinutes
                            : phase === 'short_break' ? this.shortBreakMinutes
                            : this.longBreakMinutes;
                        this.totalSeconds = mins * 60;
                        this.remainingSeconds = this.totalSeconds;
                        this.endsAt = Date.now() + this.totalSeconds * 1000;
                        if (phase === 'focus') this.sessionStartedAt = new Date();
                        this.startTimer();
                        this.playAmbient();
                        this.persistState();
                    },
                    resume() {
                        this.isPaused = false;
                        this.isRunning = true;
                        this.endsAt = Date.now() + this.remainingSeconds * 1000;
                        this.startTimer();
                        this.playAmbient();
                        this.persistState();
                    },
                    pause() {
                        this.isRunning = false;
                        this.isPaused = true;
                        clearInterval(this.intervalId);
                        this.pauseAmbient();
                        this.persistState();
                    },
                    startTimer() {
                        clearInterval(this.intervalId);
                        this.intervalId = setInterval(() => this.tick(), 1000);
                    },
                    tick() {
                        // Derive from the deadline, not a decrement — immune to
                        // background-tab interval throttling.
                        this.remainingSeconds = Math.max(0, Math.ceil((this.endsAt - Date.now()) / 1000));
                        if (this.remainingSeconds <= 0) this.handleComplete();
                    },
                    handleComplete() {
                        clearInterval(this.intervalId);
                        this.isRunning = false;
                        this.playChime();

                        let next, msg;
                        if (this.phase === 'focus') {
                            this.currentSession++;
                            if (this.isAuthenticated) this.saveSession(true);
                            next = this.currentSession >= this.sessionsBeforeLongBreak ? 'long_break' : 'short_break';
                            msg = "Time's up!";
                        } else if (this.phase === 'short_break') {
                            next = 'focus';
                            msg = "Break's over!";
                        } else {
                            next = 'ready';
                            msg = 'Great work!';
                            this.currentSession = 0;
                        }

                        // Advance the stored anchor now — the 2s transition pause
                        // is cosmetic, and navigating away mid-transition (or the
                        // pill syncing in parallel) must see the next phase, not a
                        // stale expired one (which would double-post the session).
                        if (next === 'ready') {
                            window.FocusSession.clear();
                        } else {
                            const mins = next === 'focus' ? this.focusMinutes
                                : next === 'short_break' ? this.shortBreakMinutes
                                : this.longBreakMinutes;
                            this.persistState({
                                phase: next,
                                isRunning: true,
                                endsAt: Date.now() + mins * 60000,
                                totalSeconds: mins * 60,
                                remainingSeconds: mins * 60,
                                sessionStartedAt: next === 'focus' ? Date.now() : null,
                            });
                        }

                        this.transitionMessage = msg;
                        setTimeout(() => {
                            this.transitionMessage = '';
                            if (next === 'ready') this.goReady();
                            else this.beginPhase(next);
                        }, 2000);
                    },
                    skip() {
                        if (this.transitionMessage) return;
                        clearInterval(this.intervalId);
                        if (this.phase === 'focus') {
                            if (this.isAuthenticated) this.saveSession(false);
                            const next = this.currentSession >= this.sessionsBeforeLongBreak ? 'long_break' : 'short_break';
                            this.beginPhase(next);
                        } else if (this.phase === 'short_break') {
                            this.beginPhase('focus');
                        } else if (this.phase === 'long_break') {
                            this.currentSession = 0;
                            this.goReady();
                        } else {
                            this.beginPhase('focus');
                        }
                    },
                    reset() {
                        const mins = this.phase === 'focus' ? this.focusMinutes
                            : this.phase === 'short_break' ? this.shortBreakMinutes
                            : this.longBreakMinutes;
                        this.totalSeconds = mins * 60;
                        this.remainingSeconds = this.totalSeconds;
                        if (this.isRunning) {
                            this.endsAt = Date.now() + this.totalSeconds * 1000;
                            this.startTimer();
                        }
                        if (this.phase !== 'ready') this.persistState();
                    },
                    goReady() {
                        this.phase = 'ready';
                        this.isRunning = false;
                        this.isPaused = false;
                        this.endsAt = null;
                        this.totalSeconds = this.focusMinutes * 60;
                        this.remainingSeconds = this.totalSeconds;
                        this.stopAllAudio();
                        window.FocusSession.clear();
                    },

                    // ── Server sync (auth only) ──
                    saveSession(completed) {
                        if (!this.isAuthenticated) return;
                        const started = this.sessionStartedAt || new Date();
                        const ended = new Date();
                        const elapsedSec = Math.max(0, Math.round((ended - started) / 1000));
                        const actual = completed ? this.focusMinutes : Math.round(elapsedSec / 60);

                        fetch('{{ route('timer.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                            body: JSON.stringify({
                                planned_duration: this.focusMinutes,
                                actual_duration: actual,
                                completed: completed,
                                started_at: started.toISOString(),
                                ended_at: ended.toISOString(),
                            }),
                        })
                            .then((r) => r.json())
                            .then((d) => {
                                if (d && d.success) {
                                    this.todaySessions = d.today_sessions;
                                    this.todayFocusMinutes = d.today_focus_time;
                                }
                            })
                            .catch(() => {});
                    },
                    onSettingChange() {
                        // Clamp into valid ranges so a half-typed value never persists.
                        this.focusMinutes = this.clamp(this.focusMinutes, 1, 120);
                        this.shortBreakMinutes = this.clamp(this.shortBreakMinutes, 1, 60);
                        this.longBreakMinutes = this.clamp(this.longBreakMinutes, 1, 120);
                        this.sessionsBeforeLongBreak = this.clamp(this.sessionsBeforeLongBreak, 1, 10);
                        this.dailyGoalSessions = this.clamp(this.dailyGoalSessions, 1, 24);

                        if (this.currentSession > this.sessionsBeforeLongBreak) this.currentSession = 0;

                        // Live-update the displayed time when idle.
                        if (this.phase === 'ready' && !this.isRunning) {
                            this.totalSeconds = this.focusMinutes * 60;
                            this.remainingSeconds = this.totalSeconds;
                        }

                        clearTimeout(this._settingsTimer);
                        this._settingsTimer = setTimeout(() => this.persistSettings(), 500);

                        // Keep the stored session's settings snapshot current so
                        // the pill and a later restore use the same durations.
                        if (this.phase !== 'ready') this.persistState();
                    },
                    persistSettings() {
                        if (!this.isAuthenticated) return;
                        fetch('{{ route('timer.settings') }}', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                            body: JSON.stringify({
                                focus_minutes: this.focusMinutes,
                                short_break_minutes: this.shortBreakMinutes,
                                long_break_minutes: this.longBreakMinutes,
                                sessions_before_long_break: this.sessionsBeforeLongBreak,
                                daily_goal_sessions: this.dailyGoalSessions,
                            }),
                        }).catch(() => {});
                    },
                    clamp(v, min, max) {
                        v = Number(v);
                        if (!Number.isFinite(v)) return min;
                        return Math.min(max, Math.max(min, Math.round(v)));
                    },

                    // ── Sound (delegates to the shared AmbientSound engine in
                    //    resources/js/ambient-sound.js, also used by the pill) ──
                    setSound(name) {
                        this.activeSound = name;
                        if (name === 'silent' || !this.isRunning) window.AmbientSound.stop();
                        else window.AmbientSound.start(name, this.volume);
                        if (this.phase !== 'ready') this.persistState();
                    },
                    setVolume(value) {
                        this.volume = value;
                        window.AmbientSound.setVolume(value);
                        if (this.phase !== 'ready') this.persistState();
                    },
                    playAmbient() {
                        if (this.activeSound !== 'silent') window.AmbientSound.start(this.activeSound, this.volume);
                    },
                    pauseAmbient() {
                        window.AmbientSound.stop();
                    },
                    stopAllAudio() {
                        window.AmbientSound.stop();
                    },
                    playChime() {
                        window.AmbientSound.playChime();
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
