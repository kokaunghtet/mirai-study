<x-app-layout>

    <!-- Back button to go back to feed page -->
    <a href="{{ route('feed.index') }}"
       class="mb-5 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-600 shadow-sm transition-all hover:bg-green-50 hover:text-green-600 hover:border-green-200 active:scale-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"/>
            <path d="m12 19-7-7 7-7"/>
        </svg>
        Back
    </a>

    <x-slot name="title">{{ $post->title ?? 'Post' }} — MiraiStudy</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Post --}}
            <article class="bg-white border border-gray-200 rounded-xl p-6">

                {{-- Author --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold">
                            {{ strtoupper(substr($post->user->display_name, 0, 1)) }}
                        </div>
                        <div>
                            <a href="{{ route('profile.show', $post->user->username) }}"
                               class="font-semibold text-gray-900 hover:text-green-600">
                                {{ $post->user->display_name }}
                            </a>
                            <p class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    {{-- Edit/Delete for owner --}}
                    @auth
                        @if (auth()->id() === $post->user_id)
                            <div class="flex items-center gap-2">
                                <a href="{{ route('posts.edit', $post) }}"
                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 shadow-sm transition-all hover:bg-green-50 hover:text-green-600 hover:border-green-200 active:scale-95">Edit</a>
                                <form method="POST" action="{{ route('posts.destroy', $post) }}"
                                      onsubmit="return confirm('Delete this post?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-500 shadow-sm transition-all hover:bg-red-50 hover:text-red-600 hover:border-red-200 active:scale-95">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endauth
                </div>

                {{-- Title --}}
                @if ($post->title)
                    <h1 class="text-2xl font-bold text-gray-900 mb-3">{{ $post->title }}</h1>
                @endif

                {{-- Content --}}
                <div class="text-gray-700 leading-relaxed">
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
                                            :class="idx === {{ $i }} ? 'bg-green-500' : 'bg-gray-300'"
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
                            class="flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5 hover:bg-gray-50 transition-colors">
                                <div class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-lg bg-green-100 text-green-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <path d="M14 2v6h6"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-xs font-semibold text-gray-900">
                                        {{ $file->filename ?? basename($file->url) }}
                                    </div>
                                    @if ($file->filesize)
                                        <div class="text-[11px] text-gray-400">
                                            {{ max(1, round($file->filesize / 1024)) }} KB
                                        </div>
                                    @endif
                                </div>
                                <svg class="h-4 w-4 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Tags --}}
                @if ($post->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 my-4">
                        @foreach ($post->tags as $tag)
                            <span class="text-xs bg-green-50 text-green-600 px-2.5 py-1 rounded-full">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                {{-- Footer: Actions --}}
                <div class="flex items-center justify-between px-3.5 pt-3 border-t border-gray-100">

                    {{-- Left: Like + Comment --}}
                    <div class="flex items-center gap-1">

                        {{-- Like --}}
                        @auth
                            <?php $liked = $post->likes->contains('user_id', auth()->id()); ?>
                            <form method="POST" action="{{ route('posts.like', $post) }}">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 {{ $liked ? 'text-green-600' : 'text-gray-500' }} hover:bg-gray-100 hover:text-green-600 transition-all">
                                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24"
                                        fill="{{ $liked ? 'currentColor' : 'none' }}"
                                        stroke="currentColor"
                                        stroke-width="1.9">
                                        <path d="M7 10v10"/>
                                        <path d="M15 5.5 14 10h5.2a2 2 0 0 1 2 2.3l-.8 5.4A4 4 0 0 1 16.4 21H7V10h2.4a2 2 0 0 0 1.8-1.1L14 3.5a1 1 0 0 1 1.9.6z"/>
                                    </svg>
                                    <span class="text-xs font-semibold">{{ $post->likes_count }}</span>
                                </button>
                            </form>
                        @else
                            <button type="button"
                                    onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                    class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-green-600 transition-all">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                    <path d="M7 10v10"/>
                                    <path d="M15 5.5 14 10h5.2a2 2 0 0 1 2 2.3l-.8 5.4A4 4 0 0 1 16.4 21H7V10h2.4a2 2 0 0 0 1.8-1.1L14 3.5a1 1 0 0 1 1.9.6z"/>
                                </svg>
                                <span class="text-xs font-semibold">{{ $post->likes_count }}</span>
                            </button>
                        @endauth

                        {{-- Comment --}}
                        <a href="{{ route('posts.show', $post) }}#comments"
                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-green-600 transition-all">
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
                            @php $isBookmarked = $post->bookmarks->isNotEmpty(); @endphp
                            <form method="POST" action="{{ route('posts.bookmark', $post) }}">
                                @csrf
                                <button type="submit"
                                        title="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark' }}"
                                        class="rounded-lg px-2.5 py-1.5 transition-all {{ $isBookmarked ? 'text-green-600' : 'text-gray-500 hover:bg-gray-100 hover:text-green-600' }}">
                                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24"
                                        fill="{{ $isBookmarked ? 'currentColor' : 'none' }}"
                                        stroke="currentColor" stroke-width="1.9">
                                        <path d="M6 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18l-6-3-6 3z"/>
                                    </svg>
                                </button>
                            </form>
                        @else
                            <button type="button"
                                    onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                    class="rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-green-600 transition-all">
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
                                    class="rounded-lg px-2.5 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-green-600 transition-all"
                                    :title="copied ? 'Link copied!' : 'Copy link'">
                                <svg x-show="!copied" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                    <path d="M22 2 11 13"/>
                                    <path d="M22 2 15 22 11 13 2 9l20-7z"/>
                                </svg>
                                <svg x-show="copied" class="h-[18px] w-[18px] text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                            </button>
                        </div>

                    </div>
                </div>
            </article>

            {{-- Comments Section --}}
            <section id="comments" class="bg-white border border-gray-200 rounded-xl p-6">
                @include('feed._comments', ['post' => $post])
            </section>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4">
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h3 class="font-semibold text-gray-900 mb-1">
                    {{ $post->user->display_name }}
                </h3>
                <p class="text-sm text-gray-500 mb-3">
                    {{ $post->user->bio ?? 'No bio yet.' }}
                </p>
                <a href="{{ route('profile.show', $post->user->username) }}"
                   class="text-sm text-green-600 hover:underline">
                    View Profile →
                </a>
            </div>
        </aside>

    </div>
</x-app-layout>