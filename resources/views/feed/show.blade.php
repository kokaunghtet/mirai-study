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
                                      onsubmit="return confirm('Delete this post?')">
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
                <div class="text-content leading-relaxed">
                    {{ $post->content }}
                </div>

                {{-- Media (images/videos) --}}
                @php $mediaItems = $post->media->where('type', '!=', 'document'); @endphp
                @if ($mediaItems->isNotEmpty())
                    <div class="mx-3 mb-3" x-data="{ idx: 0 }">
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
                            <form method="POST" action="{{ route('posts.like', $post) }}">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 {{ $liked ? 'text-accent' : 'text-muted' }} hover:bg-surface-muted hover:text-accent transition-all">
                                    <i data-lucide="thumbs-up" class="h-[18px] w-[18px]"
                                        fill="{{ $liked ? 'currentColor' : 'none' }}"></i>
                                    <span class="text-xs font-semibold">{{ $post->likes_count }}</span>
                                </button>
                            </form>
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
                            <form method="POST" action="{{ route('posts.bookmark', $post) }}">
                                @csrf
                                <button type="submit"
                                        title="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark' }}"
                                        class="rounded-lg px-2.5 py-1.5 transition-all {{ $isBookmarked ? 'text-accent' : 'text-muted hover:bg-surface-muted hover:text-accent' }}">
                                    <i data-lucide="bookmark" class="h-[18px] w-[18px]"
                                        fill="{{ $isBookmarked ? 'currentColor' : 'none' }}"></i>
                                </button>
                            </form>
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
                </div>
            </article>

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
</x-app-layout>