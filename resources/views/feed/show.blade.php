<x-app-layout>

    <!-- Back button to go back to feed page -->
    <a href="{{ route('feed.index') }}"
       class="mb-5 inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-3 py-1.5 text-sm font-semibold text-muted shadow-sm transition-all hover:bg-accent/10 hover:text-accent hover:border-accent/30 active:scale-95">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Back
    </a>

    <x-slot name="title">{{ $post->title ?? 'Post' }} — MiraiStudy</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Post --}}
            <article class="bg-surface border border-line rounded-xl p-6">

                {{-- Author --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold">
                            {{ strtoupper(substr($post->user->display_name, 0, 1)) }}
                        </div>
                        <div>
                            <a href="{{ route('profile.show', $post->user->username) }}"
                               class="font-semibold text-content hover:text-accent">
                                {{ $post->user->display_name }}
                            </a>
                            <p class="text-xs text-muted">{{ $post->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    {{-- Edit/Delete for owner --}}
                    @auth
                        @if (auth()->id() === $post->user_id)
                            <div class="flex items-center gap-2">
                                <a href="{{ route('posts.edit', $post) }}"
                                    class="inline-flex items-center gap-1 rounded-lg border border-line bg-surface px-3 py-1.5 text-xs font-semibold text-muted shadow-sm transition-all hover:bg-accent/10 hover:text-accent hover:border-accent/30 active:scale-95">Edit</a>
                                <form method="POST" action="{{ route('posts.destroy', $post) }}"
                                      data-confirm="Delete this post?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-line bg-surface px-3 py-1.5 text-xs font-semibold text-red-500 shadow-sm transition-all hover:bg-red-50 hover:text-red-600 hover:border-red-200 active:scale-95">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endauth
                </div>

                {{-- Title --}}
                @if ($post->title)
                    <h1 class="text-2xl font-bold text-content mb-3">{{ $post->title }}</h1>
                @endif

                {{-- Content --}}
                <div class="text-content leading-relaxed mb-3">
                    {{ $post->content }}
                </div>

                {{-- Media (images/videos) --}}
                @php $mediaItems = $post->media->where('type', '!=', 'document'); @endphp
                @if ($mediaItems->isNotEmpty())
                    <div class="mt-4" x-data="{ idx: 0 }">
                        <div class="relative aspect-video overflow-hidden rounded-xl bg-black">

                            {{-- Slides --}}
                            @foreach ($mediaItems->values() as $i => $item)
                                <div x-show="idx === {{ $i }}" class="h-full w-full">
                                    @if ($item->type === 'video')
                                        <video src="{{ $item->url }}" class="h-full w-full object-cover" controls></video>
                                    @else
                                        <img src="{{ $item->url }}" alt="post media"
                                            class="h-full w-full object-cover cursor-zoom-in">
                                    @endif
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
                                    &leftarrow;
                                </button>
                                <button type="button" @click="idx = Math.min({{ $mediaItems->count() - 1 }}, idx + 1)"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white hover:bg-black/70 text-xl">
                                    &rightarrow;
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
                    <div class="mt-4 flex flex-col gap-2">
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
                    <div class="flex flex-wrap gap-2 my-4">
                        @foreach ($post->tags as $tag)
                            <span class="text-xs bg-accent/10 text-accent px-2.5 py-1 rounded-full">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                {{-- Footer: Actions --}}
                <div class="flex items-center justify-between px-3.5 pt-3 border-t border-line">

                    {{-- Left: Like + Comment --}}
                    <div class="flex items-center gap-1">

                        {{-- Like --}}
                        @auth
                            <?php $liked = $post->likes->contains('user_id', auth()->id()); ?>
                            <div x-data="{
                                    liked: {{ $liked ? 'true' : 'false' }},
                                    count: {{ $post->likes_count }},
                                    busy: false,
                                    async toggle() {
                                        if (this.busy) return;
                                        this.busy = true;
                                        try {
                                            const res = await fetch('{{ route('posts.like', $post) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                    'Accept': 'application/json',
                                                }
                                            });
                                            const data = await res.json();
                                            this.liked = data.liked;
                                            this.count = data.likes_count;
                                        } catch (e) {
                                            console.error('Like failed:', e);
                                        } finally {
                                            this.busy = false;
                                        }
                                    }
                                }">
                                <button type="button"
                                        @click="toggle()"
                                        :disabled="busy"
                                        :class="liked ? 'text-accent' : 'text-muted hover:bg-surface-muted hover:text-accent'"
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

                        {{-- Comment --}}
                        <a href="{{ route('posts.show', $post) }}#comments"
                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-muted hover:bg-surface-muted hover:text-accent transition-all">
                            <i data-lucide="message-circle" class="h-[18px] w-[18px]"></i>
                            <span class="text-xs font-semibold">{{ $post->comments_count }}</span>
                        </a>
                    </div>

                    {{-- Right: Bookmark + Share --}}
                    <div class="flex items-center gap-1">

                        {{-- Bookmark --}}
                        @auth
                            @php $isBookmarked = $post->bookmarks->isNotEmpty(); @endphp
                            <div x-data="{
                                    bookmarked: {{ $isBookmarked ? 'true' : 'false' }},
                                    busy: false,
                                    async toggle() {
                                        if (this.busy) return;
                                        this.busy = true;
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
                                        } catch (e) {
                                            console.error('Bookmark failed:', e);
                                        } finally {
                                            this.busy = false;
                                        }
                                    }
                                }">
                                <button type="button"
                                        @click="toggle()"
                                        :disabled="busy"
                                        :title="bookmarked ? 'Remove bookmark' : 'Bookmark'"
                                        :class="bookmarked ? 'text-accent' : 'text-muted hover:bg-surface-muted hover:text-accent'"
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

                        {{-- Share --}}
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
                </div>
            </article>

            {{-- Edit History (isolated scope) --}}
            <div x-data="postHistory({{ $post->id }})">
                <button type="button" @click="toggleHistory()"
                        class="mb-3 flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-muted hover:bg-surface-muted hover:text-accent transition-all">
                    <i data-lucide="git-branch" class="h-4 w-4"></i>
                    <span x-text="open ? 'Hide History' : 'View History'"></span>
                </button>

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="rounded-xl border border-line bg-surface overflow-hidden">

                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-line">
                        <div class="flex items-center gap-2 text-sm font-semibold text-content">
                            <i data-lucide="git-branch" class="h-4 w-4 text-accent"></i>
                            Edit History
                        </div>
                        <button @click="open = false" class="grid h-7 w-7 place-items-center rounded-full text-muted hover:bg-surface-muted hover:text-content transition-colors">
                            <i data-lucide="x" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>

                    {{-- Loading --}}
                    <div x-show="loading" class="px-5 py-10 text-center text-sm text-muted">
                        <i data-lucide="loader-circle" class="mx-auto h-5 w-5 animate-spin text-accent mb-2"></i>
                        Loading history…
                    </div>

                    {{-- Git graph --}}
                    <div x-show="!loading" class="px-5 py-5">
                        <div class="relative">
                            {{-- Vertical line --}}
                            <div class="absolute top-0 bottom-0 left-[11px] w-px bg-line"></div>

                            <template x-for="(rev, i) in revisions" :key="rev.id">
                                <div class="relative flex gap-4" :class="i < revisions.length - 1 ? 'pb-6' : ''">
                                    {{-- Dot --}}
                                    <div class="relative z-10 shrink-0 flex justify-center" style="width:24px;padding-top:3px">
                                        <div :class="rev.is_latest
                                            ? 'h-[18px] w-[18px] rounded-full bg-accent border-[3px] border-surface'
                                            : 'h-3.5 w-3.5 rounded-full bg-surface border-2 border-line mt-0.5'">
                                        </div>
                                    </div>
                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0 pb-0.5">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <div class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-accent/15 text-[11px] font-bold text-accent"
                                                     x-text="rev.editor.initial"></div>
                                                <span class="text-sm font-semibold text-content" x-text="rev.editor.display_name"></span>
                                                <span x-show="rev.is_latest"
                                                      class="inline-flex items-center rounded-full bg-accent/15 px-1.5 py-0.5 text-[10px] font-bold text-accent uppercase tracking-wide">HEAD</span>
                                                <span x-show="rev.is_initial && !rev.is_latest"
                                                      class="inline-flex items-center rounded-full bg-surface-muted px-1.5 py-0.5 text-[10px] font-medium text-muted uppercase tracking-wide">Initial</span>
                                            </div>
                                            <span class="shrink-0 text-[11px] text-muted" x-text="rev.created_at_full"></span>
                                        </div>
                                        <p class="mt-0.5 text-[12px] text-muted"
                                           x-text="rev.is_initial ? 'Created this post' : 'Edited this post'"></p>
                                        <template x-if="rev.content_preview">
                                            <p class="mt-1.5 rounded-lg bg-surface-muted px-3 py-2 text-xs text-muted font-mono leading-relaxed"
                                               x-text="rev.content_preview"></p>
                                        </template>
                                        <template x-if="!rev.content_preview && rev.title">
                                            <p class="mt-1.5 text-xs text-muted italic" x-text="'Title: ' + rev.title"></p>
                                        </template>
                                        <template x-if="!rev.content_preview && !rev.title">
                                            <p class="mt-1.5 text-xs text-muted italic">Media / file-only post</p>
                                        </template>
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
                </div>
            </div>

            {{-- Comments Section --}}
            <section id="comments" class="bg-surface border border-line rounded-xl p-6">
                @include('feed._comments', ['post' => $post])
            </section>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4">
            <div class="bg-surface border border-line rounded-xl p-5">
                <h3 class="font-semibold text-content mb-1">
                    {{ $post->user->display_name }}
                </h3>
                <p class="text-sm text-muted mb-3">
                    {{ $post->user->bio ?? 'No bio yet.' }}
                </p>
                <a href="{{ route('profile.show', $post->user->username) }}"
                   class="text-sm text-accent hover:underline">
                    View Profile →
                </a>
            </div>
        </aside>

    </div>

    <script>
        function postHistory(postId) {
            return {
                open: false,
                loaded: false,
                loading: false,
                revisions: [],

                toggleHistory() {
                    this.open = !this.open;
                    if (this.open && !this.loaded) this.fetch();
                },

                async fetch() {
                    this.loading = true;
                    try {
                        const res = await fetch(`/posts/${postId}/history`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!res.ok) throw new Error(res.status);
                        this.revisions = await res.json();
                        this.loaded = true;
                    } catch (e) {
                        console.error('History fetch failed:', e);
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
