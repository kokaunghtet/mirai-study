@props(['post'])

<article x-data="{ commentsCount: {{ $post->comments_count }} }"
    @dblclick="if (!$event.target.closest('a,button,input,textarea,video,form,[data-no-nav]')) window.location='{{ route('posts.show', $post) }}'"
    class="relative rounded-2xl bg-surface border border-line shadow-sm">

    {{-- Header: Avatar + Name + Actions --}}
    <header class="flex items-center justify-between px-4 py-3.5">
        <div class="flex items-center gap-2.5">

            {{-- Avatar --}}
            <div class="grid h-[38px] w-[38px] shrink-0 place-items-center overflow-hidden rounded-full border border-line">
                @if ($post->user->profile_image)
                    <img src="{{ $post->user->profile_image }}"
                        alt="{{ $post->user->display_name }}"
                        loading="lazy"
                        class="h-full w-full object-cover">
                @else
                    <div class="grid h-full w-full place-items-center rounded-full bg-accent/15 text-sm font-bold text-accent">
                        {{ strtoupper(substr($post->user->display_name, 0, 1)) }}
                    </div>
                @endif
            </div>

            <div>
                <a href="{{ route('profile.show', $post->user->username) }}"
                   class="text-[13px] font-bold text-content hover:text-accent transition-colors">
                    {{ $post->user->display_name }}
                </a>
                <div class="mt-0.5 flex items-center gap-1.5 text-[11px] text-muted">
                    {{ $post->created_at->diffForHumans() }}
                    @if ($post->isFreshForViewer())
                        <span class="rounded-full bg-accent/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-accent">New</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Follow + More menu --}}
        <div class="flex items-center gap-2">

            {{-- Follow button — shown to everyone except post owner --}}
            @if (auth()->id() !== $post->user_id)
                @auth
                    <div x-data="{
                            following: {{ auth()->user()->following()->where('following_id', $post->user_id)->exists() ? 'true' : 'false' }},
                            loading: false,
                            async toggle() {
                                if (this.loading) return;
                                this.loading = true;
                                try {
                                    const res = await fetch('{{ route('users.follow', $post->user) }}', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                            'Accept': 'application/json',
                                        }
                                    });
                                    const data = await res.json();
                                    this.following = data.following;
                                    window.dispatchEvent(new CustomEvent('follow-changed', {
                                        detail: { userId: {{ $post->user_id }}, following: data.following }
                                    }));
                                } finally {
                                    this.loading = false;
                                }
                            }
                         }"
                         @follow-changed.window="if ($event.detail.userId === {{ $post->user_id }}) following = $event.detail.following">
                        <button type="button"
                                @click="toggle()"
                                :disabled="loading"
                                :class="following
                                    ? 'bg-surface border-line text-content hover:border-red-200 hover:text-red-600 hover:bg-red-50'
                                    : 'bg-accent border-transparent text-white hover:bg-accent-strong'"
                                class="shrink-0 rounded-lg border px-3 py-1.5 text-[12px] font-bold transition-all active:scale-95">
                            <span x-text="following ? 'Following' : 'Follow'"></span>
                        </button>
                    </div>
                @else
                    <button type="button"
                            onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                            class="rounded-lg border border-line bg-surface px-3 py-1.5 text-[12px] font-bold text-content transition-all hover:bg-surface-muted active:scale-95">
                        Follow
                    </button>
                @endauth
            @endif

            {{-- More menu --}}
            @if (auth()->id() === $post->user_id || (auth()->check() && ! $post->user->isAdmin()))
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false"
                        class="grid h-8 w-8 place-items-center rounded-lg text-muted hover:bg-surface-muted transition-colors"
                        type="button">
                    <i data-lucide="ellipsis" class="h-5 w-5"></i>
                </button>

                {{-- Dropdown — structure is always closed correctly --}}
                <div x-show="open"
                     class="absolute right-0 top-10 z-50 w-36 overflow-hidden rounded-xl bg-surface py-1 text-[13px] font-semibold shadow-lg border border-line">

                    @if (auth()->id() === $post->user_id)
                        {{-- Owner: Edit + Delete --}}
                        <a href="{{ route('posts.edit', $post) }}"
                           class="block w-full px-3 py-2 text-left text-content hover:bg-surface-muted transition">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('posts.destroy', $post) }}"
                              data-confirm="Delete this post?">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">
                                Delete
                            </button>
                        </form>
                    @else
                        <button type="button"
                                @click="$dispatch('open-report', { type: 'post', id: {{ $post->id }} }); open = false"
                                class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">
                            Report
                        </button>
                    @endif

                </div>{{-- ← always closed, regardless of @if branch --}}
            </div>
            @endif

        </div>
    </header>

    {{-- Title (optional) --}}
    @if ($post->title)
        <div class="px-4 pb-1">
            <a href="{{ route('posts.show', $post) }}"
               class="font-bold text-content hover:text-accent transition-colors">
                {{ $post->title }}
            </a>
        </div>
    @endif

    {{-- Content --}}
    <div data-no-nav class="px-4 pb-3 pt-0 break-words {{ strlen($post->content) < 80 ? 'text-lg leading-7 font-medium text-content' : 'text-sm leading-6 text-content' }}">
        {{ Str::limit($post->content, 300) }}
        @if (strlen($post->content) > 300)
            <a href="{{ route('posts.show', $post) }}"
               class="text-accent hover:underline text-sm ml-1">see more</a>
        @endif
    </div>

    {{-- Media (images) --}}
    @php $mediaItems = $post->media->where('type', '!=', 'document'); @endphp
    @if ($mediaItems->isNotEmpty())
        <div class="mx-4 mb-3" x-data="{ idx: 0 }">
            <div class="relative aspect-video overflow-hidden rounded-xl bg-black">

                {{-- Slides --}}
                @foreach ($mediaItems->values() as $i => $item)
                    <div x-show="idx === {{ $i }}" class="h-full w-full">
                        <img src="{{ $item->url }}" alt="post media" loading="lazy"
                            class="h-full w-full object-cover cursor-zoom-in">
                    </div>
                @endforeach

                {{-- Counter --}}
                @if ($mediaItems->count() > 1)
                    <div class="absolute right-2.5 top-2.5 rounded-md bg-black/70 px-2 py-1 text-[11px] font-semibold text-white"
                        x-text="`${idx + 1}/{{ $mediaItems->count() }}`">
                    </div>

                    {{-- Prev/Next --}}
                    <button type="button" @click="idx = Math.max(0, idx - 1)"
                            class="absolute left-2 top-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white hover:bg-black/70 text-xl">
                        ‹
                    </button>
                    <button type="button" @click="idx = Math.min({{ $mediaItems->count() - 1 }}, idx + 1)"
                            class="absolute right-2 top-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white hover:bg-black/70 text-xl">
                        ›
                    </button>
                @endif
            </div>

            {{-- Dots --}}
            @if ($mediaItems->count() > 1)
                <div class="flex justify-center gap-1.5 py-2">
                    @foreach ($mediaItems->values() as $i => $item)
                        <button type="button" @click="idx = {{ $i }}"
                                :class="idx === {{ $i }} ? 'bg-accent' : 'bg-gray-300'"
                                class="h-1.5 w-1.5 rounded-full transition-colors">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- File attachments --}}
    @php $fileItems = $post->media->where('type', 'document'); @endphp
    @if ($fileItems->isNotEmpty())
        <div class="flex flex-col gap-2 px-4 pb-3">
            @foreach ($fileItems as $file)
                <a href="{{ $file->url }}" target="_blank"
                class="flex items-center gap-2.5 rounded-lg border border-line bg-surface px-3 py-2.5 hover:bg-surface-muted transition-colors">
                    <div class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-lg bg-accent/15 text-accent">
                        <i data-lucide="file" class="h-4 w-4"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-xs font-semibold text-content">
                            {{ $file->filename ?? basename($file->url) }}
                        </div>
                        @if ($file->filesize)
                            <div class="text-[11px] text-muted">
                                {{ max(1, round($file->filesize / 1024)) }} KB
                            </div>
                        @endif
                    </div>
                    <i data-lucide="upload" class="h-4 w-4 text-muted shrink-0"></i>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Tags --}}
    @if ($post->tags->isNotEmpty())
        <div class="flex flex-wrap gap-1.5 px-4 pb-3">
            @foreach ($post->tags as $tag)
                <span class="rounded-full bg-accent/10 px-2.5 py-0.5 text-[11px] font-semibold text-accent">
                    {{ $tag->name }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- Footer: Actions --}}
    <footer class="flex items-center justify-between px-3.5 py-3 border-t border-line">

        {{-- Left: Like + Comment --}}
        <div class="flex items-center gap-1">

            {{-- Like --}}
            @auth
                @php $isLiked = $post->likes->where('user_id', auth()->id())->isNotEmpty(); @endphp
                <div x-data="{
                        liked: {{ $isLiked ? 'true' : 'false' }},
                        count: {{ $post->likes_count }},
                        loading: false,
                        async toggle() {
                            if (this.loading) return;
                            this.loading = true;
                            try {
                                const res = await fetch('{{ route('posts.like', $post) }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json',
                                    }
                                });
                                const data = await res.json();
                                this.liked  = data.liked;
                                this.count  = data.likes_count;
                            } finally {
                                this.loading = false;
                            }
                        }
                    }">
                    <button type="button"
                            @click="toggle()"
                            :disabled="loading"
                            :class="liked
                                ? 'text-accent'
                                : 'text-muted hover:bg-surface-muted hover:text-accent'"
                            class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 transition-all">
                        <i data-lucide="thumbs-up" class="h-[18px] w-[18px]"
                           :fill="liked ? 'currentColor' : 'none'"></i>
                        <span class="text-xs font-semibold" x-text="count"></span>
                    </button>
                </div>
            @else
                <button type="button"
                        onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-muted hover:bg-surface-muted hover:text-accent transition-all">
                    <i data-lucide="thumbs-up" class="h-[18px] w-[18px]"></i>
                    <span class="text-xs font-semibold">{{ $post->likes_count }}</span>
                </button>
            @endauth

            {{-- Comment — opens the side drawer; falls back to the post page without JS --}}
            <a href="{{ route('posts.show', $post) }}#comments"
               data-no-nav
               @click.prevent="$dispatch('open-comments', {
                    id: {{ $post->id }},
                    url: '{{ route('comments.index', $post) }}',
                    title: @js($post->title ?: $post->user->display_name . "'s post")
               })"
               class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-muted hover:bg-surface-muted hover:text-accent transition-all">
                <i data-lucide="message-circle" class="h-[18px] w-[18px]"></i>
                <span class="text-xs font-semibold" x-text="$store.commentCounts[{{ $post->id }}] ?? commentsCount">{{ $post->comments_count }}</span>
            </a>
        </div>

        {{-- Right: Bookmark + Share --}}
        <div class="flex items-center gap-1">

            {{-- Bookmark --}}
            @auth
                @php $isBookmarked = $post->bookmarks->isNotEmpty(); @endphp
                <div x-data="{
                        bookmarked: {{ $isBookmarked ? 'true' : 'false' }},
                        loading: false,
                        async toggle() {
                            if (this.loading) return;
                            this.loading = true;
                            try {
                                const res = await fetch('{{ route('posts.bookmark', $post) }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json',
                                    }
                                });
                                const data = await res.json();
                                this.bookmarked = data.bookmarked;
                            } finally {
                                this.loading = false;
                            }
                        }
                    }">
                    <button type="button"
                            @click="toggle()"
                            :disabled="loading"
                            :title="bookmarked ? 'Remove bookmark' : 'Bookmark'"
                            :class="bookmarked
                                ? 'text-accent'
                                : 'text-muted hover:bg-surface-muted hover:text-accent'"
                            class="rounded-lg px-2.5 py-1.5 transition-all">
                        <i data-lucide="bookmark" class="h-[18px] w-[18px]"
                           :fill="bookmarked ? 'currentColor' : 'none'"></i>
                    </button>
                </div>
            @else
                <button type="button"
                        onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                        class="rounded-lg px-2.5 py-1.5 text-muted hover:bg-surface-muted hover:text-accent transition-all">
                    <i data-lucide="bookmark" class="h-[18px] w-[18px]"></i>
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
                        class="rounded-lg px-2.5 py-1.5 text-muted hover:bg-surface-muted hover:text-accent transition-all"
                        :title="copied ? 'Link copied!' : 'Copy link'">
                    <i data-lucide="send" x-show="!copied" class="h-[18px] w-[18px]"></i>
                    <i data-lucide="check" x-show="copied" class="h-[18px] w-[18px] text-accent"></i>
                </button>
            </div>

        </div>
    </footer>

</article>