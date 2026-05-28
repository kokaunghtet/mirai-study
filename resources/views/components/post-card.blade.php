@props(['post'])

<article class="relative rounded-2xl bg-white border border-gray-200 shadow-sm transition-all hover:shadow-md">

    {{-- Header: Avatar + Name + Actions --}}
    <header class="flex items-center justify-between px-4 py-3.5">
        <div class="flex items-center gap-2.5">

            {{-- Avatar --}}
            <div class="grid h-[38px] w-[38px] shrink-0 place-items-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600">
                {{ strtoupper(substr($post->user->display_name, 0, 1)) }}
            </div>

            <div>
                <a href="{{ route('profile.show', $post->user->username) }}"
                   class="text-[13px] font-bold text-gray-900 hover:text-indigo-600 transition-colors">
                    {{ $post->user->display_name }}
                </a>
                <div class="mt-0.5 text-[11px] text-gray-400">
                    {{ $post->created_at->diffForHumans() }}
                </div>
            </div>
        </div>

        {{-- Follow + More menu --}}
        <div class="flex items-center gap-2">

            {{-- Follow button — shown to everyone except post owner --}}
            @if (auth()->id() !== $post->user_id)
                @auth
                    <form method="POST" action="{{ route('users.follow', $post->user) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-[12px] font-bold text-gray-700 transition-all hover:bg-gray-50 active:scale-95">
                            Follow
                        </button>
                    </form>
                @else
                    <button type="button"
                            onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-[12px] font-bold text-gray-700 transition-all hover:bg-gray-50 active:scale-95">
                        Follow
                    </button>
                @endauth
            @endif

            {{-- More menu --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false"
                        class="grid h-8 w-8 place-items-center rounded-lg text-gray-400 hover:bg-gray-100 transition-colors"
                        type="button">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="5" cy="12" r="1.6"/>
                        <circle cx="12" cy="12" r="1.6"/>
                        <circle cx="19" cy="12" r="1.6"/>
                    </svg>
                </button>

                {{-- Dropdown — structure is always closed correctly --}}
                <div x-show="open"
                     class="absolute right-0 top-10 z-50 w-36 overflow-hidden rounded-xl bg-white py-1 text-[13px] font-semibold shadow-lg border border-gray-200">

                    @if (auth()->id() === $post->user_id)
                        {{-- Owner: Edit + Delete --}}
                        <a href="{{ route('posts.edit', $post) }}"
                           class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50 transition">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('posts.destroy', $post) }}"
                              onsubmit="return confirm('Delete this post?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">
                                Delete
                            </button>
                        </form>
                    @else
                        {{-- Non-owner: Report (placeholder until report feature is built) --}}
                        <button type="button"
                                class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">
                            Report
                        </button>
                    @endif

                </div>{{-- ← always closed, regardless of @if branch --}}
            </div>

        </div>
    </header>

    {{-- Title (optional) --}}
    @if ($post->title)
        <div class="px-4 pb-1">
            <a href="{{ route('posts.show', $post) }}"
               class="font-bold text-gray-900 hover:text-indigo-600 transition-colors">
                {{ $post->title }}
            </a>
        </div>
    @endif

    {{-- Content --}}
    <div class="px-4 pb-4 pt-0 break-words {{ strlen($post->content) < 80 ? 'text-lg leading-7 font-medium text-gray-900' : 'text-sm leading-6 text-gray-700' }}">
        {{ Str::limit($post->content, 300) }}
        @if (strlen($post->content) > 300)
            <a href="{{ route('posts.show', $post) }}"
               class="text-indigo-600 hover:underline text-sm ml-1">see more</a>
        @endif
    </div>

    {{-- Tags --}}
    @if ($post->tags->isNotEmpty())
        <div class="flex flex-wrap gap-1.5 px-4 pb-3">
            @foreach ($post->tags as $tag)
                <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-semibold text-indigo-600">
                    {{ $tag->name }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- Footer: Actions --}}
    <footer class="flex items-center justify-between px-3.5 py-3 border-t border-gray-100">

        {{-- Left: Like + Comment --}}
        <div class="flex items-center gap-1">

            {{-- Like --}}
            @auth
                <form method="POST" action="{{ route('posts.like', $post) }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 transition-all">
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                            <path d="M7 10v10"/>
                            <path d="M15 5.5 14 10h5.2a2 2 0 0 1 2 2.3l-.8 5.4A4 4 0 0 1 16.4 21H7V10h2.4a2 2 0 0 0 1.8-1.1L14 3.5a1 1 0 0 1 1.9.6z"/>
                        </svg>
                        <span class="text-xs font-semibold">{{ $post->likes_count }}</span>
                    </button>
                </form>
            @else
                <button type="button"
                        onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 transition-all">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                        <path d="M7 10v10"/>
                        <path d="M15 5.5 14 10h5.2a2 2 0 0 1 2 2.3l-.8 5.4A4 4 0 0 1 16.4 21H7V10h2.4a2 2 0 0 0 1.8-1.1L14 3.5a1 1 0 0 1 1.9.6z"/>
                    </svg>
                    <span class="text-xs font-semibold">{{ $post->likes_count }}</span>
                </button>
            @endauth

            {{-- Comment --}}
            <a href="{{ route('posts.show', $post) }}#comments"
               class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 transition-all">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                    <path d="M21 12a8 8 0 0 1-8 8H7l-4 2 1.4-4.2A8 8 0 1 1 21 12z"/>
                </svg>
                <span class="text-xs font-semibold">{{ $post->comments_count }}</span>
            </a>
        </div>

        {{-- Right: Bookmark + Share --}}
        <div class="flex items-center gap-1">

            {{-- Bookmark --}}
            @auth
                <form method="POST" action="{{ route('posts.bookmark', $post) }}">
                    @csrf
                    <button type="submit"
                            class="rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 transition-all">
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                            <path d="M6 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18l-6-3-6 3z"/>
                        </svg>
                    </button>
                </form>
            @else
                <button type="button"
                        onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                        class="rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 transition-all">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                        <path d="M6 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18l-6-3-6 3z"/>
                    </svg>
                </button>
            @endauth

            {{-- Share — Alpine handles copy to clipboard, works for everyone --}}
            <div x-data="{ copied: false }">
                <button type="button"
                        x-on:click="
                            navigator.clipboard.writeText('{{ url()->route('posts.show', $post) }}');
                            copied = true;
                            setTimeout(() => copied = false, 2000)
                        "
                        class="rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 transition-all"
                        :title="copied ? 'Link copied!' : 'Copy link'">
                    <svg x-show="!copied" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                        <path d="M22 2 11 13"/>
                        <path d="M22 2 15 22 11 13 2 9l20-7z"/>
                    </svg>
                    <svg x-show="copied" class="h-[18px] w-[18px] text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </button>
            </div>

        </div>
    </footer>

</article>