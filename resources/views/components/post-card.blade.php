@props(['post'])

<article class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-sm transition">

    {{-- Author --}}
    <div class="flex items-center gap-3 mb-3">
        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
            {{ strtoupper(substr($post->user->display_name, 0, 1)) }}
        </div>
        <div>
            <a href="{{ route('profile.show', $post->user->username) }}"
               class="text-sm font-semibold text-gray-900 hover:text-indigo-600">
                {{ $post->user->display_name }}
            </a>
            <p class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</p>
        </div>
    </div>

    {{-- Title + Content --}}
    @if ($post->title)
        <h2 class="font-semibold text-gray-900 mb-1">
            <a href="{{ route('posts.show', $post) }}" class="hover:text-indigo-600">
                {{ $post->title }}
            </a>
        </h2>
    @endif

    <p class="text-gray-700 text-sm leading-relaxed line-clamp-3">
        {{ $post->content }}
    </p>

    {{-- Tags --}}
    @if ($post->tags->isNotEmpty())
        <div class="flex flex-wrap gap-1.5 mt-3">
            @foreach ($post->tags as $tag)
                <span class="text-xs bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full">
                    {{ $tag->name }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex items-center gap-5 mt-4 pt-3 border-t border-gray-100 text-sm text-gray-500">

        {{-- Like --}}
        @auth
            <form method="POST" action="{{ route('posts.like', $post) }}">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 hover:text-red-500 transition">
                    <span>♥</span>
                    <span>{{ $post->likes_count }}</span>
                </button>
            </form>
        @else
            <button onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                    class="flex items-center gap-1.5 hover:text-red-500 transition">
                <span>♥</span>
                <span>{{ $post->likes_count }}</span>
            </button>
        @endauth

        {{-- Comment --}}
        <a href="{{ route('posts.show', $post) }}"
           class="flex items-center gap-1.5 hover:text-indigo-500 transition">
            <span>💬</span>
            <span>{{ $post->comments_count }}</span>
        </a>

        {{-- Bookmark --}}
        @auth
            <form method="POST" action="{{ route('posts.bookmark', $post) }}">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 hover:text-yellow-500 transition">
                    <span>🔖</span>
                </button>
            </form>
        @else
            <button onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                    class="flex items-center gap-1.5 hover:text-yellow-500 transition">
                <span>🔖</span>
            </button>
        @endauth

        {{-- Read more --}}
        <a href="{{ route('posts.show', $post) }}"
           class="ml-auto text-indigo-600 hover:underline text-xs">
            Read more →
        </a>
    </div>
</article>