<x-app-layout>
    <x-slot name="title">Settings — MiraiStudy</x-slot>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <div id="settings-page-root" class="w-full min-h-screen p-6 md:p-10 lg:p-12 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
        <div class="max-w-6xl mx-auto">

            {{-- Page Header (no divider, no actions) --}}
            <header class="mb-8">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Appearance</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Customize how your application interface looks and feels.
                </p>
            </header>

            {{-- Two-Column Layout: Left controls (~60%) · Right preview (~40%) --}}
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 lg:gap-8 items-start">

                {{-- Left Column — Controls --}}
                <div class="lg:col-span-3 space-y-8">

                    {{-- Section: Theme --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-base font-semibold mb-4 text-slate-900 dark:text-slate-100">Theme</h2>

                        <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-800/60">
                            <button id="theme-light" class="seg-btn flex flex-col items-center justify-center gap-1.5 rounded-lg py-2.5 text-xs font-semibold transition-all">
                                <i data-lucide="sun" class="h-5 w-5"></i>
                                <span>Light</span>
                            </button>
                            <button id="theme-dark" class="seg-btn flex flex-col items-center justify-center gap-1.5 rounded-lg py-2.5 text-xs font-semibold transition-all">
                                <i data-lucide="moon" class="h-5 w-5"></i>
                                <span>Dark</span>
                            </button>
                            <button id="theme-system" class="seg-btn flex flex-col items-center justify-center gap-1.5 rounded-lg py-2.5 text-xs font-semibold transition-all">
                                <i data-lucide="settings" class="h-5 w-5"></i>
                                <span class="text-center leading-tight">System (auto)</span>
                            </button>
                        </div>
                    </section>

                    {{-- Section: Primary Color --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-base font-semibold mb-4 text-slate-900 dark:text-slate-100">Primary Color</h2>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-gradient-to-tr from-mirai-lime to-mirai-dark" data-theme="emerald">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Venom</span>
                            </button>

                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-blue-500" data-theme="blue">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Aurora</span>
                            </button>

                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-violet-500" data-theme="violet">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Sangria</span>
                            </button>

                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-rose-500" data-theme="rose">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Twilight</span>
                            </button>

                            <button class="theme-btn relative flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-sm font-semibold text-white shadow-sm transition-all bg-amber-500" data-theme="amber">
                                <i data-lucide="check" class="check-icon h-4 w-4 hidden"></i>
                                <span>Inferno</span>
                            </button>
                        </div>
                    </section>

                </div>

                {{-- Right Column — Live Interface Preview --}}
                <div class="lg:col-span-2">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-300">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">Live Interface Preview</h3>

                        <div id="mockup-container" class="w-full border rounded-xl overflow-hidden shadow-inner flex transition-colors duration-300" style="height: 460px;">

                            {{-- Mini Sidebar --}}
                            <aside id="mockup-sidebar" class="w-2/5 border-r p-2.5 flex flex-col justify-between transition-colors duration-300 shrink-0 select-none">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-1.5 px-1 py-1">
                                        <i data-lucide="book-open" id="mockup-logo-icon" class="h-4 w-4 transition-colors"></i>
                                        <span class="text-[11px] font-bold tracking-tight text-slate-800 dark:text-slate-200">MiraiStudy</span>
                                    </div>
                                    <div id="mockup-nav-list" class="space-y-0.5 pt-1">
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all" data-target="feed">
                                            <i data-lucide="home" class="h-3.5 w-3.5"></i>
                                            <span>Feed</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="exams">
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                            <span>Exams</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="quiz">
                                            <i data-lucide="help-circle" class="h-3.5 w-3.5"></i>
                                            <span>Quiz</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="focus">
                                            <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                            <span>Focus</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="notifications">
                                            <i data-lucide="bell" class="h-3.5 w-3.5"></i>
                                            <span>Notifications</span>
                                        </button>
                                        <button class="mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400" data-target="bookmarks">
                                            <i data-lucide="bookmark" class="h-3.5 w-3.5"></i>
                                            <span>Bookmarks</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-5 w-5 rounded-full bg-slate-300 dark:bg-slate-700 flex items-center justify-center text-[8px] font-bold text-slate-600 dark:text-slate-300">T</div>
                                        <div class="leading-none text-left">
                                            <div class="text-[9px] font-bold text-slate-700 dark:text-slate-300">Test User</div>
                                            <div class="text-[8px] text-slate-400">@testuser</div>
                                        </div>
                                    </div>
                                    <i data-lucide="chevron-up" class="h-2.5 w-2.5 text-slate-400"></i>
                                </div>
                            </aside>

                            {{-- Mini Main Content --}}
                            <main class="flex-1 flex bg-slate-50 dark:bg-slate-950 p-3 overflow-hidden transition-colors duration-300">
                                <div id="mockup-dynamic-stage" class="flex-1 text-left space-y-2.5 overflow-y-auto no-scrollbar">
                                    {{-- Populated dynamically by switchMockupPage() --}}
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
            // Theme mode: 'light' | 'dark' | 'system'. Absence of stored key = system.
            let themeMode = localStorage.getItem('theme');
            if (themeMode !== 'dark' && themeMode !== 'light') themeMode = 'system';

            let isDarkMode = false; // resolved from themeMode in updateModeUI()

            let currentTheme = localStorage.getItem('color-theme') || 'emerald';
            let currentActiveNav = 'feed';

            const themeStyles = {
                emerald: { bg: 'bg-emerald-500', text: 'text-emerald-500', border: 'border-emerald-500', hoverBg: 'hover:bg-emerald-600', fill: 'bg-emerald-500', bgLight: 'bg-emerald-50', bgDarkActive: 'bg-emerald-950/40', badgeBg: 'bg-emerald-500/10' },
                blue: { bg: 'bg-blue-500', text: 'text-blue-500', border: 'border-blue-500', hoverBg: 'hover:bg-blue-600', fill: 'bg-blue-500', bgLight: 'bg-blue-50', bgDarkActive: 'bg-blue-950/40', badgeBg: 'bg-blue-500/10' },
                violet: { bg: 'bg-violet-500', text: 'text-violet-500', border: 'border-violet-500', hoverBg: 'hover:bg-violet-600', fill: 'bg-violet-500', bgLight: 'bg-violet-50', bgDarkActive: 'bg-violet-950/40', badgeBg: 'bg-violet-500/10' },
                rose: { bg: 'bg-rose-500', text: 'text-rose-500', border: 'border-rose-500', hoverBg: 'hover:bg-rose-600', fill: 'bg-rose-500', bgLight: 'bg-rose-50', bgDarkActive: 'bg-rose-950/40', badgeBg: 'bg-rose-500/10' },
                amber: { bg: 'bg-amber-500', text: 'text-amber-500', border: 'border-amber-500', hoverBg: 'hover:bg-amber-600', fill: 'bg-amber-500', bgLight: 'bg-amber-50', bgDarkActive: 'bg-amber-950/40', badgeBg: 'bg-amber-500/10' }
            };

            // Mini main-content views (one per nav item)
            const mockupPages = {
                feed: `
                    <button id="mockup-create-btn" class="w-full text-[10px] py-2 text-white font-medium rounded-lg transition-all shadow-sm flex items-center justify-center gap-1 mb-2">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i> Create Post
                    </button>

                    <div class="mockup-card border rounded-xl p-3 space-y-2 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white">A</div>
                                <div>
                                    <div class="text-[9px] font-bold text-slate-800 dark:text-slate-200">Admin</div>
                                    <div class="text-[8px] text-slate-400">1 minute ago</div>
                                </div>
                            </div>
                            <button class="mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md transition-all border-slate-200 dark:border-slate-700">Follow</button>
                        </div>
                        <p class="text-[10px] font-bold text-slate-800 dark:text-slate-100 leading-tight">Temporibus provident et dolorem voluptatibus perferendis maiores.</p>
                        <p class="text-[9px] text-slate-500 dark:text-slate-400 leading-normal">Dolorem accusantium quis labore commodi magni est. Qui recusandae adipisci tenetur qui aspernatur.</p>
                        <div class="flex gap-1 flex-wrap pt-0.5">
                            <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">Notes</span>
                        </div>
                        <div class="pt-1 flex items-center justify-between border-t border-slate-100 dark:border-slate-800/60 text-slate-400">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-1 text-[8px]"><i data-lucide="thumbs-up" class="h-2.5 w-2.5"></i> 3</span>
                                <span class="flex items-center gap-1 text-[8px]"><i data-lucide="message-square" class="h-2.5 w-2.5"></i> 8</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="bookmark" class="h-2.5 w-2.5"></i>
                                <i data-lucide="send" class="h-2.5 w-2.5"></i>
                            </div>
                        </div>
                    </div>

                    <div class="mockup-card border rounded-xl p-3 space-y-2 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <div class="mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white">A</div>
                                <div>
                                    <div class="text-[9px] font-bold text-slate-800 dark:text-slate-200">Admin</div>
                                    <div class="text-[8px] text-slate-400">1 minute ago</div>
                                </div>
                            </div>
                            <button class="mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md transition-all border-slate-200 dark:border-slate-700">Follow</button>
                        </div>
                        <p class="text-[10px] font-bold text-slate-800 dark:text-slate-100 leading-tight">Eum ea facilis nisi distinctio tempora commodi tempore.</p>
                        <p class="text-[9px] text-slate-500 dark:text-slate-400 leading-normal">Velit eos voluptatem qui. Dolores perferendis cupiditate fugiat delectus et ullam ratione.</p>
                        <div class="flex gap-1 flex-wrap pt-0.5">
                            <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">JLPT</span>
                            <span class="mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded">Notes</span>
                        </div>
                        <div class="pt-1 flex items-center justify-between border-t border-slate-100 dark:border-slate-800/60 text-slate-400">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-1 text-[8px]"><i data-lucide="thumbs-up" class="h-2.5 w-2.5"></i> 5</span>
                                <span class="flex items-center gap-1 text-[8px]"><i data-lucide="message-square" class="h-2.5 w-2.5"></i> 8</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="bookmark" class="h-2.5 w-2.5"></i>
                                <i data-lucide="send" class="h-2.5 w-2.5"></i>
                            </div>
                        </div>
                    </div>
                `,
                exams: `
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 space-y-2.5 shadow-sm">
                        <h3 class="text-[11px] font-bold text-slate-800 dark:text-slate-200">Available Exam Papers</h3>
                        <div class="space-y-2">
                            <div class="border border-slate-100 dark:border-slate-800 p-2 rounded-lg flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/30">
                                <div>
                                    <div class="text-[10px] font-bold text-slate-800 dark:text-slate-200">JLPT N2 Mock Exam</div>
                                    <div class="text-[8px] text-slate-400">140 Questions · 110 mins</div>
                                </div>
                                <button class="mockup-btn-accent text-[9px] text-white font-medium px-2.5 py-1 rounded shadow-sm">Start</button>
                            </div>
                        </div>
                    </div>
                `,
                quiz: `
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 space-y-3 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400">Quick Quiz</span>
                            <span id="mockup-text-accent" class="text-[9px] font-bold">Question 3 of 5</span>
                        </div>
                        <p class="text-[10px] font-semibold text-slate-800 dark:text-slate-200">Which describes the correct use of grammatical rules?</p>
                        <div class="space-y-1.5">
                            <button class="w-full text-left text-[9px] border border-slate-200 dark:border-slate-700 p-2 rounded-lg text-slate-700 dark:text-slate-300">A. Option representation answer variant.</button>
                            <button id="mockup-border-accent" class="w-full text-left text-[9px] border p-2 rounded-lg bg-opacity-5 transition-colors text-slate-700 dark:text-slate-300">B. Indicates an exclusive exception status.</button>
                        </div>
                    </div>
                `,
                focus: `
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-center space-y-3 shadow-sm">
                        <div class="text-3xl font-bold font-mono tracking-tight text-slate-800 dark:text-slate-100">24:59</div>
                        <div class="flex items-center justify-center gap-2 pt-1">
                            <button class="mockup-btn-accent text-[9px] text-white font-medium px-3 py-1 rounded-md shadow-sm">Pause</button>
                        </div>
                    </div>
                `,
                notifications: `
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 space-y-2 shadow-sm">
                        <p class="text-[9px] text-slate-400">No new alerts received.</p>
                    </div>
                `,
                bookmarks: `
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 space-y-2 shadow-sm">
                        <p class="text-[9px] text-slate-400">Saved items container view module.</p>
                    </div>
                `
            };

            // DOM Elements
            const html = document.documentElement;
            const themeLightBtn = document.getElementById('theme-light');
            const themeDarkBtn = document.getElementById('theme-dark');
            const themeSystemBtn = document.getElementById('theme-system');
            const themeButtons = document.querySelectorAll('.theme-btn');

            const mockupContainer = document.getElementById('mockup-container');
            const mockupSidebar = document.getElementById('mockup-sidebar');
            const mockupLogoIcon = document.getElementById('mockup-logo-icon');
            const mockupDynamicStage = document.getElementById('mockup-dynamic-stage');
            const mockupNavItems = document.querySelectorAll('.mockup-nav-item');

            const SEG_BASE = "seg-btn flex flex-col items-center justify-center gap-1.5 rounded-lg py-2.5 text-xs font-semibold transition-all";
            const systemMedia = window.matchMedia('(prefers-color-scheme: dark)');

            function resolveDark() {
                if (themeMode === 'dark') return true;
                if (themeMode === 'light') return false;
                return systemMedia.matches;
            }

            function switchMockupPage(targetPage) {
                currentActiveNav = targetPage;
                mockupDynamicStage.innerHTML = mockupPages[targetPage];
                applyThemeColorsToMockup();
                lucide.createIcons();
            }

            function updateModeUI() {
                const activeColor = themeStyles[currentTheme];
                isDarkMode = resolveDark();

                // Persist the chosen mode (system = no stored key, follow OS).
                if (themeMode === 'system') {
                    localStorage.removeItem('theme');
                } else {
                    localStorage.setItem('theme', themeMode);
                }

                if (isDarkMode) {
                    html.classList.add('dark');
                    mockupContainer.className = "w-full border rounded-xl overflow-hidden shadow-inner flex transition-colors duration-300 bg-slate-950 border-slate-800";
                    mockupSidebar.className = "w-2/5 border-r p-2.5 flex flex-col justify-between transition-colors duration-300 bg-slate-900 border-slate-800 shrink-0 select-none";
                } else {
                    html.classList.remove('dark');
                    mockupContainer.className = "w-full border rounded-xl overflow-hidden shadow-inner flex transition-colors duration-300 bg-slate-50 border-slate-200";
                    mockupSidebar.className = "w-2/5 border-r p-2.5 flex flex-col justify-between transition-colors duration-300 bg-white border-slate-200 shrink-0 select-none";
                }

                // Segmented theme control — selected segment is filled with the accent color.
                const segs = { light: themeLightBtn, dark: themeDarkBtn, system: themeSystemBtn };
                Object.entries(segs).forEach(([mode, btn]) => {
                    if (mode === themeMode) {
                        btn.className = `${SEG_BASE} text-white shadow-sm ${activeColor.bg}`;
                    } else {
                        btn.className = `${SEG_BASE} text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200`;
                    }
                });

                mockupNavItems.forEach(item => {
                    const target = item.getAttribute('data-target');
                    if (target === currentActiveNav) {
                        item.className = `mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-bold transition-all ${isDarkMode ? activeColor.bgDarkActive + ' ' + activeColor.text : activeColor.bgLight + ' ' + activeColor.text}`;
                    } else {
                        item.className = "mockup-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-medium transition-all text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800/50";
                    }
                });
            }

            function applyThemeColorsToMockup() {
                const activeColor = themeStyles[currentTheme];

                mockupLogoIcon.className = `h-4 w-4 transition-colors ${activeColor.text}`;

                const createBtn = document.getElementById('mockup-create-btn');
                if (createBtn) createBtn.className = `w-full text-[10px] py-2 text-white font-medium rounded-lg transition-all shadow-sm flex items-center justify-center gap-1 ${activeColor.bg} ${activeColor.hoverBg}`;

                document.querySelectorAll('.mockup-avatar-badge').forEach(el => {
                    el.className = `mockup-avatar-badge h-5 w-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white ${activeColor.bg}`;
                });

                document.querySelectorAll('.mockup-tag').forEach(el => {
                    el.className = `mockup-tag text-[8px] font-semibold px-1.5 py-0.5 rounded ${activeColor.text} ${activeColor.badgeBg}`;
                });

                document.querySelectorAll('.mockup-interactive-text').forEach(el => {
                    el.className = `mockup-interactive-text text-[8px] font-semibold border px-2 py-0.5 rounded-md transition-all border-slate-200 dark:border-slate-700 ${activeColor.text}`;
                });

                document.querySelectorAll('.mockup-btn-accent').forEach(el => {
                    el.className = `mockup-btn-accent text-[9px] text-white font-medium px-2.5 py-1 rounded shadow-sm ${activeColor.bg} ${activeColor.hoverBg}`;
                });

                const borderAccent = document.getElementById('mockup-border-accent');
                if (borderAccent) borderAccent.className = `w-full text-left text-[9px] border p-2 rounded-lg bg-opacity-5 transition-colors ${activeColor.border} ${isDarkMode ? activeColor.bgDarkActive : activeColor.bgLight}`;

                const textAccent = document.getElementById('mockup-text-accent');
                if (textAccent) textAccent.className = `text-[9px] font-bold ${activeColor.text}`;
            }

            function updateThemeUI() {
                localStorage.setItem('color-theme', currentTheme);

                themeButtons.forEach(btn => {
                    const themeName = btn.getAttribute('data-theme');
                    const checkIcon = btn.querySelector('.check-icon');

                    if (themeName === currentTheme) {
                        btn.classList.add('ring-2', 'ring-offset-2', 'ring-slate-900', 'ring-offset-white', 'dark:ring-white', 'dark:ring-offset-slate-900');
                        if (checkIcon) checkIcon.classList.remove('hidden');
                    } else {
                        btn.classList.remove('ring-2', 'ring-offset-2', 'ring-slate-900', 'ring-offset-white', 'dark:ring-white', 'dark:ring-offset-slate-900');
                        if (checkIcon) checkIcon.classList.add('hidden');
                    }
                });

                updateModeUI();
                applyThemeColorsToMockup();
                lucide.createIcons();
            }

            // Theme segment selection (mutually exclusive)
            themeLightBtn.addEventListener('click', () => { themeMode = 'light'; updateModeUI(); });
            themeDarkBtn.addEventListener('click', () => { themeMode = 'dark'; updateModeUI(); });
            themeSystemBtn.addEventListener('click', () => { themeMode = 'system'; updateModeUI(); });

            // Live-follow OS preference while in System mode
            systemMedia.addEventListener('change', () => {
                if (themeMode === 'system') updateThemeUI();
            });

            themeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    currentTheme = button.getAttribute('data-theme');
                    updateThemeUI();
                });
            });

            mockupNavItems.forEach(item => {
                item.addEventListener('click', () => {
                    switchMockupPage(item.getAttribute('data-target'));
                    updateModeUI();
                });
            });

            // Init views
            switchMockupPage('feed');
            updateThemeUI();
        </script>
    @endpush
</x-app-layout>
