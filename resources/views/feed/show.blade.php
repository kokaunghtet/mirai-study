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
            <section class="bg-white border border-gray-200 rounded-xl p-6">
                <h2 class="font-semibold text-gray-900 mb-5">
                    Comments ({{ $post->comments_count }})
                </h2>

                {{-- Comment Form --}}
                @auth
                    <form method="POST" action="{{ route('comments.store', $post) }}" class="mb-6">
                        @csrf
                        <textarea name="content" rows="2"
                                  placeholder="Write a comment..."
                                  class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-green-300"
                                  required>{{ old('content') }}</textarea>
                        @error('content')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                class="mt-2 bg-green-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-green-700">
                            Post Comment
                        </button>
                    </form>
                @else
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg text-sm text-gray-500 text-center">
                        <button onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                class="text-green-600 font-medium hover:underline">
                            Log in
                        </button>
                        to join the conversation.
                    </div>
                @endauth

                {{-- Comments List --}}
                <div class="space-y-5">
                    @forelse ($post->comments as $comment)
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-sm shrink-0">
                            @if ($comment->user->profile_image)
                                <img src="{{ $comment->user->profile_image }}"
                                    alt="{{ $comment->user->display_name }}"
                                    loading="lazy"
                                    class="h-full w-full rounded-full object-cover">
                            @else
                                <div class="grid h-full w-full place-items-center rounded-full bg-green-100 text-sm font-bold text-green-600">
                                    {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                            <div class="flex-1">
                                <div class="bg-gray-50 rounded-lg px-4 py-3">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-900">
                                                {{ $comment->user->display_name }}
                                            </span>
                                            <span class="text-xs text-gray-400">
                                                {{ $comment->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        {{-- Delete own comment --}}
                                        @auth
                                            @if (auth()->id() === $comment->user_id)
                                                <form method="POST"
                                                      action="{{ route('comments.destroy', $comment) }}"
                                                      onsubmit="return confirm('Delete this comment?')"
                                                      class="inline-flex items-center">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="text-gray-400 hover:text-red-500 transition-colors"
                                                            title="Delete comment">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M3 6h18"/>
                                                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>
                                    <p class="text-sm text-gray-700">{{ $comment->content }}</p>
                                </div>

                                {{-- Replies --}}
                                @if ($comment->replies->isNotEmpty())
                                    <div class="mt-3 space-y-3 pl-4 border-l-2 border-gray-100">
                                        @foreach ($comment->replies as $reply)
                                            <div class="flex gap-2">
                                                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-sm shrink-0">
                                                    @if ($reply->user->profile_image)
                                                        <img src="{{ $reply->user->profile_image }}"
                                                            alt="{{ $reply->user->display_name }}"
                                                            loading="lazy"
                                                            class="h-full w-full rounded-full object-cover">
                                                    @else
                                                        <div class="grid h-full w-full place-items-center rounded-full bg-green-100 text-sm font-bold text-green-600">
                                                            {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-1 bg-gray-50 rounded-lg px-3 py-2">
                                                    <div class="flex items-center justify-between mb-0.5">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-semibold text-gray-900">
                                                                {{ $reply->user->display_name }}
                                                            </span>
                                                            <span class="text-xs text-gray-400">
                                                                {{ $reply->created_at->diffForHumans() }}
                                                            </span>
                                                        </div>
                                                        {{-- Delete own reply --}}
                                                        @auth
                                                            @if (auth()->id() === $reply->user_id)
                                                                <form method="POST"
                                                                      action="{{ route('comments.destroy', $reply) }}"
                                                                      onsubmit="return confirm('Delete this reply?')"
                                                                      class="flex items-center">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                            class="text-gray-400 hover:text-red-500 transition-colors"
                                                                            title="Delete reply">
                                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                            <path d="M3 6h18"/>
                                                                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                                        </svg>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        @endauth
                                                    </div>
                                                    <p class="text-xs text-gray-700">{{ $reply->content }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Reply form --}}
                                @auth
                                    <form method="POST"
                                          action="{{ route('comments.store', $post) }}"
                                          class="mt-2 flex gap-2">
                                        @csrf
                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                        <input type="text" name="content"
                                               placeholder="Reply..."
                                               class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-green-300">
                                        <button type="submit"
                                                class="text-xs bg-gray-100 hover:bg-green-100 text-gray-700 hover:text-green-700 px-3 py-1.5 rounded-lg transition">
                                            Reply
                                        </button>
                                    </form>
                                @endauth
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">
                            No comments yet. Be the first to comment.
                        </p>
                    @endforelse
                </div>
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