<x-app-layout>
    <x-slot name="title">Quiz — MiraiStudy</x-slot>

    <div class="px-4" x-data="quizSetup(@js($catalog), @js($counts))" x-cloak>
        <div class="max-w-3xl mx-auto">

            {{-- Header --}}
            <header class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight text-content">Quiz</h1>
                <p class="mt-1 text-sm text-muted">
                    Pick a track, choose what to practice, and how many questions to answer.
                </p>
            </header>

            {{-- ── Resume in-progress quiz ─────────────────────────────── --}}
            @if ($activeAttempt)
                <div x-data="resumeBanner({{ $activeAttempt->id }}, {{ $activeAttempt->total_questions }})"
                     class="mb-6 flex items-center justify-between gap-3 rounded-2xl border border-accent/30 bg-accent/10 p-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-sm font-semibold text-content">
                            <i data-lucide="clock" class="h-4 w-4 shrink-0 text-accent"></i>
                            <span class="truncate">Quiz in progress — {{ $activeAttempt->heading() }}</span>
                        </div>
                        <div class="mt-0.5 pl-6 text-xs text-muted" x-show="answered > 0"
                             x-text="`${answered} of ${total} answered`"></div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <form method="POST" action="{{ route('quiz.abort', $activeAttempt) }}"
                              @submit="abort($event)" data-loading>
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Discard quiz"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                <span class="hidden sm:inline">Discard</span>
                            </button>
                        </form>
                        <a href="{{ route('quiz.show', $activeAttempt) }}"
                           class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-tr from-accent-from to-accent-to px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-accent-strong">
                            Resume <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </a>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('quiz.start') }}" data-loading class="space-y-6">
                @csrf
                <input type="hidden" name="category" :value="category">
                <input type="hidden" name="level" :value="level">
                <input type="hidden" name="section" :value="section">
                <input type="hidden" name="count" :value="count">

                {{-- ── Step 1: Category ───────────────────────────────── --}}
                <section class="rounded-2xl border border-line bg-surface p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-accent/10 text-[11px] font-bold text-accent">1</span>
                        <h2 class="text-base font-semibold text-content">Choose a track</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="(def, key) in catalog" :key="key">
                            <button type="button" @click="selectCategory(key)"
                                    class="flex items-start gap-3 rounded-xl border p-4 text-left transition-all"
                                    :class="category === key
                                        ? 'border-accent bg-accent/10 ring-1 ring-accent'
                                        : 'border-line bg-surface hover:border-accent/40 hover:bg-surface-muted'">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg"
                                      :class="category === key ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'bg-surface-muted text-muted'">
                                    <i :data-lucide="key === 'JLPT' ? 'languages' : 'cpu'" class="h-5 w-5"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-content" x-text="def.label"></span>
                                    <span class="block text-xs text-muted" x-text="def.blurb"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </section>

                {{-- ── Step 2: Level ──────────────────────────────────── --}}
                <section x-show="category" x-transition class="rounded-2xl border border-line bg-surface p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-accent/10 text-[11px] font-bold text-accent">2</span>
                        <h2 class="text-base font-semibold text-content">
                            <span x-text="category === 'JLPT' ? 'Choose a level' : 'Choose an exam'"></span>
                        </h2>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <template x-for="(lvl, key) in levels" :key="key">
                            <button type="button" @click="selectLevel(key)"
                                    class="rounded-xl border px-4 py-2.5 text-sm font-semibold transition-all"
                                    :class="level === key
                                        ? 'border-accent bg-accent/10 text-accent ring-1 ring-accent'
                                        : 'border-line bg-surface text-content hover:border-accent/40 hover:bg-surface-muted'">
                                <span x-text="lvl.label"></span>
                            </button>
                        </template>
                    </div>
                </section>

                {{-- ── Step 3: Section (only when the level has sub-sections) ── --}}
                <section x-show="level && needsSection" x-transition class="rounded-2xl border border-line bg-surface p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-accent/10 text-[11px] font-bold text-accent">3</span>
                        <h2 class="text-base font-semibold text-content">
                            <span x-text="category === 'JLPT' ? 'Choose a subject' : 'Choose a field'"></span>
                        </h2>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <template x-for="(label, key) in sections" :key="key">
                            <button type="button" @click="selectSection(key)"
                                    class="rounded-xl border px-4 py-2.5 text-sm font-semibold transition-all"
                                    :class="section === key
                                        ? 'border-accent bg-accent/10 text-accent ring-1 ring-accent'
                                        : 'border-line bg-surface text-content hover:border-accent/40 hover:bg-surface-muted'">
                                <span x-text="label"></span>
                            </button>
                        </template>
                    </div>
                </section>

                {{-- ── Step 4: Count ──────────────────────────────────── --}}
                <section x-show="level && (!needsSection || section)" x-transition class="rounded-2xl border border-line bg-surface p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-accent/10 text-[11px] font-bold text-accent"
                              x-text="needsSection ? '4' : '3'"></span>
                        <h2 class="text-base font-semibold text-content">How many questions?</h2>
                    </div>

                    <div class="grid grid-cols-4 gap-1 rounded-xl bg-surface-muted p-1">
                        <template x-for="n in counts" :key="n">
                            <button type="button" @click="count = n"
                                    class="flex items-center justify-center gap-1.5 rounded-lg py-2.5 text-sm font-semibold transition-all"
                                    :class="count === n ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white shadow-sm' : 'text-muted hover:text-content'">
                                <span x-text="n"></span>
                                <span class="text-xs font-normal opacity-80">Q</span>
                            </button>
                        </template>
                    </div>
                </section>

                @error('section')
                    <p class="text-center text-xs font-semibold text-red-500">{{ $message }}</p>
                @enderror

                {{-- Start --}}
                <button type="submit" :disabled="!canStart"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-tr from-accent-from to-accent-to px-5 py-3.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-accent-strong disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-text="canStart ? `Start ${count}-question quiz` : 'Complete the steps above'"></span>
                    <i data-lucide="arrow-right" class="h-4 w-4" x-show="canStart"></i>
                </button>
            </form>

            {{-- ── Recent results ─────────────────────────────────────── --}}
            @if ($recent->isNotEmpty())
                <section class="mt-8">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-content">Recent results</h2>
                        @if ($completedCount > 3)
                            <a href="{{ route('quiz.history') }}" class="text-xs font-semibold text-accent hover:underline">
                                View all ({{ $completedCount }})
                            </a>
                        @endif
                    </div>
                    <div class="space-y-2">
                        @foreach ($recent as $attempt)
                            @include('quiz._attempt-card', ['attempt' => $attempt])
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
