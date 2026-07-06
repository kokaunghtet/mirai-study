@php
    $pref = auth()->user()?->preferences;
    $accentColor = $pref->accent_color ?? 'venom';
    $themeMode   = $pref->theme_mode ?? 'light';
    $fillStyle   = $pref->fill_style ?? 'gradient';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="{{ $accentColor }}"
      data-fill="{{ $fillStyle }}"
      class="{{ $themeMode === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Resolve light/dark before first paint to avoid a flash of the wrong theme.
        // A local override (from the sidebar toggle) wins; otherwise use the saved preference.
        (function () {
            var stored = null;
            // Storage can throw when disabled (Safari Lockdown, blocked cookies,
            // zero quota). Guard it so theme resolution below always runs.
            try {
                stored = localStorage.getItem('themeMode');
            } catch (e) { /* storage unavailable — fall back to the server value */ }

            var mode = stored || @json($themeMode);

            // Seed localStorage from the saved server preference on a fresh device
            // so the live "System" listener and the settings page have a value to
            // read. Only when empty — an existing local choice still wins.
            if (!stored) {
                try { localStorage.setItem('themeMode', mode); } catch (e) { /* ignore */ }
            }

            var dark = mode === 'dark' ||
                (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <title>{{ $title ?? 'MiraiStudy' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        /* Sidebar transition */
        .sidebar-link {
            transition: all 0.15s ease;
        }
        .sidebar-link:hover {
            background-color: rgb(var(--surface-muted));
        }
        .sidebar-link.active {
            background-color: rgb(var(--accent) / 0.1);
            color: rgb(var(--accent));
        }
        .sidebar-link.active svg {
            color: rgb(var(--accent));
        }
    </style>
</head>
<body class="bg-canvas text-content min-h-screen"
      x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        toggleCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
        }
      }">

    {{-- ═══════════════════════════════════════════════
         Mobile Top Bar (visible on small screens only)
    ═══════════════════════════════════════════════ --}}
    <div class="lg:hidden fixed top-0 inset-x-0 z-40 bg-surface/60 backdrop-blur-sm border-b border-line/70 h-14 flex items-center gap-3 px-4">
        <a href="{{ route('feed.index') }}" class="font-bold text-lg bg-gradient-to-tr from-accent-from to-accent-to bg-clip-text text-transparent whitespace-nowrap shrink-0">
            MiraiStudy
        </a>

        <form action="{{ route('feed.index') }}" method="GET" class="flex-1 min-w-0">
            <div class="relative">
                <i data-lucide="search" class="absolute left-2 top-1/2 -translate-y-1/2 h-3 w-3 text-muted pointer-events-none"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search..."
                       class="w-full h-7 pl-7 pr-2 rounded-lg bg-surface/80 border border-line/70 text-xs text-content placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-accent/50 transition-colors">
            </div>
        </form>

        @auth
            <a href="{{ route('profile.show', auth()->user()->username) }}"
               class="h-8 w-8 rounded-full overflow-hidden bg-accent/15 flex items-center justify-center text-accent font-bold text-sm shrink-0 active:scale-95 transition-transform">
                @if (auth()->user()->profile_image)
                    <img src="{{ auth()->user()->profile_image }}" alt="{{ auth()->user()->display_name }}" class="h-full w-full object-cover">
                @else
                    {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                @endif
            </a>
        @else
            <a href="{{ route('login') }}" class="text-sm font-semibold text-accent shrink-0">Login</a>
        @endauth
    </div>

    {{-- ═══════════════════════════════════════════════
         Sidebar
    ═══════════════════════════════════════════════ --}}
    <aside :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'w-16' : 'w-64']"
           class="fixed top-0 left-0 z-50 h-full bg-surface/50 border-r border-line/70 flex flex-col transition-[width,transform] duration-200 ease-in-out lg:translate-x-0">

        {{-- Logo --}}
        <div class="h-16 flex items-center border-b border-line shrink-0" :class="sidebarCollapsed ? '' : 'justify-between px-4 gap-2'">
            {{-- Logo link — hidden when collapsed --}}
            <div>
                <a href="{{ route('feed.index') }}" x-show="!sidebarCollapsed" class="flex items-center gap-2.5 flex-1 min-w-0" @click="sidebarOpen = false">
                    <x-application-logo class="w-20 h-20 fill-current text-muted" />
                </a>
            </div>

            {{-- Collapse / expand toggle — desktop only --}}
            <button @click="toggleCollapse()"
                    :class="sidebarCollapsed ? 'w-full h-full p-3' : 'shrink-0 w-7 h-7'"
                    class="hidden lg:flex items-center justify-center rounded-md text-muted hover:bg-surface-muted transition-colors"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                <i data-lucide="chevron-left" x-show="!sidebarCollapsed" class="w-4 h-4"></i>
                
                <div x-show="sidebarCollapsed" class="w-full h-full bg-gradient-to-tr from-accent-from to-accent-to"
                    role="img" aria-label="MiraiStudy Logo"
                    style="
                    -webkit-mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;
                            mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;">
                </div>
            </button>
        </div>

        {{-- Navigation Links --}}
        <nav class="flex-1 overflow-y-auto py-4 space-y-1" :class="sidebarCollapsed ? 'px-2' : 'px-3'">

            {{-- Admin Dashboard --}}
            @auth
                @if (auth()->user()->isAdmin())
                    {{-- Admin Dashboard --}}
                    <div>
                        <a href="{{ route('admin.dashboard') }}"
                           @click="sidebarOpen = false"
                           title="Dashboard"
                           class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'active' : 'text-muted' }}"
                           :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                            <i data-lucide="layout-dashboard" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.dashboard') ? '' : 'text-muted' }}"></i>
                            <span x-show="!sidebarCollapsed">Dashboard</span>
                        </a>
                        <div x-show="!sidebarCollapsed" class="mt-0.5 ml-3 pl-3 border-l border-line">
                            <a href="{{ route('admin.users') }}"
                               @click="sidebarOpen = false"
                               title="Manage Users"
                               class="sidebar-link flex items-center gap-2 py-1.5 px-3 rounded-lg text-xs font-medium {{ request()->routeIs('admin.users*') ? 'active' : 'text-muted' }}">
                                <i data-lucide="users" class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('admin.users*') ? '' : 'text-muted' }}"></i>
                                <span>Users</span>
                            </a>
                            <a href="{{ route('admin.reports') }}"
                               @click="sidebarOpen = false"
                               title="Reports"
                               class="sidebar-link flex items-center gap-2 py-1.5 px-3 rounded-lg text-xs font-medium {{ request()->routeIs('admin.reports*') ? 'active' : 'text-muted' }}">
                                <i data-lucide="flag" class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('admin.reports*') ? '' : 'text-muted' }}"></i>
                                <span>Reports</span>
                            </a>
                            <a href="{{ route('admin.appeals') }}"
                               @click="sidebarOpen = false"
                               title="Appeals"
                               class="sidebar-link flex items-center gap-2 py-1.5 px-3 rounded-lg text-xs font-medium {{ request()->routeIs('admin.appeals*') ? 'active' : 'text-muted' }}">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('admin.appeals*') ? '' : 'text-muted' }}"></i>
                                <span>Appeals</span>
                            </a>
                            <a href="{{ route('admin.analytics') }}"
                               @click="sidebarOpen = false"
                               title="Analytics"
                               class="sidebar-link flex items-center gap-2 py-1.5 px-3 rounded-lg text-xs font-medium {{ request()->routeIs('admin.analytics*') ? 'active' : 'text-muted' }}">
                                <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('admin.analytics*') ? '' : 'text-muted' }}"></i>
                                <span>Analytics</span>
                            </a>
                        </div>
                    </div>
                @endif
            @endauth

            {{-- Moderation (moderators only; admins see this under their Dashboard sub-nav) --}}
            @auth
                @if (auth()->user()->isModerator())
                    <div>
                        <a href="{{ route('admin.reports') }}"
                           @click="sidebarOpen = false"
                           title="Moderation"
                           class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.reports*') ? 'active' : 'text-muted' }}"
                           :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                            <i data-lucide="shield" class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.reports*') ? '' : 'text-muted' }}"></i>
                            <span x-show="!sidebarCollapsed">Moderation</span>
                        </a>
                    </div>
                @endif
            @endauth

            {{-- Feed --}}
            <a href="{{ route('feed.index') }}"
               @click="sidebarOpen = false"
               title="Feed"
               class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('feed.*') || request()->routeIs('posts.*') ? 'active' : 'text-muted' }}"
               :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                <i data-lucide="home" class="w-5 h-5 shrink-0 {{ request()->routeIs('feed.*') || request()->routeIs('posts.*') ? '' : 'text-muted' }}"></i>
                <span x-show="!sidebarCollapsed">Feed</span>
            </a>

            {{-- Exams --}}
            <div>
                <a href="{{ route('exams.index') }}"
                   @click="sidebarOpen = false"
                   title="Exams"
                   class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('exams.*') ? 'active' : 'text-muted' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                    <i data-lucide="file-text" class="w-5 h-5 shrink-0 {{ request()->routeIs('exams.*') ? '' : 'text-muted' }}"></i>
                    <span x-show="!sidebarCollapsed">Exams</span>
                </a>
                @auth
                    @if (auth()->user()->isAdmin() || auth()->user()->isModerator())
                        <div x-show="!sidebarCollapsed" class="mt-0.5 ml-3 pl-3 border-l border-line">
                            <a href="{{ route('admin.papers') }}"
                               @click="sidebarOpen = false"
                               title="Manage Papers"
                               class="sidebar-link flex items-center gap-2 py-1.5 px-3 rounded-lg text-xs font-medium {{ request()->routeIs('admin.papers*') ? 'active' : 'text-muted' }}">
                                <i data-lucide="upload" class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('admin.papers*') ? '' : 'text-muted' }}"></i>
                                <span>Manage Papers</span>
                            </a>
                        </div>
                    @endif
                @endauth
            </div>

            {{-- Quiz --}}
            <div>
                <a href="{{ route('quiz.index') }}"
                   @click="sidebarOpen = false"
                   title="Quiz"
                   class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('quiz.*') ? 'active' : 'text-muted' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                    <i data-lucide="brain" class="w-5 h-5 shrink-0 {{ request()->routeIs('quiz.*') ? '' : 'text-muted' }}"></i>
                    <span x-show="!sidebarCollapsed">Quiz</span>
                </a>
                @auth
                    @if (auth()->user()->isAdmin() || auth()->user()->isModerator())
                        <div x-show="!sidebarCollapsed" class="mt-0.5 ml-3 pl-3 border-l border-line">
                            <a href="{{ route('admin.questions') }}"
                               @click="sidebarOpen = false"
                               title="Manage Questions"
                               class="sidebar-link flex items-center gap-2 py-1.5 px-3 rounded-lg text-xs font-medium {{ request()->routeIs('admin.questions*') ? 'active' : 'text-muted' }}">
                                <i data-lucide="clipboard-list" class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('admin.questions*') ? '' : 'text-muted' }}"></i>
                                <span>Manage Questions</span>
                            </a>
                        </div>
                    @endif
                @endauth
            </div>

            {{-- Focus Timer --}}
            <a href="{{ route('timer.index') }}"
               @click="sidebarOpen = false"
               title="Focus"
               class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('timer.*') ? 'active' : 'text-muted' }}"
               :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                <i data-lucide="clock" class="w-5 h-5 shrink-0 {{ request()->routeIs('timer.*') ? '' : 'text-muted' }}"></i>
                <span x-show="!sidebarCollapsed">Focus</span>
            </a>

            {{-- Divider --}}
            <div class="border-t border-line my-3"></div>

            @auth

                {{-- Notifications --}}
                @php
                    $unreadCount = auth()->user()->appNotifications()->whereNull('read_at')->count();
                @endphp
                <a href="{{ route('notifications.index') }}"
                   @click="sidebarOpen = false"
                   title="Notifications"
                   x-data="notificationBell({ unread: {{ $unreadCount }}, userId: {{ auth()->id() }} })"
                   class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('notifications.*') ? 'active' : 'text-muted' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                    <span class="relative shrink-0">
                        <i data-lucide="bell" class="w-5 h-5 {{ request()->routeIs('notifications.*') ? '' : 'text-muted' }}"></i>
                        <span x-show="unread > 0"
                              x-cloak
                              x-text="unread > 99 ? '99+' : unread"
                              class="absolute -top-1.5 -right-1.5 min-w-[1.1rem] h-[1.1rem] flex items-center justify-center rounded-full bg-red-500 text-white text-[0.6rem] font-bold leading-none px-0.5"></span>
                    </span>
                    <span x-show="!sidebarCollapsed">Notifications</span>
                </a>

                {{-- Bookmarks --}}
                <a href="{{ route('bookmarks.index') }}"
                   @click="sidebarOpen = false"
                   title="Bookmarks"
                   class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('bookmarks.*') ? 'active' : 'text-muted' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                    <i data-lucide="bookmark" class="w-5 h-5 shrink-0 {{ request()->routeIs('bookmarks.*') ? '' : 'text-muted' }}"></i>
                    <span x-show="!sidebarCollapsed">Bookmarks</span>
                </a>

            @endauth
        </nav>

        {{-- User section at bottom --}}
        <div class="shrink-0 border-t border-line p-3 space-y-2">
            {{-- Light / Dark mode toggle (available to everyone, incl. guests) --}}
            <button type="button"
                    x-data="themeToggle({ persistUrl: '{{ auth()->check() ? route('settings.theme-mode') : '' }}' })"
                    @click="toggle()"
                    class="w-full flex items-center rounded-lg text-sm font-medium text-muted hover:bg-surface-muted hover:text-content transition-colors"
                    :class="sidebarCollapsed ? 'justify-center p-2' : 'gap-3 px-3 py-2.5'"
                    :title="dark ? 'Switch to light mode' : 'Switch to dark mode'">
                <i data-lucide="moon" x-show="!dark" class="w-5 h-5 shrink-0"></i>
                <i data-lucide="sun" x-show="dark" class="w-5 h-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" x-text="dark ? 'Light mode' : 'Dark mode'"></span>
            </button>

            @auth
                <div class="relative" x-data="{ userMenu: false }">
                    <button @click="userMenu = !userMenu"
                            class="w-full flex items-center gap-3 rounded-lg hover:bg-surface-muted transition-colors"
                            :class="sidebarCollapsed ? 'justify-center p-2' : 'px-3 py-2.5'"
                            :title="sidebarCollapsed ? '{{ auth()->user()->display_name }}' : ''">
                        <div class="w-8 h-8 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold text-sm shrink-0">
                            @if (auth()->user()->profile_image)
                                <img src="{{ auth()->user()->profile_image }}"
                                    alt="{{ auth()->user()->display_name }}"
                                    loading="lazy"
                                    class="h-full w-full rounded-full object-cover">
                            @else
                                <div class="grid h-full w-full place-items-center rounded-full bg-accent/15 text-sm font-bold text-accent">
                                    {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div x-show="!sidebarCollapsed" class="flex-1 min-w-0 text-left">
                            <div class="text-sm font-semibold text-content truncate">{{ auth()->user()->display_name }}</div>
                            <div class="text-xs text-muted truncate">{{ '@' . auth()->user()->username }}</div>
                        </div>
                        <i data-lucide="chevron-up" x-show="!sidebarCollapsed" class="w-4 h-4 text-muted shrink-0 transition-transform" :class="userMenu ? 'rotate-180' : ''"></i>
                    </button>

                    {{-- Dropdown menu (opens upward) --}}
                    <div x-show="userMenu"
                         @click.outside="userMenu = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         :class="sidebarCollapsed ? 'bottom-0 left-full ml-2 w-56' : 'bottom-full left-0 right-0 mb-1'"
                         class="absolute bg-surface rounded-lg shadow-lg border border-line py-1 z-50">
                        <a href="{{ route('profile.show', auth()->user()->username) }}"
                           @click="sidebarOpen = false; userMenu = false"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-content hover:bg-surface-muted">
                            <i data-lucide="user" class="w-4 h-4 text-muted"></i>
                            My Profile
                        </a>
                        <a href="{{ route('profile.edit') }}"
                           @click="sidebarOpen = false; userMenu = false"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-content hover:bg-surface-muted {{ request()->routeIs('profile.edit') ? 'bg-surface-muted' : '' }}">
                            <i data-lucide="square-pen" class="w-4 h-4 text-muted"></i>
                            Edit Profile
                        </a>
                        <a href="{{ route('settings.index') }}"
                           @click="sidebarOpen = false; userMenu = false"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-content hover:bg-surface-muted {{ request()->routeIs('settings.*') ? 'bg-surface-muted' : '' }}">
                            <i data-lucide="settings" class="w-4 h-4 text-muted"></i>
                            Settings
                        </a>
                        
                        <hr class="my-1 border-line">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="flex flex-col gap-2" :class="sidebarCollapsed ? 'px-0' : 'px-1'">
                    <a href="{{ route('login') }}"
                       class="flex items-center justify-center gap-2 text-sm font-medium text-muted hover:text-accent px-3 py-2 rounded-lg border border-line hover:border-accent/30 transition-colors"
                       :title="sidebarCollapsed ? 'Login' : ''">
                        <i data-lucide="log-in" class="w-4 h-4 shrink-0"></i>
                        <span x-show="!sidebarCollapsed">Login</span>
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex items-center justify-center gap-2 text-sm font-medium bg-gradient-to-tr from-accent-from to-accent-to text-white px-3 py-2 rounded-lg hover:opacity-90 transition-colors"
                       :title="sidebarCollapsed ? 'Sign up' : ''">
                        <i data-lucide="user-plus" class="w-4 h-4 shrink-0"></i>
                        <span x-show="!sidebarCollapsed">Sign up</span>
                    </a>
                </div>
            @endauth
        </div>
    </aside>

    {{-- ═══════════════════════════════════════════════
         Main Content Area
    ═══════════════════════════════════════════════ --}}
    <div class="min-h-screen pt-14 pb-24 lg:pt-0 lg:pb-0 transition-[margin] duration-200 ease-in-out" :class="sidebarCollapsed ? 'lg:ml-16' : 'lg:ml-64'">

        {{-- Flash Messages via Snackbar --}}
        @if (session('success'))
            @push('scripts')
            <script>
                (function(d) { window._snackbarComponent ? window._snackbarComponent.show(d) : window._snackbarQueue.push(d); })({ message: @json(session('success')), type: 'success' });
            </script>
            @endpush
        @endif

        @if (session('error'))
            @push('scripts')
            <script>
                (function(d) { window._snackbarComponent ? window._snackbarComponent.show(d) : window._snackbarQueue.push(d); })({ message: @json(session('error')), type: 'error' });
            </script>
            @endpush
        @endif

        {{-- Account Deletion Warning Banner --}}
        @auth
            @if (auth()->user()->isDeletionScheduled())
                <div class="mx-auto max-w-6xl px-4 mb-4">
                    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 shadow-sm" x-data="{ dismissed: false }" x-show="!dismissed">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                                    <i data-lucide="alert-triangle" class="h-5 w-5 text-amber-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-amber-800">Your account is scheduled for permanent deletion</p>
                                    <p class="mt-0.5 text-xs text-amber-700">
                                        Your account and all data will be permanently deleted on
                                        <strong>{{ auth()->user()->deletionDate()->format('M j, Y') }}</strong>
                                        ({{ auth()->user()->deletionDate()->diffForHumans() }}).
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('profile.restore') }}">
                                    @csrf
                                    <button type="submit"
                                            class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700 transition-colors">
                                        Cancel Deletion
                                    </button>
                                </form>
                                <button type="button" @click="dismissed = true"
                                        class="rounded-lg p-2 text-amber-600 hover:bg-amber-100 transition-colors"
                                        title="Dismiss">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

        {{-- Page Content — widens to use the freed space when the nav
             sidebar is collapsed; animates in step with the nav (200ms). --}}
        <main class="mx-auto px-4 sm:px-6 py-8 transition-[max-width] duration-200 ease-in-out"
              :class="sidebarCollapsed ? 'max-w-7xl' : 'max-w-6xl'">
            {{ $slot }}
        </main>
    </div>

    {{-- Snackbar --}}
    @include('components.snackbar')

    {{-- Confirm Modal --}}
    @include('components.confirm-modal')

    {{-- Report Modal --}}
    @include('components.report-modal')

    {{-- Appeal Modal (shown when banned user attempts a restricted action) --}}
    @include('components.appeal-modal')

    {{-- Auth Modal for guests --}}
    @guest
        @include('components.auth-modal')
    @endguest

    {{-- Mobile Bottom Navigation --}}
    <x-mobile-bottom-nav />

    @stack('scripts')

</body>
</html>
