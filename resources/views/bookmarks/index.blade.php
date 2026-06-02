<x-app-layout>
    <x-slot name="title">Bookmarks — MiraiStudy</x-slot>

    <div class="flex justify-center">
        <div class="w-full max-w-[560px]">

            {{-- Header --}}
            <div class="mb-5">
                <h1 class="text-xl font-bold text-gray-900">Bookmarks</h1>
                <p class="text-sm text-gray-400 mt-0.5">Posts you've saved for later</p>
            </div>

            {{-- Posts --}}
            <div class="space-y-4">
                @forelse ($posts as $post)
                    <x-post-card :post="$post" />
                @empty
                    <div class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <svg class="h-7 w-7 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path d="M6 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18l-6-3-6 3z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-500">No bookmarks yet</p>
                        <p class="text-xs text-gray-400 mt-1">
                            Posts you bookmark will appear here.
                        </p>
                        <a href="{{ route('feed.index') }}"
                           class="mt-4 text-sm font-semibold text-green-600 hover:underline">
                            Browse the feed →
                        </a>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($posts->hasPages())
                <div class="mt-6">
                    {{ $posts->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>