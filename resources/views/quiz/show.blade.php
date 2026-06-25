<x-app-layout>
    <x-slot name="title">Quiz in progress — MiraiStudy</x-slot>

    <div class="px-4" x-data="quizPlayer(@js($questions), {{ $attempt->id }})" x-cloak>
        <div class="max-w-3xl mx-auto">

            <form method="POST" action="{{ route('quiz.submit', $attempt) }}" @submit="onSubmit($event)" data-loading>
                @csrf

                {{-- Hidden answer map — one field per question, posted on submit --}}
                <template x-for="q in questions" :key="'in-' + q.id">
                    <input type="hidden" :name="`answers[${q.id}]`" :value="answers[q.id] || ''">
                </template>

                {{-- ── Header: title, position, progress ───────────────── --}}
                <div class="rounded-t-2xl border border-line bg-surface px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted">{{ $heading }}</span>
                        <span class="text-sm font-semibold text-content">
                            Q <span x-text="current + 1"></span> / <span x-text="total"></span>
                        </span>
                    </div>
                    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-surface-muted">
                        <div class="h-full rounded-full bg-accent transition-all duration-300" :style="`width: ${progress}%`"></div>
                    </div>
                </div>

                {{-- ── Question + options ──────────────────────────────── --}}
                <div class="border-x border-line bg-surface px-5 py-6 min-h-[18rem]">
                    <template x-for="(q, i) in questions" :key="q.id">
                        <div x-show="current === i" x-transition.opacity>
                            <p class="text-base font-semibold leading-relaxed text-content" x-text="q.text"></p>

                            <div class="mt-5 space-y-2.5">
                                <template x-for="letter in ['A', 'B', 'C', 'D']" :key="letter">
                                    <button type="button" @click="select(q.id, letter)"
                                            class="flex w-full items-center gap-3 rounded-xl border p-3.5 text-left transition-all"
                                            :class="answers[q.id] === letter
                                                ? 'border-accent bg-accent/10 ring-1 ring-accent'
                                                : 'border-line bg-surface hover:border-accent/40 hover:bg-surface-muted'">
                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold"
                                              :class="answers[q.id] === letter ? 'bg-accent text-white' : 'bg-surface-muted text-muted'"
                                              x-text="letter"></span>
                                        <span class="text-sm text-content" x-text="q.options[letter]"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- ── Prev / Next ─────────────────────────────────────── --}}
                <div class="flex items-center justify-between gap-3 border-x border-b border-line bg-surface px-5 py-4">
                    <button type="button" @click="prev()" :disabled="current === 0"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-line px-4 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-40">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i> Prev
                    </button>

                    <span class="text-xs text-muted">
                        <span x-text="answeredCount"></span> of <span x-text="total"></span> answered
                    </span>

                    <button type="button" @click="next()" x-show="current < total - 1"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-line px-4 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                        Next <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </button>
                    <button type="submit" x-show="current === total - 1"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-tr from-accent-from to-accent-to px-4 py-2 text-sm font-bold text-white transition-colors hover:opacity-90">
                        Submit <i data-lucide="check" class="h-4 w-4"></i>
                    </button>
                </div>

                {{-- ── Question palette ────────────────────────────────── --}}
                <div class="mt-4 rounded-2xl border border-line bg-surface p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs font-semibold text-muted">Jump to question</span>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-tr from-accent-from to-accent-to px-3 py-1.5 text-xs font-bold text-white transition-colors hover:opacity-90">
                            Submit quiz <i data-lucide="check" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>
                    <div class="grid grid-cols-8 gap-1.5 sm:grid-cols-10">
                        <template x-for="(q, i) in questions" :key="'p-' + q.id">
                            <button type="button" @click="goTo(i)"
                                    class="grid h-9 place-items-center rounded-lg border text-xs font-semibold transition-all"
                                    :class="current === i
                                        ? 'border-accent bg-accent text-white'
                                        : (answers[q.id]
                                            ? 'border-accent/40 bg-accent/10 text-accent'
                                            : 'border-line bg-surface-muted text-muted hover:border-accent/40')"
                                    x-text="i + 1"></button>
                        </template>
                    </div>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
