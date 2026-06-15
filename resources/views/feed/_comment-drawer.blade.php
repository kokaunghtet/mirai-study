{{--
    Comment drawer box (the panel itself). Shared by the feed (sits in its
    sidebar column) and other post-card pages like Bookmarks (floats in the
    right gutter). Slides in from the right with no backdrop, so the page
    behind stays fully visible.

    Requires an x-data="commentDrawer()" ancestor that listens for the
    `open-comments` event post cards dispatch — include feed._comment-drawer-script
    on the page and wrap this in that root.

    Pass `drawerClass` to control positioning:
      • feed      → default `sticky top-4 z-30`  (in the sidebar column)
      • bookmarks → `fixed top-4 right-4 …`       (floats in the right gutter)
--}}
<div x-show="isOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-x-6"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-x-0"
     x-transition:leave-end="opacity-0 translate-x-6"
     class="{{ $drawerClass ?? 'sticky top-4 z-30' }} flex max-h-[calc(100vh-2rem)] flex-col overflow-hidden rounded-xl border border-line bg-surface shadow-sm">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
        <h3 class="truncate pr-3 font-semibold text-content" x-text="title">Comments</h3>
        <button type="button" @click="close()"
                class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-muted hover:bg-surface-muted hover:text-content transition-colors"
                title="Close">
            <i data-lucide="x" class="h-4 w-4"></i>
        </button>
    </div>

    {{-- Body --}}
    <div class="flex-1 overflow-y-auto px-5 py-4">
        <div x-show="loading">
            <x-leaf-loader class="py-6" />
        </div>
        {{-- Comments markup is injected here --}}
        <div x-ref="content" x-show="!loading"></div>
    </div>
</div>
