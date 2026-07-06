@php
    $navItems = [
        ['route' => 'feed.index',  'match' => ['feed.*', 'posts.*'], 'icon' => 'home',      'label' => 'Feed'],
        ['route' => 'exams.index', 'match' => ['exams.*'],           'icon' => 'file-text', 'label' => 'Exams'],
        ['route' => 'quiz.index',  'match' => ['quiz.*'],            'icon' => 'brain',     'label' => 'Quiz'],
        ['route' => 'timer.index', 'match' => ['timer.*'],           'icon' => 'clock',     'label' => 'Focus'],
    ];
@endphp

<nav class="lg:hidden fixed bottom-0 inset-x-0 z-40" style="padding-bottom: env(safe-area-inset-bottom)">
    <div class="flex items-center gap-3 mx-4 mb-3">

        {{-- Pill nav bar: 4 primary items --}}
        <div class="flex flex-1 items-center justify-around rounded-2xl bg-surface/80 backdrop-blur-sm border border-line/70 shadow-lg h-14 px-1">

            @foreach ($navItems as $item)
                @php $active = collect($item['match'])->contains(fn ($p) => request()->routeIs($p)); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl active:scale-95 transition-all duration-150
                          {{ $active ? 'text-accent' : 'text-muted hover:text-content' }}">
                    <span class="flex items-center justify-center w-7 h-7 rounded-lg
                                 {{ $active ? 'bg-accent/10' : '' }}">
                        <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <span class="text-[10px] font-semibold leading-none">{{ $item['label'] }}</span>
                </a>
            @endforeach

        </div>

        {{-- Primary circular action: New Post (auth only) --}}
        @auth
            <a href="{{ route('posts.create') }}"
               class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-accent-from to-accent-to text-white shadow-lg active:scale-95 transition-transform duration-150">
                <i data-lucide="plus" class="h-6 w-6"></i>
            </a>
        @endauth

    </div>
</nav>
