<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MiraiStudy' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
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
        <a href="{{ route('feed.index') }}" class="font-bold text-lg text-green-600">
            MiraiStudy
        </a>
        <button @click="sidebarOpen = !sidebarOpen"
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path x-show="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                <path x-show="sidebarOpen" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
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
           class="fixed top-0 left-0 z-50 h-full bg-white border-r border-gray-200 flex flex-col transition-all duration-200 ease-in-out lg:translate-x-0">

        {{-- Logo --}}
        <div class="h-16 flex items-center border-b border-gray-100 shrink-0 px-4 gap-2">
            {{-- Logo link — hidden when collapsed --}}
            <a href="{{ route('feed.index') }}"
               x-show="!sidebarCollapsed"
               class="flex items-center gap-2.5 flex-1 min-w-0"
               @click="sidebarOpen = false">
                <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                </div>
                <span class="font-bold text-lg text-gray-900 whitespace-nowrap">MiraiStudy</span>
            </a>

            {{-- Collapse / expand toggle — desktop only --}}
            <button @click="toggleCollapse()"
                    :class="sidebarCollapsed ? 'mx-auto' : 'shrink-0'"
                    class="hidden lg:flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                {{-- Chevron left (collapse) --}}
                <svg x-show="!sidebarCollapsed" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                {{-- Chevron right (expand) --}}
                <svg x-show="sidebarCollapsed" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
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
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('feed.*') || request()->routeIs('posts.*') ? '' : 'text-gray-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span x-show="!sidebarCollapsed">Feed</span>
            </a>

            {{-- Exams --}}
            <a href="{{ route('exams.index') }}"
               @click="sidebarOpen = false"
               title="Exams"
               class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('exams.*') ? 'active' : 'text-gray-600' }}"
               :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('exams.*') ? '' : 'text-gray-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                <span x-show="!sidebarCollapsed">Exams</span>
            </a>

            {{-- Quiz --}}
            <a href="{{ route('quiz.index') }}"
               @click="sidebarOpen = false"
               title="Quiz"
               class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('quiz.*') ? 'active' : 'text-gray-600' }}"
               :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('quiz.*') ? '' : 'text-gray-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span x-show="!sidebarCollapsed">Quiz</span>
            </a>

            {{-- Focus Timer --}}
            <a href="{{ route('timer.index') }}"
               @click="sidebarOpen = false"
               title="Focus"
               class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('timer.*') ? 'active' : 'text-gray-600' }}"
               :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('timer.*') ? '' : 'text-gray-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
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
                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('notifications.*') ? '' : 'text-gray-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span x-show="!sidebarCollapsed">Notifications</span>
                </a>

                {{-- Bookmarks --}}
                <a href="{{ route('bookmarks.index') }}"
                   @click="sidebarOpen = false"
                   title="Bookmarks"
                   class="sidebar-link flex items-center py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('bookmarks.*') ? 'active' : 'text-gray-600' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : 'gap-3 px-3'">
                    <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('bookmarks.*') ? '' : 'text-gray-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                    </svg>
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
                            {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                        </div>
                        <div x-show="!sidebarCollapsed" class="flex-1 min-w-0 text-left">
                            <div class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->display_name }}</div>
                            <div class="text-xs text-gray-400 truncate">{{ '@' . auth()->user()->username }}</div>
                        </div>
                        <svg x-show="!sidebarCollapsed" class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="userMenu ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                        </svg>
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
                         class="absolute bottom-full left-0 right-0 mb-1 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                        <a href="{{ route('profile.show', auth()->user()->username) }}"
                           @click="sidebarOpen = false; userMenu = false"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            My Profile
                        </a>
                        <a href="{{ route('profile.edit') }}"
                           @click="sidebarOpen = false; userMenu = false"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            Settings
                        </a>
                        <hr class="my-1 border-gray-100">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16 17 21 12 16 7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
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
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        <span x-show="!sidebarCollapsed">Login</span>
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex items-center justify-center gap-2 text-sm font-medium bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition-colors"
                       :title="sidebarCollapsed ? 'Sign up' : ''">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/>
                            <line x1="23" y1="11" x2="17" y2="11"/>
                        </svg>
                        <span x-show="!sidebarCollapsed">Sign up</span>
                    </a>
                </div>
            @endauth
        </div>
    </aside>

    {{-- ═══════════════════════════════════════════════
         Main Content Area
    ═══════════════════════════════════════════════ --}}
    <div class="min-h-screen pt-14 lg:pt-0 transition-all duration-200" :class="sidebarCollapsed ? 'lg:ml-16' : 'lg:ml-64'">

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

        {{-- Page Content --}}
        <main class="max-w-6xl mx-auto px-4 py-8">
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
