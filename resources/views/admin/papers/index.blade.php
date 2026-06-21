<x-app-layout>
    <x-slot name="title">Manage Papers — MiraiStudy</x-slot>

    <div class="px-4">
        <div class="max-w-4xl mx-auto">

            {{-- Header --}}
            <header class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Manage Papers</h1>
                    <p class="mt-1 text-sm text-muted">Upload and remove past exam papers.</p>
                </div>
                <a href="{{ route('admin.papers.create') }}"
                   class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-accent px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-accent-strong">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Upload</span>
                </a>
            </header>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 rounded-xl border border-accent/30 bg-accent/10 px-4 py-3 text-sm font-medium text-content">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Papers --}}
            @forelse ($papers as $paper)
                @if ($loop->first)
                    <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                @endif
                    <li class="flex items-center gap-3 px-4 py-3">
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
                    </li>
                @if ($loop->last)
                    </ul>
                @endif
            @empty
                <div class="rounded-2xl border border-dashed border-line bg-surface px-4 py-12 text-center">
                    <i data-lucide="folder-open" class="mx-auto h-8 w-8 text-muted"></i>
                    <p class="mt-3 text-sm font-medium text-content">No papers yet</p>
                    <p class="mt-1 text-xs text-muted">Upload your first past paper to get started.</p>
                </div>
            @endforelse

            @if ($papers->hasPages())
                <div class="mt-4">{{ $papers->links() }}</div>
            @endif

        </div>
    </div>
</x-app-layout>
