@forelse ($papers as $paper)
    @if ($loop->first)
        <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
    @endif
        <li class="flex items-center gap-3 px-4 py-3">
            <i data-lucide="file-text" class="h-5 w-5 shrink-0 text-accent"></i>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="truncate text-sm font-semibold text-content">{{ $paper->title }}</span>
                    @if ($paper->doc_type === 'answer')
                        <span class="shrink-0 rounded-md bg-accent/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-accent">Answer key</span>
                    @elseif ($paper->doc_type === 'combined')
                        <span class="shrink-0 rounded-md bg-accent/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-accent">Questions + answers</span>
                    @endif
                </div>
                <div class="mt-0.5 text-xs text-muted">
                    {{ $paper->year }}@if ($paper->session) · {{ $paper->session }}@endif @if ($paper->part) · {{ $paper->part }}@endif
                    @if ($paper->description) · {{ $paper->description }} @endif
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('exams.view', $paper) }}" target="_blank" rel="noopener"
                   title="View in browser"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                    <span class="hidden sm:inline">View</span>
                </a>
                <a href="{{ route('exams.download', $paper) }}"
                   title="Download"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-tr from-accent-from to-accent-to px-3 py-2 text-sm font-semibold text-white transition-colors hover:opacity-90">
                    <i data-lucide="download" class="h-4 w-4"></i>
                    <span class="hidden sm:inline">Download</span>
                </a>
            </div>
        </li>
    @if ($loop->last)
        </ul>
    @endif
@empty
    <div class="rounded-2xl border border-dashed border-line bg-surface px-4 py-12 text-center">
        <i data-lucide="folder-open" class="mx-auto h-8 w-8 text-muted"></i>
        <p class="mt-3 text-sm font-medium text-content">No papers here yet</p>
        <p class="mt-1 text-xs text-muted">Nothing has been uploaded for this level.</p>
    </div>
@endforelse
