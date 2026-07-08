{{--
    Comment drawer box (the panel itself). Shared by the feed (sits in its
    sidebar column) and other post-card pages like Bookmarks (floats in the
    right gutter).

    On mobile: fixed bottom sheet with backdrop.
    On desktop: sticky sidebar panel (slide-in from right).

    Pass `drawerClass` to control desktop positioning:
      • feed      → default `lg:sticky lg:top-4 lg:z-30`
      • bookmarks → `fixed top-4 right-4 …` (floats in the right gutter)
--}}

{{-- Mobile backdrop --}}
<div x-show="isOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="close()"
     class="lg:hidden fixed inset-0 z-[47] bg-black/50 backdrop-blur-sm"></div>

{{-- Drawer panel --}}
<div x-show="isOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-0 inset-x-0 z-[48] {{ $drawerClass ?? 'lg:sticky lg:bottom-auto lg:inset-x-auto lg:top-4 lg:z-30' }} flex max-h-[75vh] lg:max-h-[calc(100vh-2rem)] flex-col overflow-hidden rounded-t-2xl lg:rounded-xl border border-line bg-surface shadow-sm lg:mb-4">

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
    <div class="flex-1 overflow-y-auto scrollbar-transparent px-5 py-4 pb-14 lg:pb-4">
        <div x-show="loading">
            <x-leaf-loader class="py-6" />
        </div>
        {{-- Comments markup is injected here --}}
        <div x-ref="content"
             x-show="!loading"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"></div>
    </div>
</div>
