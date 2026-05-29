<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MiraiStudy' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">

    {{-- Navigation --}}
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="{{ route('feed.index') }}" class="font-bold text-xl text-green-600">
                MiraiStudy
            </a>

            {{-- Nav Links --}}
            <div class="flex items-center gap-6 text-sm font-medium text-gray-600">
                <a href="{{ route('feed.index') }}"
                   class="{{ request()->routeIs('feed.*') ? 'text-green-600' : 'hover:text-green-600' }}">
                    Feed
                </a>
                <a href="{{ route('exams.index') }}"
                   class="{{ request()->routeIs('exams.*') ? 'text-green-600' : 'hover:text-green-600' }}">
                    Exams
                </a>
                <a href="{{ route('quiz.index') }}"
                   class="{{ request()->routeIs('quiz.*') ? 'text-green-600' : 'hover:text-green-600' }}">
                    Quiz
                </a>
                <a href="{{ route('timer.index') }}"
                   class="{{ request()->routeIs('timer.*') ? 'text-green-600' : 'hover:text-green-600' }}">
                    Focus
                </a>
            </div>

            {{-- Auth buttons --}}
            <div class="flex items-center gap-3">
                @auth
                    {{-- Notifications --}}
                    <a href="{{ route('notifications.index') }}" class="relative text-gray-500 hover:text-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </a>

                    {{-- User dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-green-600">
                            {{ auth()->user()->display_name }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @click.outside="open = false"
                             class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                            <a href="{{ route('profile.show', auth()->user()->username) }}"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                My Profile
                            </a>
                            <a href="{{ route('bookmarks.index') }}"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Bookmarks
                            </a>
                            <a href="{{ route('profile.edit') }}"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Settings
                            </a>
                            <hr class="my-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}"
                       class="text-sm font-medium text-gray-600 hover:text-green-600">
                        Login
                    </a>
                    <a href="{{ route('register') }}"
                       class="text-sm font-medium bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        Sign up
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="max-w-6xl mx-auto px-4 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-6xl mx-auto px-4 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Page Content --}}
    <main class="max-w-6xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    {{-- Auth Modal for guests --}}
    @guest
        @include('components.auth-modal')
    @endguest

    {{-- Alpine.js (already included via Breeze) --}}
</body>
</html>