<x-app-layout>
    <x-slot name="title">Quiz result — MiraiStudy</x-slot>

    @php
        $total    = $attempt->total_questions;
        $score    = (int) $attempt->score;
        $pct      = $attempt->percentage();
        $passed   = $attempt->passed();
        $answered = $attempt->answers->count();
        $skipped  = max(0, $total - $answered);

        // Ring geometry
        $r    = 52;
        $circ = 2 * M_PI * $r;
        $dash = $circ * (1 - $pct / 100);
        $letters = ['A', 'B', 'C', 'D'];
    @endphp

    <div class="px-4">
        <div class="max-w-3xl mx-auto space-y-6">

            {{-- ── Score summary ───────────────────────────────────── --}}
            <section class="rounded-2xl border border-line bg-surface p-6 shadow-sm text-center">
                <span class="text-xs font-bold uppercase tracking-wider text-muted">{{ $heading }}</span>

                <div class="mt-5 flex flex-col items-center">
                    <div class="relative h-32 w-32">
                        <svg class="h-32 w-32 -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="rgb(var(--surface-muted))" stroke-width="10" />
                            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="rgb(var(--accent))" stroke-width="10"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $circ }}"
                                    stroke-dashoffset="{{ $dash }}" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold text-content">{{ $pct }}%</span>
                            <span class="text-xs text-muted">{{ $score }} / {{ $total }}</span>
                        </div>
                    </div>

                    <div class="mt-4 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-bold
                                {{ $passed ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600' }}">
                        <i data-lucide="{{ $passed ? 'award' : 'circle-x' }}" class="h-4 w-4"></i>
                        {{ $passed ? 'Passed' : 'Keep practicing' }}
                    </div>

                    @if ($skipped > 0)
                        <p class="mt-2 text-xs text-muted">{{ $skipped }} question{{ $skipped === 1 ? '' : 's' }} left unanswered</p>
                    @endif
                </div>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                    <form method="POST" action="{{ route('quiz.start') }}" data-loading>
                        @csrf
                        <input type="hidden" name="category" value="{{ $attempt->category->name }}">
                        <input type="hidden" name="level" value="{{ $attempt->level->code }}">
                        <input type="hidden" name="section" value="{{ $attempt->section }}">
                        <input type="hidden" name="count" value="{{ $total }}">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-accent px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-accent-strong sm:w-auto">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i> Retake
                        </button>
                    </form>
                    <a href="{{ route('quiz.index') }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-line px-5 py-2.5 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i> New quiz
                    </a>
                </div>
            </section>

            {{-- ── Review ──────────────────────────────────────────── --}}
            <section class="space-y-3">
                <h2 class="px-1 text-sm font-semibold text-content">Review your answers</h2>

                @foreach ($attempt->answers as $i => $answer)
                    @php $q = $answer->question; @endphp
                    <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                        <div class="flex items-start gap-2">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-[11px] font-bold
                                        {{ $answer->is_correct ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                                {{ $i + 1 }}
                            </span>
                            <p class="text-sm font-semibold leading-relaxed text-content">{{ $q->text }}</p>
                        </div>

                        <div class="mt-3 space-y-1.5 pl-8">
                            @foreach ($letters as $letter)
                                @php
                                    $optKey     = 'option_' . strtolower($letter);
                                    $isCorrect  = $letter === $q->answer;
                                    $isSelected = $letter === $answer->selected_answer;
                                @endphp
                                <div class="flex items-center gap-2.5 rounded-lg border px-3 py-2 text-sm
                                            @if ($isCorrect) border-green-300 bg-green-50 text-green-800
                                            @elseif ($isSelected) border-red-300 bg-red-50 text-red-700
                                            @else border-line text-content @endif">
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-[11px] font-bold
                                                @if ($isCorrect) bg-green-200 text-green-800
                                                @elseif ($isSelected) bg-red-200 text-red-700
                                                @else bg-surface-muted text-muted @endif">
                                        {{ $letter }}
                                    </span>
                                    <span class="flex-1">{{ $q->{$optKey} }}</span>
                                    @if ($isCorrect)
                                        <i data-lucide="circle-check" class="h-4 w-4 text-green-600"></i>
                                    @elseif ($isSelected)
                                        <i data-lucide="circle-x" class="h-4 w-4 text-red-500"></i>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($q->explanation)
                            <p class="mt-3 ml-8 rounded-lg bg-surface-muted px-3 py-2 text-xs leading-relaxed text-muted">
                                <span class="font-semibold text-content">Explanation:</span> {{ $q->explanation }}
                            </p>
                        @else 
                            <p class="mt-3 ml-8 rounded-lg bg-surface-muted px-3 py-2 text-xs leading-relaxed text-muted">
                                <span class="font-semibold text-content">Explanation:</span> explanantion not available at the moment
                            </p>
                        @endif
                    </div>
                @endforeach
            </section>

        </div>
    </div>
</x-app-layout>
