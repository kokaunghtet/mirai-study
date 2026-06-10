<x-app-layout>
    <x-slot name="title">Settings — MiraiStudy</x-slot>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <div class="w-full min-h-screen flex items-center p-6 md:p-10 lg:p-12 bg-slate-50 transition-colors duration-300">
        <div class="max-w-6xl mx-auto w-full">

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 lg:gap-8 items-start">

                {{-- Left Column — Controls --}}
                <div class="lg:col-span-3 space-y-4">

                    <header class="mb-8">
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Appearance</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Customize how your application interface looks and feels.
                        </p>
                        {{-- Saved indicator --}}
                        <p id="save-status" class="mt-2 text-xs text-green-600 font-semibold hidden">
                            ✓ Saved
                        </p>
                    </header>

                    {{-- Theme Mode --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold mb-4 text-slate-900">Theme</h2>
                        <p class="text-xs text-slate-400 mb-4">Dark mode will be available soon.</p>

                        <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1">
                            <button id="theme-light"
                                    class="seg-btn flex flex-col items-center justify-center gap-1.5 rounded-lg py-2.5 text-xs font-semibold transition-all">
                                <i data-lucide="sun" class="h-5 w-5"></i>
                                <span>Light</span>
                            </button>
                            <button id="theme-dark"
                                    class="seg-btn flex flex-col items-center justify-center gap-1.5 rounded-lg py-2.5 text-xs font-semibold transition-all"
                                    title="Coming soon" disabled>
                                <i data-lucide="moon" class="h-5 w-5"></i>
                                <span>Dark</span>
                            </button>
                            <button id="theme-system"
                                    class="seg-btn flex flex-col items-center justify-center gap-1.5 rounded-lg py-2.5 text-xs font-semibold transition-all">
                                <i data-lucide="settings" class="h-5 w-5"></i>
                                <span class="text-center leading-tight">System</span>
                            </button>
                        </div>
                    </section>

                    {{-- Primary Color --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold mb-4 text-slate-900">Primary Color</h2>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-gradient-to-tr from-[#B5E853] to-[#0A1200]"
                                    data-theme="venom">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Venom</span>
                            </button>
                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-blue-500"
                                    data-theme="aurora">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Aurora</span>
                            </button>
                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-violet-500"
                                    data-theme="sangria">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Sangria</span>
                            </button>
                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-rose-500"
                                    data-theme="twilight">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Twilight</span>
                            </button>
                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-amber-500"
                                    data-theme="inferno">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Inferno</span>
                            </button>
                        </div>
                    </section>

                    {{-- Save Button --}}
                    <div class="flex flex-col gap-2">
                        <button id="save-btn"
                                type="button"
                                class="w-full rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-green-700 active:scale-[0.98]">
                            Save Changes
                        </button>
                        <p id="save-status" class="text-center text-xs text-green-600 font-semibold hidden">
                            ✓ Preferences saved
                        </p>
                        <p id="save-error" class="text-center text-xs text-red-500 font-semibold hidden">
                            Failed to save. Please try again.
                        </p>
                    </div>

                </div>

                {{-- Right Column — Live Preview --}}
                <div class="lg:col-span-2">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">
                            Live Interface Preview
                        </h3>

                        <div id="mockup-container"
                             class="w-full border border-slate-200 bg-slate-50 rounded-xl overflow-hidden shadow-inner flex"
                             style="height: 460px;">

                            {{-- Mini Sidebar --}}
                            <aside id="mockup-sidebar"
                                   class="w-2/5 border-r border-slate-200 bg-white p-2.5 flex flex-col justify-between shrink-0 select-none">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-1.5 px-1 py-1">
                                        <i data-lucide="book-open" id="mockup-logo-icon" class="h-4 w-4"></i>
                                        <span class="text-[11px] font-bold tracking-tight text-slate-800">MiraiStudy</span>
                                    </div>
                                    <div id="mockup-nav-list" class="space-y-0.5 pt-1">
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all" data-target="feed">
                                            <i data-lucide="home" class="h-3.5 w-3.5"></i><span>Feed</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="exams">
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i><span>Exams</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="quiz">
                                            <i data-lucide="circle-help" class="h-3.5 w-3.5"></i><span>Quiz</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="focus">
                                            <i data-lucide="clock" class="h-3.5 w-3.5"></i><span>Focus</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="notifications">
                                            <i data-lucide="bell" class="h-3.5 w-3.5"></i><span>Notifications</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="bookmarks">
                                            <i data-lucide="bookmark" class="h-3.5 w-3.5"></i><span>Bookmarks</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-1 rounded-lg hover:bg-slate-100 cursor-pointer transition-colors">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-5 w-5 rounded-full bg-slate-300 flex items-center justify-center text-[8px] font-bold text-slate-600">
                                            {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                                        </div>
                                        <div class="leading-none text-left">
                                            <div class="text-[9px] font-bold text-slate-700">{{ auth()->user()->display_name }}</div>
                                            <div class="text-[8px] text-slate-400">@{{ auth()->user()->username }}</div>
                                        </div>
                                    </div>
                                    <i data-lucide="chevron-up" class="h-2.5 w-2.5 text-slate-400"></i>
                                </div>
                            </aside>

                            {{-- Mini Main Content --}}
                            <main class="flex-1 flex bg-slate-50 p-3 overflow-hidden">
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

    @push('scripts')
    <script>
        // ── Initialise from server-side saved preferences ──────────
        let themeMode    = '{{ $preferences->theme_mode }}';
        let currentTheme = '{{ $preferences->accent_color }}';
        let currentActiveNav = 'feed';

        // Track whether unsaved changes exist
        let isDirty = false;

        // ── Fallback if DB has stale values ─────────────────────────
        const themeStyles = {
            venom:   { bg: 'bg-green-500',  text: 'text-green-500',  border: 'border-green-500',  hoverBg: 'hover:bg-green-600',  bgLight: 'bg-green-50',   bgDarkActive: 'bg-green-950/40',  badgeBg: 'bg-green-500/10'  },
            aurora:  { bg: 'bg-blue-500',   text: 'text-blue-500',   border: 'border-blue-500',   hoverBg: 'hover:bg-blue-600',   bgLight: 'bg-blue-50',    bgDarkActive: 'bg-blue-950/40',   badgeBg: 'bg-blue-500/10'   },
            sangria: { bg: 'bg-violet-500', text: 'text-violet-500', border: 'border-violet-500', hoverBg: 'hover:bg-violet-600', bgLight: 'bg-violet-50',  bgDarkActive: 'bg-violet-950/40', badgeBg: 'bg-violet-500/10' },
            twilight:{ bg: 'bg-rose-500',   text: 'text-rose-500',   border: 'border-rose-500',   hoverBg: 'hover:bg-rose-600',   bgLight: 'bg-rose-50',    bgDarkActive: 'bg-rose-950/40',   badgeBg: 'bg-rose-500/10'   },
            inferno: { bg: 'bg-amber-500',  text: 'text-amber-500',  border: 'border-amber-500',  hoverBg: 'hover:bg-amber-600',  bgLight: 'bg-amber-50',   bgDarkActive: 'bg-amber-950/40',  badgeBg: 'bg-amber-500/10'  }
        };

        if (!themeStyles[currentTheme]) currentTheme = 'venom';
        if (!['light', 'dark', 'system'].includes(themeMode)) themeMode = 'light';

        // ── Mockup page content ─────────────────────────────────────
        const mockupPages = {
            feed: `
                <button id="mockup-create-btn" class="w-full text-[10px] py-2 text-white font-medium rounded-lg shadow-sm flex items-center justify-center gap-1 mb-2">
                    <i data-lucide="plus" class="h-3.5 w-3.5"></i> Create Post
                </button>
                <div class="mockup-card border rounded-xl p-3 space-y-2 bg-white border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white">A</div>
                            <div>
                                <div class="text-[9px] font-bold text-slate-800">Admin</div>
                                <div class="text-[8px] text-slate-400">2 minutes ago</div>
                            </div>
                        </div>
                        <button class="mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md border-slate-200">Follow</button>
                    </div>
                    <p class="text-[10px] font-bold text-slate-800 leading-tight">Study tip: Use spaced repetition for JLPT vocab.</p>
                    <p class="text-[9px] text-slate-500 leading-normal">Reviewing cards at increasing intervals helps move vocabulary into long-term memory faster.</p>
                    <div class="flex gap-1 flex-wrap pt-0.5">
                        <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">JLPT</span>
                        <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">Study Tips</span>
                    </div>
                    <div class="pt-1 flex items-center justify-between border-t border-slate-100 text-slate-400">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="thumbs-up" class="h-2.5 w-2.5"></i> 12</span>
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="message-square" class="h-2.5 w-2.5"></i> 4</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="bookmark" class="h-2.5 w-2.5"></i>
                            <i data-lucide="send" class="h-2.5 w-2.5"></i>
                        </div>
                    </div>
                </div>
                <div class="mockup-card border rounded-xl p-3 space-y-2 bg-white border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white">M</div>
                            <div>
                                <div class="text-[9px] font-bold text-slate-800">Moderator</div>
                                <div class="text-[8px] text-slate-400">5 minutes ago</div>
                            </div>
                        </div>
                        <button class="mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md border-slate-200">Follow</button>
                    </div>
                    <p class="text-[9px] text-slate-500 leading-normal">ITPEC FE exam is next month. Don't forget to practice past papers under timed conditions.</p>
                    <div class="flex gap-1 flex-wrap pt-0.5">
                        <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">ITPEC</span>
                    </div>
                    <div class="pt-1 flex items-center justify-between border-t border-slate-100 text-slate-400">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="thumbs-up" class="h-2.5 w-2.5"></i> 5</span>
                            <span class="flex items-center gap-1 text-[8px]"><i data-lucide="message-square" class="h-2.5 w-2.5"></i> 2</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="bookmark" class="h-2.5 w-2.5"></i>
                            <i data-lucide="send" class="h-2.5 w-2.5"></i>
                        </div>
                    </div>
                </div>
            `,
            exams: `
                <div class="bg-white border border-slate-200 rounded-xl p-3 space-y-2.5 shadow-sm">
                    <h3 class="text-[11px] font-bold text-slate-800">Exam Papers</h3>
                    <div class="space-y-2">
                        <div class="border border-slate-100 p-2 rounded-lg flex items-center justify-between bg-slate-50">
                            <div>
                                <div class="text-[10px] font-bold text-slate-800">JLPT N2 — 2023</div>
                                <div class="text-[8px] text-slate-400">PDF · 2.4 MB</div>
                            </div>
                            <button class="mockup-btn-accent text-[9px] text-white font-medium px-2.5 py-1 rounded shadow-sm">Download</button>
                        </div>
                        <div class="border border-slate-100 p-2 rounded-lg flex items-center justify-between bg-slate-50">
                            <div>
                                <div class="text-[10px] font-bold text-slate-800">ITPEC FE — 2022</div>
                                <div class="text-[8px] text-slate-400">PDF · 1.8 MB</div>
                            </div>
                            <button class="mockup-btn-accent text-[9px] text-white font-medium px-2.5 py-1 rounded shadow-sm">Download</button>
                        </div>
                    </div>
                </div>
            `,
            quiz: `
                <div class="bg-white border border-slate-200 rounded-xl p-3 space-y-3 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400">JLPT N3 Quiz</span>
                        <span id="mockup-text-accent" class="text-[9px] font-bold">Q 3 / 10</span>
                    </div>
                    <p class="text-[10px] font-semibold text-slate-800">この単語の読み方は何ですか？</p>
                    <div class="space-y-1.5">
                        <button class="w-full text-left text-[9px] border border-slate-200 p-2 rounded-lg text-slate-700">A. きょうかしょ</button>
                        <button id="mockup-border-accent" class="w-full text-left text-[9px] border p-2 rounded-lg text-slate-700">B. べんきょう</button>
                        <button class="w-full text-left text-[9px] border border-slate-200 p-2 rounded-lg text-slate-700">C. せんせい</button>
                    </div>
                </div>
            `,
            focus: `
                <div class="bg-white border border-slate-200 rounded-xl p-4 text-center space-y-3 shadow-sm">
                    <div class="text-[9px] uppercase tracking-wider font-bold text-slate-400">Focus Session</div>
                    <div class="text-3xl font-bold font-mono tracking-tight text-slate-800">24:59</div>
                    <div class="text-[9px] text-slate-400">Session 2 of 4</div>
                    <div class="flex items-center justify-center gap-2 pt-1">
                        <button class="mockup-btn-accent text-[9px] text-white font-medium px-3 py-1 rounded-md shadow-sm">Pause</button>
                        <button class="text-[9px] border border-slate-200 font-medium px-3 py-1 rounded-md text-slate-600">Skip</button>
                    </div>
                </div>
            `,
            notifications: `
                <div class="bg-white border border-slate-200 rounded-xl p-3 space-y-2 shadow-sm">
                    <div class="text-[10px] font-bold text-slate-800 mb-2">Notifications</div>
                    <div class="flex items-start gap-2 p-2 rounded-lg bg-slate-50">
                        <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white shrink-0">A</div>
                        <p class="text-[9px] text-slate-600"><strong>Admin</strong> liked your post.</p>
                    </div>
                    <div class="flex items-start gap-2 p-2 rounded-lg">
                        <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white shrink-0">M</div>
                        <p class="text-[9px] text-slate-600"><strong>Moderator</strong> started following you.</p>
                    </div>
                </div>
            `,
            bookmarks: `
                <div class="bg-white border border-slate-200 rounded-xl p-3 space-y-2 shadow-sm">
                    <div class="text-[10px] font-bold text-slate-800 mb-2">Saved Posts</div>
                    <div class="border border-slate-100 rounded-lg p-2 bg-slate-50">
                        <p class="text-[9px] font-semibold text-slate-700">Study tip: Use spaced repetition for JLPT vocab.</p>
                        <p class="text-[8px] text-slate-400 mt-0.5">Admin · 2 minutes ago</p>
                    </div>
                </div>
            `
        };

        // ── DOM refs ────────────────────────────────────────────────
        const themeLightBtn  = document.getElementById('theme-light');
        const themeDarkBtn   = document.getElementById('theme-dark');
        const themeSystemBtn = document.getElementById('theme-system');
        const themeButtons   = document.querySelectorAll('.theme-btn');
        const mockupNavItems = document.querySelectorAll('.mockup-nav-item');
        const saveBtn        = document.getElementById('save-btn');
        const saveStatus     = document.getElementById('save-status');
        const saveError      = document.getElementById('save-error');

        const SEG_BASE = "seg-btn flex flex-col items-center justify-center gap-1.5 rounded-lg py-2.5 text-xs font-semibold transition-all";

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

        // ── Save button state helpers ───────────────────────────────
        function markDirty() {
            isDirty = true;
            saveBtn.textContent = 'Save Changes';
            saveBtn.disabled = false;
            saveBtn.className = 'w-full rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-green-700 active:scale-[0.98]';
            saveStatus.classList.add('hidden');
            saveError.classList.add('hidden');
        }

        function setSaving() {
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;
            saveBtn.className = 'w-full rounded-xl bg-green-400 px-5 py-3 text-sm font-bold text-white shadow-sm cursor-not-allowed';
        }

        function setSaved() {
            isDirty = false;
            saveBtn.textContent = 'Saved';
            saveBtn.disabled = true;
            saveBtn.className = 'w-full rounded-xl bg-gray-100 px-5 py-3 text-sm font-bold text-gray-400 cursor-not-allowed';
            saveStatus.classList.remove('hidden');
            saveError.classList.add('hidden');

            setTimeout(() => {
                saveStatus.classList.add('hidden');
                saveBtn.textContent = 'Save Changes';
                saveBtn.disabled = false;
                saveBtn.className = 'w-full rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-green-700 active:scale-[0.98]';
            }, 2000);
        }

        function setError() {
            saveBtn.textContent = 'Save Changes';
            saveBtn.disabled = false;
            saveBtn.className = 'w-full rounded-xl bg-green-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-green-700 active:scale-[0.98]';
            saveError.classList.remove('hidden');
            saveStatus.classList.add('hidden');
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
                    })
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                setSaved();

            } catch (err) {
                console.error('Failed to save preferences:', err);
                setError();
            }
        }

        // ── Mockup rendering ────────────────────────────────────────
        function switchMockupPage(targetPage) {
            currentActiveNav = targetPage;
            document.getElementById('mockup-dynamic-stage').innerHTML = mockupPages[targetPage];
            applyThemeColorsToMockup();
            safeCreateIcons();
        }

        function applyThemeColorsToMockup() {
            const c = themeStyles[currentTheme];

            document.getElementById('mockup-logo-icon').className = `h-4 w-4 transition-colors ${c.text}`;

            const createBtn = document.getElementById('mockup-create-btn');
            if (createBtn) createBtn.className = `w-full text-[10px] py-2 text-white font-medium rounded-lg shadow-sm flex items-center justify-center gap-1 mb-2 ${c.bg} ${c.hoverBg}`;

            document.querySelectorAll('.mockup-avatar-badge').forEach(el => {
                el.className = `mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white ${c.bg}`;
            });

            document.querySelectorAll('.mockup-tag').forEach(el => {
                el.className = `mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded ${c.text} ${c.badgeBg}`;
            });

            document.querySelectorAll('.mockup-interactive-text').forEach(el => {
                el.className = `mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md border-slate-200 ${c.text}`;
            });

            document.querySelectorAll('.mockup-btn-accent').forEach(el => {
                el.className = `mockup-btn-accent text-[9px] text-white font-medium px-2.5 py-1 rounded shadow-sm ${c.bg} ${c.hoverBg}`;
            });

            const borderAccent = document.getElementById('mockup-border-accent');
            if (borderAccent) borderAccent.className = `w-full text-left text-[9px] border p-2 rounded-lg text-slate-700 ${c.border} ${c.bgLight}`;

            const textAccent = document.getElementById('mockup-text-accent');
            if (textAccent) textAccent.className = `text-[9px] font-bold ${c.text}`;

            mockupNavItems.forEach(item => {
                const isActive = item.getAttribute('data-target') === currentActiveNav;
                item.className = isActive
                    ? `mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-bold transition-all ${c.bgLight} ${c.text}`
                    : 'mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400 hover:bg-slate-100';
            });
        }

        function updateSegmentedControl() {
            const c = themeStyles[currentTheme];
            const segs = { light: themeLightBtn, dark: themeDarkBtn, system: themeSystemBtn };
            Object.entries(segs).forEach(([mode, btn]) => {
                btn.className = mode === themeMode
                    ? `${SEG_BASE} text-white shadow-sm ${c.bg}`
                    : `${SEG_BASE} text-slate-500 hover:text-slate-700`;
            });
        }

        function updateColorButtons() {
            themeButtons.forEach(btn => {
                const isActive = btn.getAttribute('data-theme') === currentTheme;
                const checkIcon = btn.querySelector('.check-icon');
                if (isActive) {
                    btn.classList.add('ring-2', 'ring-offset-2', 'ring-slate-900', 'ring-offset-white');
                    checkIcon?.classList.remove('hidden');
                } else {
                    btn.classList.remove('ring-2', 'ring-offset-2', 'ring-slate-900', 'ring-offset-white');
                    checkIcon?.classList.add('hidden');
                }
            });
        }

        function render() {
            updateSegmentedControl();
            updateColorButtons();
            applyThemeColorsToMockup();
            safeCreateIcons();
        }

        // ── Event listeners ─────────────────────────────────────────
        themeLightBtn.addEventListener('click', () => {
            themeMode = 'light'; render(); markDirty();
        });
        themeDarkBtn.addEventListener('click', () => {
            // Dark disabled for now
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

        mockupNavItems.forEach(item => {
            item.addEventListener('click', () => {
                switchMockupPage(item.getAttribute('data-target'));
            });
        });

        saveBtn.addEventListener('click', savePreferences);

        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // ── Init ─────────────────────────────────────────────────────
        // Step 1: Pre-select buttons immediately — no Lucide needed
        updateSegmentedControl();
        updateColorButtons();

        // Step 2: Load mockup content and apply colors
        document.getElementById('mockup-dynamic-stage').innerHTML = mockupPages['feed'];
        applyThemeColorsToMockup();

        // Step 3: Lucide icons — try now, retry after short delay
        safeCreateIcons();
        setTimeout(safeCreateIcons, 300);

        // Step 4: Save button starts in normal state
        saveBtn.textContent = 'Save Changes';
        saveBtn.disabled = false;
</script>
@endpush
</x-app-layout>