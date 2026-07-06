<x-app-layout>
    <x-slot name="title">Settings — MiraiStudy</x-slot>

    <style>
        @media(min-width:1024px){ html, body { overflow: hidden; } }
    </style>

    <div class="w-full min-h-full flex justify-center items-start lg:items-center pt-14 lg:pt-0 p-6 md:p-10 lg:p-12 transition-colors duration-300">
        <div class="max-w-6xl mx-auto w-full">

            <header class="mb-8">
                <h1 class="text-3xl font-bold tracking-tight text-content">Appearance</h1>
                <p class="mt-1 text-sm text-muted">
                    Customize how your application interface looks and feels.
                </p>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 lg:gap-8 items-start">

                {{-- Left Column — Controls --}}
                <div class="lg:col-span-3">
                    <section class="rounded-2xl border border-line bg-surface p-6 shadow-sm">

                        {{-- Theme Mode --}}
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Theme</p>
                            <p class="text-xs text-muted mt-1 mb-4">Choose a light or dark interface, or match your system.</p>

                            <div class="grid grid-cols-3 gap-1 rounded-xl bg-surface-muted p-1">
                                <button id="theme-light"
                                        class="seg-btn flex flex-row items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-semibold transition-all cursor-pointer">
                                    <i data-lucide="sun" class="h-4 w-4"></i>
                                    <span>Light</span>
                                </button>
                                <button id="theme-dark"
                                        class="seg-btn flex flex-row items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-semibold transition-all cursor-pointer">
                                    <i data-lucide="moon" class="h-4 w-4"></i>
                                    <span>Dark</span>
                                </button>
                                <button id="theme-system"
                                        class="seg-btn flex flex-row items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-semibold transition-all cursor-pointer">
                                    <i data-lucide="settings" class="h-4 w-4"></i>
                                    <span>System</span>
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-line my-5"></div>

                        {{-- Accent Color --}}
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Accent Color</p>
                            <p class="text-xs text-muted mt-1 mb-4">Pick your accent style and color.</p>

                            {{-- Fill style: Gradient | Solid --}}
                            <div class="grid grid-cols-2 gap-1 rounded-xl bg-surface-muted p-1 mb-4">
                                <button id="fill-gradient"
                                        class="fill-btn flex items-center justify-center rounded-lg py-2 text-xs font-semibold transition-all cursor-pointer">
                                    Gradient
                                </button>
                                <button id="fill-solid"
                                        class="fill-btn flex items-center justify-center rounded-lg py-2 text-xs font-semibold transition-all cursor-pointer">
                                    Solid
                                </button>
                            </div>

                            {{-- Theme swatches — background painted by JS per fill style --}}
                            <div class="grid grid-cols-5 gap-2">
                                <button class="theme-btn relative flex flex-col items-center justify-center gap-1 rounded-xl py-4 text-white shadow-sm transition-all cursor-pointer"
                                        data-theme="venom">
                                    <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                    <span class="text-[11px] font-semibold">Green</span>
                                </button>
                                <button class="theme-btn relative flex flex-col items-center justify-center gap-1 rounded-xl py-4 text-white shadow-sm transition-all cursor-pointer"
                                        data-theme="aurora">
                                    <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                    <span class="text-[11px] font-semibold">Blue</span>
                                </button>
                                <button class="theme-btn relative flex flex-col items-center justify-center gap-1 rounded-xl py-4 text-white shadow-sm transition-all cursor-pointer"
                                        data-theme="sangria">
                                    <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                    <span class="text-[11px] font-semibold">Red</span>
                                </button>
                                <button class="theme-btn relative flex flex-col items-center justify-center gap-1 rounded-xl py-4 text-white shadow-sm transition-all cursor-pointer"
                                        data-theme="twilight">
                                    <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                    <span class="text-[11px] font-semibold">Purple</span>
                                </button>
                                <button class="theme-btn relative flex flex-col items-center justify-center gap-1 rounded-xl py-4 text-white shadow-sm transition-all cursor-pointer"
                                        data-theme="inferno">
                                    <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                    <span class="text-[11px] font-semibold">Orange</span>
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-line my-5"></div>

                        {{-- Security --}}
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Security</p>
                            <p class="text-xs text-muted mt-1 mb-4">Add an extra layer of protection to your account.</p>

                            <div class="flex items-center justify-between gap-4 rounded-xl bg-surface-muted p-4">
                                <div>
                                    <div class="text-sm font-semibold text-content">Two-factor authentication</div>
                                    <p class="mt-0.5 text-xs text-muted">Email a 6-digit code every time you log in.</p>
                                </div>
                                <button id="twofa-toggle" type="button" role="switch"
                                        aria-checked="{{ auth()->user()->two_factor_enabled ? 'true' : 'false' }}"
                                        data-enabled="{{ auth()->user()->two_factor_enabled ? '1' : '0' }}"
                                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-line transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-surface cursor-pointer">
                                    <span id="twofa-knob" class="inline-block h-5 w-5 translate-x-1 transform rounded-full bg-white shadow transition-transform"></span>
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-line my-5"></div>

                        {{-- Save Button --}}
                        <div class="flex flex-col gap-2">
                            <button id="save-btn"
                                    type="button"
                                    class="w-full rounded-xl bg-slate-800 px-5 py-3 text-sm font-bold text-white shadow-sm transition-all cursor-pointer">
                                Save Changes
                            </button>
                        </div>

                    </section>
                </div>

                {{-- Right Column — Live Preview --}}
                <div class="hidden lg:block lg:col-span-2">
                    <section class="rounded-2xl border border-line bg-surface p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-muted mb-4">
                            Live Interface Preview
                        </h3>

                        <div id="mockup-container"
                             class="w-full border border-line bg-canvas rounded-xl overflow-hidden shadow-inner flex"
                             style="height: 460px;">

                            {{-- Mini Sidebar --}}
                            <aside id="mockup-sidebar"
                                   class="w-2/5 border-r border-line bg-surface p-2.5 flex flex-col justify-between shrink-0 select-none">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-1.5 px-1 py-1">
                                        {{-- Mini MiraiStudy logo: masked silhouette + brandname, both painted with the
                                             themed accent gradient so they recolor with the selected theme (like the real header logo). --}}
                                        <div class="h-5 w-5 shrink-0 bg-gradient-to-tr from-accent-from to-accent-to"
                                             role="img" aria-label="MiraiStudy Logo"
                                             style="-webkit-mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;
                                                             mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;"></div>
                                        <span class="text-[11px] font-bold tracking-tight bg-gradient-to-tr from-accent-from to-accent-to bg-clip-text text-transparent">MiraiStudy</span>
                                    </div>
                                    <div id="mockup-nav-list" class="space-y-0.5 pt-1">
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all" data-target="feed">
                                            <i data-lucide="home" class="h-3.5 w-3.5"></i><span>Feed</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-muted" data-target="exams">
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i><span>Exams</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-muted" data-target="quiz">
                                            <i data-lucide="circle-help" class="h-3.5 w-3.5"></i><span>Quiz</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-muted" data-target="focus">
                                            <i data-lucide="clock" class="h-3.5 w-3.5"></i><span>Focus</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-muted" data-target="notifications">
                                            <i data-lucide="bell" class="h-3.5 w-3.5"></i><span>Notifications</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-muted" data-target="bookmarks">
                                            <i data-lucide="bookmark" class="h-3.5 w-3.5"></i><span>Bookmarks</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-1 rounded-lg hover:bg-surface-muted cursor-pointer transition-colors">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-5 w-5 rounded-full bg-surface-muted flex items-center justify-center text-[8px] font-bold text-muted">
                                            {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                                        </div>
                                        <div class="leading-none text-left min-w-0">
                                            <div class="text-[9px] font-bold text-content truncate">{{ auth()->user()->display_name }}</div>
                                            <div class="text-[8px] text-muted">{{'@'.auth()->user()->username }}</div>
                                        </div>
                                    </div>
                                    <i data-lucide="chevron-up" class="h-2.5 w-2.5 text-muted"></i>
                                </div>
                            </aside>

                            {{-- Mini Main Content --}}
                            <main class="flex-1 flex bg-canvas p-3 overflow-hidden">
                                <div id="mockup-dynamic-stage"
                                     class="flex-1 text-left space-y-2.5 overflow-y-auto no-scrollbar">
                                </div>
                            </main>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </div>

    {{-- Snackbar --}}
    <div id="snackbar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 pointer-events-none">
        <div id="snackbar-inner"
             class="flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg translate-y-3 opacity-0 transition-all duration-300 ease-out">
            <span id="snackbar-msg"></span>
        </div>
    </div>

    @push('scripts')
    <script>
        // ── Initialise from server-side saved preferences ──────────
        // Prefer localStorage — it's what the sidebar toggle and the first-paint
        // <head> script trust — so the segmented control reflects the latest mode.
        // The DB value is the fallback when nothing's stored yet.
        let themeMode    = localStorage.getItem('themeMode') || '{{ $preferences->theme_mode }}';
        let currentTheme = '{{ $preferences->accent_color }}';
        let currentFill  = '{{ $preferences->fill_style }}';
        let currentActiveNav = 'feed';

        // Track whether unsaved changes exist
        let isDirty = false;

        // ── Fallback if DB has stale values ─────────────────────────
        // `gradient`/`border`/tints use the fixed mirai brand colors (see tailwind.config.js).
        // `text` uses the themed `text-accent` utility (= --accent), so accent TEXT tracks the
        // live [data-theme] + .dark on <html> and stays readable in dark mode (aurora/twilight
        // get a brightened --accent there — see resources/css/app.css). In light mode each
        // theme's --accent equals its old mirai text color, so this is identical there.
        // `gradient` is the exact picker-button gradient; prominent fills in the mockup use it.
        // Single-shade custom colors, so tints/badges use opacity modifiers (/10, /15…).
        const themeStyles = {
            venom:    { gradient: 'bg-gradient-to-tr from-mirai-lime to-mirai-dark',       solid: 'bg-mirai-lime',     text: 'text-accent', border: 'border-mirai-lime',     bgLight: 'bg-mirai-lime/10',    bgDarkActive: 'bg-mirai-dark/40',     badgeBg: 'bg-mirai-lime/10'    },
            aurora:   { gradient: 'bg-gradient-to-tr from-mirai-aurora to-mirai-violet',    solid: 'bg-blue-600',       text: 'text-accent', border: 'border-mirai-violet',   bgLight: 'bg-mirai-aurora/10',  bgDarkActive: 'bg-mirai-violet/40',   badgeBg: 'bg-mirai-aurora/15'  },
            sangria:  { gradient: 'bg-gradient-to-tr from-mirai-sangria to-mirai-obsidian', solid: 'bg-mirai-sangria',  text: 'text-accent', border: 'border-mirai-sangria',  bgLight: 'bg-mirai-sangria/10', bgDarkActive: 'bg-mirai-obsidian/40', badgeBg: 'bg-mirai-sangria/10' },
            twilight: { gradient: 'bg-gradient-to-tr from-mirai-sunset to-mirai-midnight',  solid: 'bg-mirai-midnight', text: 'text-accent', border: 'border-mirai-midnight', bgLight: 'bg-mirai-sunset/15',  bgDarkActive: 'bg-mirai-midnight/40', badgeBg: 'bg-mirai-sunset/20'  },
            inferno:  { gradient: 'bg-gradient-to-tr from-mirai-apricot to-mirai-slate',    solid: 'bg-mirai-apricot',  text: 'text-accent', border: 'border-mirai-apricot',  bgLight: 'bg-mirai-apricot/10', bgDarkActive: 'bg-mirai-slate/40',    badgeBg: 'bg-mirai-apricot/10' }
        };

        if (!themeStyles[currentTheme]) currentTheme = 'venom';
        if (!['gradient', 'solid'].includes(currentFill)) currentFill = 'gradient';
        if (!['light', 'dark', 'system'].includes(themeMode)) themeMode = 'light';

        // ── Mockup page content ─────────────────────────────────────
        const mockupPages = {
            feed: `
                <button id="mockup-create-btn" class="w-full text-[10px] py-2 text-white font-medium rounded-lg shadow-sm flex items-center justify-center gap-1 mb-2">
                    <i data-lucide="plus" class="h-3.5 w-3.5"></i> Create Post
                </button>
                <div class="mockup-card border rounded-xl p-3 space-y-2 bg-surface border-line shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white">A</div>
                            <div>
                                <div class="text-[9px] font-bold text-content">Admin</div>
                                <div class="text-[8px] text-muted">2 minutes ago</div>
                            </div>
                        </div>
                        <button class="mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md border-line">Follow</button>
                    </div>
                    <p class="text-[10px] font-bold text-content leading-tight">Study tip: Use spaced repetition for JLPT vocab.</p>
                    <p class="text-[9px] text-muted leading-normal">Reviewing cards at increasing intervals helps move vocabulary into long-term memory faster.</p>
                    <div class="flex gap-1 flex-wrap pt-0.5">
                        <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">JLPT</span>
                        <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">Study Tips</span>
                    </div>
                    <div class="pt-1 flex items-center justify-between border-t border-line text-muted">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="thumbs-up" class="h-2.5 w-2.5"></i> 12</span>
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="message-circle" class="h-2.5 w-2.5"></i> 4</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="bookmark" class="h-2.5 w-2.5 fill-accent"></i>
                            <i data-lucide="send" class="h-2.5 w-2.5"></i>
                        </div>
                    </div>
                </div>
                <div class="mockup-card border rounded-xl p-3 space-y-2 bg-surface border-line shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white">M</div>
                            <div>
                                <div class="text-[9px] font-bold text-content">Moderator</div>
                                <div class="text-[8px] text-muted">5 minutes ago</div>
                            </div>
                        </div>
                        <button class="mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md border-line">Follow</button>
                    </div>
                    <p class="text-[9px] text-muted leading-normal">ITPEC FE exam is next month. Don't forget to practice past papers under timed conditions.</p>
                    <div class="flex gap-1 flex-wrap pt-0.5">
                        <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">ITPEC</span>
                    </div>
                    <div class="pt-1 flex items-center justify-between border-t border-line text-muted">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="thumbs-up" class="h-2.5 w-2.5"></i> 5</span>
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="message-circle" class="h-2.5 w-2.5"></i> 2</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="bookmark" class="h-2.5 w-2.5"></i>
                            <i data-lucide="send" class="h-2.5 w-2.5"></i>
                        </div>
                    </div>
                </div>
            `,
            exams: `
                <div class="bg-surface border border-line rounded-xl p-3 space-y-2.5 shadow-sm">
                    <h3 class="text-[11px] font-bold text-content">Exam Papers</h3>
                    <div class="space-y-2">
                        <div class="border border-line p-2 rounded-lg flex items-center justify-between bg-canvas">
                            <div>
                                <div class="text-[10px] font-bold text-content">JLPT N2 — 2023</div>
                                <div class="text-[8px] text-muted">PDF · 2.4 MB</div>
                            </div>
                            <button class="mockup-btn-accent text-[9px] text-white font-medium px-2.5 py-1 rounded shadow-sm">Download</button>
                        </div>
                        <div class="border border-line p-2 rounded-lg flex items-center justify-between bg-canvas">
                            <div>
                                <div class="text-[10px] font-bold text-content">ITPEC FE — 2022</div>
                                <div class="text-[8px] text-muted">PDF · 1.8 MB</div>
                            </div>
                            <button class="mockup-btn-accent text-[9px] text-white font-medium px-2.5 py-1 rounded shadow-sm">Download</button>
                        </div>
                    </div>
                </div>
            `,
            quiz: `
                <div class="bg-surface border border-line rounded-xl p-3 space-y-3 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] uppercase tracking-wider font-bold text-muted">JLPT N3 Quiz</span>
                        <span id="mockup-text-accent" class="text-[9px] font-bold">Q 3 / 10</span>
                    </div>
                    <p class="text-[10px] font-semibold text-content">この単語の読み方は何ですか？</p>
                    <div class="space-y-1.5">
                        <button class="w-full text-left text-[9px] border border-line p-2 rounded-lg text-content">A. きょうかしょ</button>
                        <button id="mockup-border-accent" class="w-full text-left text-[9px] border p-2 rounded-lg text-content">B. べんきょう</button>
                        <button class="w-full text-left text-[9px] border border-line p-2 rounded-lg text-content">C. せんせい</button>
                    </div>
                </div>
            `,
            focus: `
                <div class="bg-surface border border-line rounded-xl p-4 text-center space-y-3 shadow-sm">
                    <div class="text-[9px] uppercase tracking-wider font-bold text-muted">Focus Session</div>
                    <div class="text-3xl font-bold font-mono tracking-tight text-content">24:59</div>
                    <div class="text-[9px] text-muted">Session 2 of 4</div>
                    <div class="flex items-center justify-center gap-2 pt-1">
                        <button class="mockup-btn-accent text-[9px] text-white font-medium px-3 py-1 rounded-md shadow-sm">Pause</button>
                        <button class="text-[9px] border border-line font-medium px-3 py-1 rounded-md text-muted">Skip</button>
                    </div>
                </div>
            `,
            notifications: `
                <div class="space-y-2">
                    <div class="flex items-center justify-between mb-1">
                        <div>
                            <div class="text-[10px] font-bold text-content">Notifications</div>
                            <div class="text-[8px] text-muted">2 unread</div>
                        </div>
                        <button class="text-[8px] font-medium text-muted border border-line rounded-md px-1.5 py-0.5">Mark all read</button>
                    </div>
                    <div class="flex gap-2 px-2 py-2 rounded-xl border border-accent/40 bg-surface shadow-sm">
                        <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white shrink-0">A</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-1">
                                <p class="text-[9px] font-semibold text-content leading-snug">Admin liked your post</p>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                                    <span class="text-[7px] text-muted">2m</span>
                                </div>
                            </div>
                            <p class="text-[8px] text-muted mt-0.5 leading-relaxed">Your study tip post got a like.</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="w-3.5 h-3.5 rounded-full bg-red-500/10 flex items-center justify-center shrink-0">
                                    <i data-lucide="thumbs-up" class="w-2 h-2 text-red-500"></i>
                                </span>
                                <span class="text-[8px] font-semibold text-accent">View</span>
                                <span class="text-[8px] text-muted">Mark as Read</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 px-2 py-2 rounded-xl border border-line bg-surface">
                        <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white shrink-0">M</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-1">
                                <p class="text-[9px] font-semibold text-content leading-snug">Moderator followed you</p>
                                <span class="text-[7px] text-muted shrink-0">5m</span>
                            </div>
                            <p class="text-[8px] text-muted mt-0.5 leading-relaxed">You have a new follower.</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="w-3.5 h-3.5 rounded-full bg-green-500/10 flex items-center justify-center shrink-0">
                                    <i data-lucide="user-plus" class="w-2 h-2 text-green-500"></i>
                                </span>
                                <span class="text-[8px] font-semibold text-accent">View</span>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            bookmarks: `
                <div class="mockup-card border rounded-xl p-3 space-y-2 bg-surface border-line shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white">A</div>
                            <div>
                                <div class="text-[9px] font-bold text-content">Admin</div>
                                <div class="text-[8px] text-muted">2 minutes ago</div>
                            </div>
                        </div>
                        <button class="mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md border-line">Follow</button>
                    </div>
                    <p class="text-[10px] font-bold text-content leading-tight">Study tip: Use spaced repetition for JLPT vocab.</p>
                    <p class="text-[9px] text-muted leading-normal">Reviewing cards at increasing intervals helps move vocabulary into long-term memory faster.</p>
                    <div class="flex gap-1 flex-wrap pt-0.5">
                        <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">JLPT</span>
                        <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">Study Tips</span>
                    </div>
                    <div class="pt-1 flex items-center justify-between border-t border-line text-muted">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="thumbs-up" class="h-2.5 w-2.5"></i> 12</span>
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="message-circle" class="h-2.5 w-2.5"></i> 4</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="bookmark" class="h-2.5 w-2.5 fill-accent"></i>
                            <i data-lucide="send" class="h-2.5 w-2.5"></i>
                        </div>
                    </div>
                </div>
            `
        };

        // ── DOM refs ────────────────────────────────────────────────
        const themeLightBtn  = document.getElementById('theme-light');
        const themeDarkBtn   = document.getElementById('theme-dark');
        const themeSystemBtn = document.getElementById('theme-system');
        const themeButtons    = document.querySelectorAll('.theme-btn');
        const fillGradientBtn = document.getElementById('fill-gradient');
        const fillSolidBtn    = document.getElementById('fill-solid');
        const mockupNavItems  = document.querySelectorAll('.mockup-nav-item');
        const saveBtn        = document.getElementById('save-btn');

        const SEG_BASE  = "seg-btn flex flex-row items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-semibold transition-all cursor-pointer";
        const FILL_BASE = "fill-btn flex items-center justify-center rounded-lg py-2 text-xs font-semibold transition-all cursor-pointer";

        // Accent fill helper: solid mode paints flat `bg-accent` (= --accent); gradient mode
        // uses the theme's mirai gradient. Mirrors the CSS [data-fill] collapse the real app uses.
        const accentFill = () => currentFill === 'solid' ? 'bg-accent' : themeStyles[currentTheme].gradient;

        // Save button styling — themed to the selected accent fill.
        const SAVE_BASE = 'w-full rounded-xl px-5 py-3 text-sm font-bold text-white shadow-sm transition-all';
        const saveBtnActiveClass = () => `${SAVE_BASE} ${accentFill()} hover:opacity-90 active:scale-[0.98]`;
        const saveBtnSavingClass = () => `${SAVE_BASE} ${accentFill()} opacity-60 cursor-not-allowed`;

        // ── Safe Lucide wrapper ─────────────────────────────────────
        function safeCreateIcons() {
            try {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            } catch (e) {
                // Lucide not ready — icons render on retry or user interaction
            }
        }

        // ── Snackbar ────────────────────────────────────────────────
        let snackTimer;
        function showSnackbar(msg, ok) {
            const inner = document.getElementById('snackbar-inner');
            const msgEl = document.getElementById('snackbar-msg');
            clearTimeout(snackTimer);
            msgEl.textContent = msg;
            inner.className = `flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-300 ease-out ${ok ? accentFill() : 'bg-red-500'}`;
            requestAnimationFrame(() => requestAnimationFrame(() => {
                inner.classList.remove('translate-y-3', 'opacity-0');
            }));
            snackTimer = setTimeout(() => {
                inner.classList.add('translate-y-3', 'opacity-0');
            }, 2500);
        }

        // ── Save button state helpers ───────────────────────────────
        function markDirty() {
            isDirty = true;
            saveBtn.textContent = 'Save Changes';
            saveBtn.disabled = false;
            saveBtn.className = saveBtnActiveClass();
        }

        function setSaving() {
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;
            saveBtn.className = saveBtnSavingClass();
        }

        function setSaved() {
            isDirty = false;
            saveBtn.textContent = 'Saved';
            saveBtn.disabled = true;
            saveBtn.className = 'w-full rounded-xl bg-surface-muted px-5 py-3 text-sm font-bold text-muted cursor-not-allowed';
            showSnackbar('Preferences saved', true);

            setTimeout(() => {
                saveBtn.textContent = 'Save Changes';
                saveBtn.disabled = false;
                saveBtn.className = saveBtnActiveClass();
            }, 2000);
        }

        function setError() {
            saveBtn.textContent = 'Save Changes';
            saveBtn.disabled = false;
            saveBtn.className = saveBtnActiveClass();
            showSnackbar('Failed to save. Please try again.', false);
        }

        // ── Apply the chosen theme to the real page (<html>) ────────
        function applyThemeToDocument() {
            const root = document.documentElement;
            root.setAttribute('data-theme', currentTheme);
            root.setAttribute('data-fill', currentFill);
            const dark = themeMode === 'dark'
                || (themeMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            root.classList.toggle('dark', dark);
            // Notify the sidebar toggle (Alpine) so its icon + label flip live.
            window.dispatchEvent(new CustomEvent('theme-mode-changed', { detail: { mode: themeMode } }));
        }

        // ── Save to database ────────────────────────────────────────
        async function savePreferences() {
            setSaving();
            try {
                const res = await fetch('{{ route('settings.update') }}', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        theme_mode:   themeMode,
                        accent_color: currentTheme,
                        fill_style:   currentFill,
                    })
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                applyThemeToDocument();
                // Keep the first-paint <head> script and the sidebar toggle in sync —
                // they read this first, so a Save that skipped it would revert on reload.
                localStorage.setItem('themeMode', themeMode);
                setSaved();

            } catch (err) {
                console.error('Failed to save preferences:', err);
                setError();
            }
        }

        // ── Mockup rendering ────────────────────────────────────────
        function switchMockupPage(targetPage) {
            currentActiveNav = targetPage;
            const stage = document.getElementById('mockup-dynamic-stage');
            stage.innerHTML = mockupPages[targetPage];
            applyThemeColorsToMockup();
            if (typeof window.renderIcons === 'function') {
                window.renderIcons(stage);
            } else {
                safeCreateIcons();
            }
        }

        function applyThemeColorsToMockup() {
            const c = themeStyles[currentTheme];
            const fill = accentFill();

            // Mini logo + brandname recolor on their own via the --accent-from/--accent-to CSS vars.

            const createBtn = document.getElementById('mockup-create-btn');
            if (createBtn) createBtn.className = `w-full text-[10px] py-2 text-white font-medium rounded-lg shadow-sm flex items-center justify-center gap-1 mb-2 ${fill} hover:opacity-90 transition-opacity`;

            document.querySelectorAll('.mockup-avatar-badge').forEach(el => {
                el.className = `mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white ${fill}`;
            });

            document.querySelectorAll('.mockup-tag').forEach(el => {
                el.className = `mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded ${c.text} ${c.badgeBg}`;
            });

            document.querySelectorAll('.mockup-interactive-text').forEach(el => {
                el.className = `mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md border-line ${c.text}`;
            });

            document.querySelectorAll('.mockup-btn-accent').forEach(el => {
                el.className = `mockup-btn-accent text-[9px] text-white font-medium px-2.5 py-1 rounded shadow-sm ${fill} hover:opacity-90 transition-opacity`;
            });

            const borderAccent = document.getElementById('mockup-border-accent');
            if (borderAccent) borderAccent.className = `w-full text-left text-[9px] border p-2 rounded-lg text-content ${c.border} ${c.bgLight}`;

            const textAccent = document.getElementById('mockup-text-accent');
            if (textAccent) textAccent.className = `text-[9px] font-bold ${c.text}`;

            mockupNavItems.forEach(item => {
                const isActive = item.getAttribute('data-target') === currentActiveNav;
                item.className = isActive
                    ? `mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-bold transition-all ${c.bgLight} ${c.text}`
                    : 'mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-muted hover:bg-surface-muted';
            });
        }

        function updateSegmentedControl() {
            const fill = accentFill();
            const segs = { light: themeLightBtn, dark: themeDarkBtn, system: themeSystemBtn };
            Object.entries(segs).forEach(([mode, btn]) => {
                btn.className = mode === themeMode
                    ? `${SEG_BASE} text-white shadow-sm ${fill}`
                    : `${SEG_BASE} text-muted hover:text-content`;
            });
        }

        function updateFillToggle() {
            const fill = accentFill();
            fillGradientBtn.className = currentFill === 'gradient'
                ? `${FILL_BASE} text-white shadow-sm ${fill}`
                : `${FILL_BASE} text-muted hover:text-content`;
            fillSolidBtn.className = currentFill === 'solid'
                ? `${FILL_BASE} text-white shadow-sm ${fill}`
                : `${FILL_BASE} text-muted hover:text-content`;
        }

        function updateColorButtons() {
            const SWATCH_BASE = 'theme-btn relative flex flex-col items-center justify-center gap-1 rounded-xl py-4 text-white shadow-sm transition-all cursor-pointer';
            themeButtons.forEach(btn => {
                const t = btn.getAttribute('data-theme');
                const isActive = t === currentTheme;
                const bgClass = currentFill === 'solid' ? themeStyles[t].solid : themeStyles[t].gradient;
                const checkIcon = btn.querySelector('.check-icon');
                btn.className = `${SWATCH_BASE} ${bgClass}`;
                if (isActive) {
                    btn.classList.add('ring-2', 'ring-offset-2', 'ring-content', 'ring-offset-surface');
                    checkIcon?.classList.remove('hidden');
                } else {
                    checkIcon?.classList.add('hidden');
                }
            });
        }

        function render() {
            applyThemeToDocument();   // live-preview on the real app (Save persists)
            updateSegmentedControl();
            updateFillToggle();
            updateColorButtons();
            applyThemeColorsToMockup();
            safeCreateIcons();
        }

        // ── Event listeners ─────────────────────────────────────────
        themeLightBtn.addEventListener('click', () => {
            themeMode = 'light'; render(); markDirty();
        });
        themeDarkBtn.addEventListener('click', () => {
            themeMode = 'dark'; render(); markDirty();
        });
        themeSystemBtn.addEventListener('click', () => {
            themeMode = 'system'; render(); markDirty();
        });

        themeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                currentTheme = btn.getAttribute('data-theme');
                render();
                markDirty();
            });
        });

        fillGradientBtn.addEventListener('click', () => {
            currentFill = 'gradient'; render(); markDirty();
        });
        fillSolidBtn.addEventListener('click', () => {
            currentFill = 'solid'; render(); markDirty();
        });

        mockupNavItems.forEach(item => {
            item.addEventListener('click', () => {
                switchMockupPage(item.getAttribute('data-target'));
            });
        });

        saveBtn.addEventListener('click', savePreferences);

        // Sidebar toggle changed the mode → mirror it on the segmented control.
        // The sidebar already persisted it to the DB, so don't mark the page dirty,
        // and don't re-apply/re-dispatch (the equality guard breaks any loop).
        window.addEventListener('theme-mode-changed', (e) => {
            const mode = e.detail?.mode;
            if (!['light', 'dark', 'system'].includes(mode) || mode === themeMode) return;
            themeMode = mode;
            updateSegmentedControl();
        });

        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // ── Init ─────────────────────────────────────────────────────
        // Step 1: Pre-select buttons immediately — no Lucide needed
        updateSegmentedControl();
        updateFillToggle();
        updateColorButtons();

        // Step 2: Load mockup content and apply colors
        document.getElementById('mockup-dynamic-stage').innerHTML = mockupPages['feed'];
        applyThemeColorsToMockup();

        // Step 3: Lucide icons — try now, retry after short delay
        safeCreateIcons();
        setTimeout(safeCreateIcons, 300);

        // Step 4: Save button starts in normal state, themed to the saved accent
        saveBtn.textContent = 'Save Changes';
        saveBtn.disabled = false;
        saveBtn.className = saveBtnActiveClass();
</script>
@endpush

@push('scripts')
<script>
    // ── Two-factor toggle: instant-save (mirrors the theme-mode quick save) ──────
    (function () {
        const toggle = document.getElementById('twofa-toggle');
        const knob   = document.getElementById('twofa-knob');
        if (!toggle) return;

        function paint(enabled) {
            toggle.dataset.enabled = enabled ? '1' : '0';
            toggle.setAttribute('aria-checked', enabled ? 'true' : 'false');
            toggle.classList.toggle('bg-accent', enabled);
            toggle.classList.toggle('bg-line', !enabled);
            knob.classList.toggle('translate-x-5', enabled);
            knob.classList.toggle('translate-x-1', !enabled);
        }

        paint(toggle.dataset.enabled === '1');

        toggle.addEventListener('click', async () => {
            const next = toggle.dataset.enabled !== '1';
            paint(next);                 // optimistic
            toggle.disabled = true;
            try {
                const res = await fetch('{{ route('settings.two-factor') }}', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ two_factor_enabled: next }),
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                showSnackbar(next ? 'Two-factor enabled' : 'Two-factor disabled', true);
            } catch (e) {
                paint(!next);            // revert on failure
                showSnackbar('Failed to update. Please try again.', false);
            } finally {
                toggle.disabled = false;
            }
        });
    })();
</script>
@endpush
</x-app-layout>