{{-- One completed quiz attempt, as a clickable row. Expects: $attempt --}}
<a href="{{ route('quiz.result', $attempt) }}"
   class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface p-4 transition-colors hover:bg-surface-muted">
    <div class="min-w-0">
        <div class="truncate text-sm font-semibold text-content">{{ $attempt->heading() }}</div>
        <div class="mt-0.5 text-xs text-muted">
            {{ $attempt->score }}/{{ $attempt->total_questions }}
            @if ($attempt->completed_at)
                · {{ $attempt->completed_at->diffForHumans() }}
            @endif
        </div>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <span class="rounded-full px-2.5 py-1 text-xs font-bold
                     {{ $attempt->passed() ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600' }}">
            {{ $attempt->percentage() }}%
        </span>
        <i data-lucide="chevron-right" class="h-4 w-4 text-muted"></i>
    </div>
</a>
