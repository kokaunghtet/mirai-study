<x-app-layout>
    <x-slot name="title">Manage Questions — MiraiStudy</x-slot>

    <div class="px-4">
        <div class="max-w-4xl mx-auto">

            {{-- Header --}}
            <header class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Manage Questions</h1>
                    <p class="mt-1 text-sm text-muted">Add and remove quiz questions.</p>
                </div>
                <a href="{{ route('admin.questions.create') }}"
                   class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-accent px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-accent-strong">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>New question</span>
                </a>
            </header>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 rounded-xl border border-accent/30 bg-accent/10 px-4 py-3 text-sm font-medium text-content">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filter chips --}}
            <div class="mb-5 space-y-2">
                {{-- Category row --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="w-16 text-xs font-semibold text-muted">Category</span>
                    @foreach ($categories as $cat)
                        @php $on = request('category') === $cat->name; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['category' => $on ? null : $cat->name, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors {{ $on ? 'bg-accent text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
                            {{ $cat->name }}
                            <span class="rounded-full px-1.5 {{ $on ? 'bg-white/20' : 'bg-surface-muted text-muted' }}">{{ $counts['category'][$cat->id] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>
                {{-- Level row --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="w-16 text-xs font-semibold text-muted">Level</span>
                    @foreach ($categories as $cat)
                        @foreach ($cat->levels as $lvl)
                            @php $on = request('level') === $lvl->code; @endphp
                            <a href="{{ request()->fullUrlWithQuery(['level' => $on ? null : $lvl->code, 'page' => null]) }}"
                               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors {{ $on ? 'bg-accent text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
                                {{ $lvl->code }}
                                <span class="rounded-full px-1.5 {{ $on ? 'bg-white/20' : 'bg-surface-muted text-muted' }}">{{ $counts['level'][$lvl->id] ?? 0 }}</span>
                            </a>
                        @endforeach
                    @endforeach
                </div>
                {{-- Section row --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="w-16 text-xs font-semibold text-muted">Section</span>
                    @foreach ($sections as $code => $label)
                        @php $on = request('section') === $code; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['section' => $on ? null : $code, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors {{ $on ? 'bg-accent text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
                            {{ $label }}
                            <span class="rounded-full px-1.5 {{ $on ? 'bg-white/20' : 'bg-surface-muted text-muted' }}">{{ $counts['section'][$code] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            @if (request()->hasAny(['category', 'level', 'section']))
                <a href="{{ route('admin.questions') }}" class="mb-4 inline-flex items-center gap-1 text-xs font-medium text-muted hover:text-content">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear filters
                </a>
            @endif

            {{-- Questions --}}
            @forelse ($questions as $question)
                @if ($loop->first)
                    <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                @endif
                    <li class="flex items-center gap-3 px-4 py-3">
                        <i data-lucide="circle-help" class="h-5 w-5 shrink-0 text-accent"></i>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-content">{{ $question->text }}</div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-muted">
                                <span class="rounded-md bg-accent/10 px-1.5 py-0.5 font-medium text-accent">{{ $question->category?->name }}</span>
                                <span>·</span>
                                <span class="rounded-md bg-accent/10 px-1.5 py-0.5 font-medium text-accent">{{ $question->level?->code }}</span>
                                @if ($question->section)
                                    <span>·</span>
                                    <span class="rounded-md bg-surface-muted px-1.5 py-0.5">{{ $question->section }}</span>
                                @endif
                                <span>· Answer: <span class="font-semibold text-content">{{ $question->answer }}</span></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.questions.edit', $question) }}" title="Edit question"
                               class="inline-flex items-center gap-1.5 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                                <i data-lucide="square-pen" class="h-4 w-4"></i>
                                <span class="hidden sm:inline">Edit</span>
                            </a>
                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}"
                                  data-confirm="Delete this question?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete question"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    <span class="hidden sm:inline">Delete</span>
                                </button>
                            </form>
                        </div>
                    </li>
                @if ($loop->last)
                    </ul>
                @endif
            @empty
                <div class="rounded-2xl border border-dashed border-line bg-surface px-4 py-12 text-center">
                    <i data-lucide="circle-help" class="mx-auto h-8 w-8 text-muted"></i>
                    @if (request()->hasAny(['category', 'level', 'section']))
                        <p class="mt-3 text-sm font-medium text-content">No questions match this filter.</p>
                        <p class="mt-1 text-xs text-muted">Try a different combination or clear filters.</p>
                    @else
                        <p class="mt-3 text-sm font-medium text-content">No questions yet</p>
                        <p class="mt-1 text-xs text-muted">Add your first question to the quiz pool.</p>
                    @endif
                </div>
            @endforelse

            @if ($questions->hasPages())
                <div class="mt-4">{{ $questions->links() }}</div>
            @endif

        </div>
    </div>
</x-app-layout>
