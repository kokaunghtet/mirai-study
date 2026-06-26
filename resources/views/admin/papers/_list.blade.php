{{-- Filter chips --}}
<div class="mb-5 space-y-2">
    {{-- Category row --}}
    <div class="flex flex-wrap items-center gap-1.5">
        <span class="w-16 text-xs font-semibold text-muted">Category</span>
        @foreach ($categories as $cat)
            @php $on = request('category') === $cat->name; @endphp
            <a href="{{ request()->fullUrlWithQuery(['category' => $on ? null : $cat->name, 'page' => null]) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
                {{ $cat->name }}
                <span class="rounded-full px-1.5 {{ $on ? 'bg-white/20' : 'bg-surface-muted text-muted' }}">{{ $counts['category'][$cat->id] ?? 0 }}</span>
            </a>
        @endforeach
    </div>
    {{-- Level row --}}
    <div class="flex flex-wrap items-center gap-1.5">
        <span class="w-16 text-xs font-semibold text-muted">Level</span>
        @foreach ($categories as $cat)
            @if (!request('category') || request('category') === $cat->name)
                @foreach ($cat->levels as $lvl)
                    @php $on = request('level') === $lvl->code; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['level' => $on ? null : $lvl->code, 'page' => null]) }}"
                       class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
                        {{ $lvl->code }}
                        <span class="rounded-full px-1.5 {{ $on ? 'bg-white/20' : 'bg-surface-muted text-muted' }}">{{ $counts['level'][$lvl->id] ?? 0 }}</span>
                    </a>
                @endforeach
            @endif
        @endforeach
    </div>
    {{-- Year row --}}
    <div class="flex flex-wrap items-center gap-1.5">
        <span class="w-16 text-xs font-semibold text-muted">Year</span>
        @foreach ($years as $year)
            @php $on = request('year') == $year; @endphp
            <a href="{{ request()->fullUrlWithQuery(['year' => $on ? null : $year, 'page' => null]) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
                {{ $year }}
                <span class="rounded-full px-1.5 {{ $on ? 'bg-white/20' : 'bg-surface-muted text-muted' }}">{{ $counts['year'][$year] ?? 0 }}</span>
            </a>
        @endforeach
    </div>
    {{-- Doc type row --}}
    <div class="flex flex-wrap items-center gap-1.5">
        <span class="w-16 text-xs font-semibold text-muted">Type</span>
        @foreach (['question' => 'Question', 'answer' => 'Answer', 'combined' => 'Combined'] as $code => $label)
            @php $on = request('doc_type') === $code; $cnt = $counts['doc_type'][$code] ?? 0; @endphp
            @if ($on || $cnt > 0)
            <a href="{{ request()->fullUrlWithQuery(['doc_type' => $on ? null : $code, 'page' => null]) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
                {{ $label }}
                <span class="rounded-full px-1.5 {{ $on ? 'bg-white/20' : 'bg-surface-muted text-muted' }}">{{ $cnt }}</span>
            </a>
            @endif
        @endforeach
    </div>
</div>
@if (request()->hasAny(['category', 'level', 'year', 'doc_type']))
    <a href="{{ route('admin.papers') }}" class="mb-4 inline-flex items-center gap-1 text-xs font-medium text-muted hover:text-content">
        <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear filters
    </a>
@endif

{{-- Papers --}}
@forelse ($papers as $paper)
    @if ($loop->first)
        <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
    @endif
        <li x-data="paperHistory({{ $paper->id }})"
            @dblclick="toggle()"
            class="cursor-default"
            title="Double-click to view history">
            <div class="flex items-center gap-3 px-4 py-3">
                <i data-lucide="file-text" class="h-5 w-5 shrink-0 text-accent"></i>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-content">{{ $paper->title }}</div>
                    <div class="mt-0.5 text-xs text-muted">
                        {{ $paper->category?->name }}
                        @if ($paper->level) · {{ $paper->level->code }} @endif
                        · {{ $paper->year }}@if ($paper->session) {{ $paper->session }} @endif
                        · {{ $paper->downloads_count }} {{ Str::plural('download', $paper->downloads_count) }}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click.stop="toggle()" title="View history"
                            :class="open ? 'text-accent border-accent/30 bg-accent/5' : 'text-muted'"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-semibold transition-colors hover:bg-surface-muted">
                        <i data-lucide="git-branch" class="h-4 w-4"></i>
                    </button>
                    <a href="{{ route('admin.papers.edit', $paper) }}" title="Edit paper"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                        <i data-lucide="square-pen" class="h-4 w-4"></i>
                        <span class="hidden sm:inline">Edit</span>
                    </a>
                    @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('admin.papers.destroy', $paper) }}"
                              data-confirm="Delete this paper? This cannot be undone.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete paper"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                <span class="hidden sm:inline">Delete</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Git history panel --}}
            <div x-show="open" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="border-t border-line px-4 pb-4 pt-3">

                <div x-show="loading" class="py-3 text-center text-xs text-muted">
                    <i data-lucide="loader-circle" class="mx-auto mb-1 h-4 w-4 animate-spin text-accent"></i>
                    Loading history…
                </div>

                <div x-show="!loading" class="relative">
                    <div class="absolute top-0 bottom-0 left-[11px] w-px bg-line"></div>
                    <template x-for="(rev, i) in revisions" :key="rev.id">
                        <div class="relative flex gap-3" :class="i < revisions.length - 1 ? 'pb-4' : ''">
                            <div class="relative z-10 shrink-0 flex justify-center" style="width:24px;padding-top:3px">
                                <div :class="rev.is_latest
                                    ? 'h-[18px] w-[18px] rounded-full bg-accent border-[3px] border-surface'
                                    : 'h-3.5 w-3.5 rounded-full bg-surface border-2 border-line mt-0.5'">
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <div class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-accent/15 text-[10px] font-bold text-accent"
                                             x-text="rev.editor.initial"></div>
                                        <span class="text-xs font-semibold text-content" x-text="rev.editor.display_name"></span>
                                        <span x-show="rev.is_latest"
                                              class="rounded-full bg-accent/15 px-1.5 py-0.5 text-[10px] font-bold text-accent uppercase tracking-wide">HEAD</span>
                                    </div>
                                    <span class="shrink-0 text-[11px] text-muted" x-text="rev.created_at_full"></span>
                                </div>
                                <p class="mt-0.5 text-[11px] text-muted"
                                   x-text="rev.action === 'uploaded' ? 'Uploaded this paper' : 'Edited metadata'"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="!loading && revisions.length === 0">
                        <div class="flex items-center gap-3">
                            <div class="z-10 shrink-0 flex justify-center w-6 pt-0.5">
                                <div class="h-3.5 w-3.5 rounded-full bg-surface border-2 border-line mt-0.5"></div>
                            </div>
                            <p class="py-2 text-xs text-muted">No history yet.</p>
                        </div>
                    </template>
                </div>
            </div>
        </li>
    @if ($loop->last)
        </ul>
    @endif
@empty
    <div class="rounded-2xl border border-dashed border-line bg-surface px-4 py-12 text-center">
        <i data-lucide="folder-open" class="mx-auto h-8 w-8 text-muted"></i>
        @if (request()->hasAny(['category', 'level', 'year', 'doc_type']))
            <p class="mt-3 text-sm font-medium text-content">No papers match this filter.</p>
            <p class="mt-1 text-xs text-muted">Try a different combination or clear filters.</p>
        @else
            <p class="mt-3 text-sm font-medium text-content">No papers yet</p>
            <p class="mt-1 text-xs text-muted">Upload your first past paper to get started.</p>
        @endif
    </div>
@endforelse

@if ($papers->hasPages())
    <div class="mt-4">{{ $papers->links() }}</div>
@endif
