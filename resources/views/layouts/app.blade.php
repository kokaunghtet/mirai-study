<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MiraiStudy' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        /* Sidebar transition */
        .sidebar-link {
            transition: all 0.15s ease;
        }
        .sidebar-link:hover {
            background-color: rgb(243 244 246);
        }
        .sidebar-link.active {
            background-color: rgb(240 253 244);
            color: rgb(22 163 74);
        }
        .sidebar-link.active svg {
            color: rgb(22 163 74);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen"
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
    <div class="lg:hidden fixed top-0 inset-x-0 z-40 bg-white border-b border-gray-200 h-14 flex items-center justify-between px-4">
        <a href="{{ route('feed.index') }}" class="font-bold text-lg bg-gradient-to-tr from-mirai-lime to-mirai-dark bg-clip-text text-transparent whitespace-nowrap">
            MiraiStudy
        </a>
        <button @click="sidebarOpen = !sidebarOpen"
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
            <i data-lucide="menu" x-show="!sidebarOpen" class="w-6 h-6"></i>
            <i data-lucide="x" x-show="sidebarOpen" class="w-6 h-6"></i>
        </button>
    </div>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="lg:hidden fixed inset-0 z-40 bg-black/40"></div>

    {{-- ═══════════════════════════════════════════════
         Sidebar
    ═══════════════════════════════════════════════ --}}
    <aside :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'w-16' : 'w-64']"
           class="fixed top-0 left-0 z-50 h-full bg-white border-r border-gray-200 flex flex-col transition-[width,transform] duration-200 ease-in-out lg:translate-x-0">

        {{-- Logo --}}
        <div class="h-16 flex justify-between items-center border-b border-gray-100 shrink-0 px-4 gap-2">
            {{-- Logo link — hidden when collapsed --}}
            <div>
                <a href="{{ route('feed.index') }}" x-show="!sidebarCollapsed" class="flex items-center gap-2.5 flex-1 min-w-0" @click="sidebarOpen = false">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            {{-- Collapse / expand toggle — desktop only --}}
            <button @click="toggleCollapse()"
                    :class="sidebarCollapsed ? 'mx-auto' : 'shrink-0'"
                    class="hidden lg:flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                {{-- Chevron left (collapse) --}}
                <i data-lucide="chevron-left" x-show="!sidebarCollapsed" class="w-4 h-4"></i>
                {{-- Chevron right (expand) --}}
                <i data-lucide="chevron-right" x-show="sidebarCollapsed" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Navigation Links --}}
        <nav class="flex-1 overflow-y-auto py-4 space-y-1" :class="sidebarCollapsed ? 'px-2' : 'px-3'">

            {{-- Feed --}}
            <a href="{{ route('feed.index') }}"
               @click="sidebarOpen = false"
               title="Feed"
               class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('feed.*') || request()->routeIs('posts.*') ? 'active' : 'text-gray-600' }}"
               :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                <i data-lucide="home" class="w-5 h-5 shrink-0 {{ request()->routeIs('feed.*') || request()->routeIs('posts.*') ? '' : 'text-gray-400' }}"></i>
                <span x-show="!sidebarCollapsed">Feed</span>
            </a>

            {{-- Exams --}}
            <a href="{{ route('exams.index') }}"
               @click="sidebarOpen = false"
               title="Exams"
               class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('exams.*') ? 'active' : 'text-gray-600' }}"
               :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                <i data-lucide="file-text" class="w-5 h-5 shrink-0 {{ request()->routeIs('exams.*') ? '' : 'text-gray-400' }}"></i>
                <span x-show="!sidebarCollapsed">Exams</span>
            </a>

            {{-- Quiz --}}
            <a href="{{ route('quiz.index') }}"
               @click="sidebarOpen = false"
               title="Quiz"
               class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('quiz.*') ? 'active' : 'text-gray-600' }}"
               :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                <i data-lucide="circle-help" class="w-5 h-5 shrink-0 {{ request()->routeIs('quiz.*') ? '' : 'text-gray-400' }}"></i>
                <span x-show="!sidebarCollapsed">Quiz</span>
            </a>

            {{-- Focus Timer --}}
            <a href="{{ route('timer.index') }}"
               @click="sidebarOpen = false"
               title="Focus"
               class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('timer.*') ? 'active' : 'text-gray-600' }}"
               :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                <i data-lucide="clock" class="w-5 h-5 shrink-0 {{ request()->routeIs('timer.*') ? '' : 'text-gray-400' }}"></i>
                <span x-show="!sidebarCollapsed">Focus</span>
            </a>

            {{-- Divider --}}
            <div class="border-t border-gray-100 my-3"></div>

            @auth
                {{-- Notifications --}}
                <a href="{{ route('notifications.index') }}"
                   @click="sidebarOpen = false"
                   title="Notifications"
                   class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('notifications.*') ? 'active' : 'text-gray-600' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                    <i data-lucide="bell" class="w-5 h-5 shrink-0 {{ request()->routeIs('notifications.*') ? '' : 'text-gray-400' }}"></i>
                    <span x-show="!sidebarCollapsed">Notifications</span>
                </a>

                {{-- Bookmarks --}}
                <a href="{{ route('bookmarks.index') }}"
                   @click="sidebarOpen = false"
                   title="Bookmarks"
                   class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('bookmarks.*') ? 'active' : 'text-gray-600' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                    <i data-lucide="bookmark" class="w-5 h-5 shrink-0 {{ request()->routeIs('bookmarks.*') ? '' : 'text-gray-400' }}"></i>
                    <span x-show="!sidebarCollapsed">Bookmarks</span>
                </a>
            @endauth
        </nav>

        {{-- User section at bottom --}}
        <div class="shrink-0 border-t border-gray-100 p-3">
            @auth
                <div class="relative" x-data="{ userMenu: false }">
                    <button @click="userMenu = !userMenu"
                            class="w-full flex items-center gap-3 rounded-lg hover:bg-gray-50 transition-colors"
                            :class="sidebarCollapsed ? 'justify-center p-2' : 'px-3 py-2.5'"
                            :title="sidebarCollapsed ? '{{ auth()->user()->display_name }}' : ''">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-sm shrink-0">
                            @if (auth()->user()->profile_image)
                                <img src="{{ auth()->user()->profile_image }}"
                                    alt="{{ auth()->user()->display_name }}"
                                    loading="lazy"
                                    class="h-full w-full rounded-full object-cover">
                            @else
                                <div class="grid h-full w-full place-items-center rounded-full bg-green-100 text-sm font-bold text-green-600">
                                    {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div x-show="!sidebarCollapsed" class="flex-1 min-w-0 text-left">
                            <div class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->display_name }}</div>
                            <div class="text-xs text-gray-400 truncate">{{ '@' . auth()->user()->username }}</div>
                        </div>
                        <i data-lucide="chevron-up" x-show="!sidebarCollapsed" class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="userMenu ? 'rotate-180' : ''"></i>
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
                         class="absolute bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                        <a href="{{ route('profile.show', auth()->user()->username) }}"
                           @click="sidebarOpen = false; userMenu = false"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                            My Profile
                        </a>
                        <a href="{{ route('profile.edit') }}"
                           @click="sidebarOpen = false; userMenu = false"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('profile.edit') ? 'bg-gray-50' : '' }}">
                            <i data-lucide="square-pen" class="w-4 h-4 text-gray-400"></i>
                            Edit Profile
                        </a>
                        <a href="{{ route('settings.index') }}"
                           @click="sidebarOpen = false; userMenu = false"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('settings.*') ? 'bg-gray-50' : '' }}">
                            <i data-lucide="settings" class="w-4 h-4 text-gray-400"></i>
                            Settings
                        </a>
                        
                        <hr class="my-1 border-gray-100">
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
                       class="flex items-center justify-center gap-2 text-sm font-medium text-gray-600 hover:text-green-600 px-3 py-2 rounded-lg border border-gray-200 hover:border-green-200 transition-colors"
                       :title="sidebarCollapsed ? 'Login' : ''">
                        <i data-lucide="log-in" class="w-4 h-4 shrink-0"></i>
                        <span x-show="!sidebarCollapsed">Login</span>
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex items-center justify-center gap-2 text-sm font-medium bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition-colors"
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
    <div class="min-h-screen pt-14 lg:pt-0 transition-[margin] duration-200 ease-in-out" :class="sidebarCollapsed ? 'lg:ml-16' : 'lg:ml-64'">

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="max-w-6xl mx-auto px-4 pt-6 lg:pt-6">
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="max-w-6xl mx-auto px-4 pt-4">
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        {{-- Page Content — widens to use the freed space when the nav
             sidebar is collapsed; animates in step with the nav (200ms). --}}
        <main class="mx-auto py-8 transition-[max-width] duration-200 ease-in-out"
              :class="sidebarCollapsed ? 'max-w-7xl' : 'max-w-6xl'">
            {{ $slot }}
        </main>
    </div>

    {{-- Auth Modal for guests --}}
    @guest
        @include('components.auth-modal')
    @endguest

    @stack('scripts')

</body>
</html>
