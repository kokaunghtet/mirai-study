<x-app-layout>
    <x-slot name="title">Quiz history — MiraiStudy</x-slot>

    <div class="px-4">
        <div class="max-w-3xl mx-auto">

            {{-- Header --}}
            <header class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-content">Quiz history</h1>
                    <p class="mt-1 text-sm text-muted">Every quiz you've completed, newest first.</p>
                </div>
                <a href="{{ route('quiz.index') }}"
                   class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-line px-4 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> New quiz
                </a>
            </header>

            @if ($attempts->isEmpty())
                {{-- Empty state --}}
                <div class="rounded-2xl border border-line bg-surface p-12 text-center shadow-sm">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-surface-muted text-muted">
                        <i data-lucide="circle-help" class="h-6 w-6"></i>
                    </span>
                    <p class="mt-4 text-sm font-semibold text-content">No quizzes yet</p>
                    <p class="mt-1 text-xs text-muted">Take your first quiz to start tracking your results.</p>
                    <a href="{{ route('quiz.index') }}"
                       class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-accent px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-accent-strong">
                        Start a quiz <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($attempts as $attempt)
                        @include('quiz._attempt-card', ['attempt' => $attempt])
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $attempts->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
