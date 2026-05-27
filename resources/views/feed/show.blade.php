<x-app-layout>
    <x-slot name="title">{{ $post->title ?? 'Post' }} — MiraiStudy</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Post --}}
            <article class="bg-white border border-gray-200 rounded-xl p-6">

                {{-- Author --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                            {{ strtoupper(substr($post->user->display_name, 0, 1)) }}
                        </div>
                        <div>
                            <a href="{{ route('profile.show', $post->user->username) }}"
                               class="font-semibold text-gray-900 hover:text-indigo-600">
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
                                   class="text-sm text-gray-500 hover:text-indigo-600">Edit</a>
                                <form method="POST" action="{{ route('posts.destroy', $post) }}"
                                      onsubmit="return confirm('Delete this post?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-500 hover:text-red-700">
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
                <div class="text-gray-700 leading-relaxed whitespace-pre-wrap">
                    {{ $post->content }}
                </div>

                {{-- Tags --}}
                @if ($post->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach ($post->tags as $tag)
                            <span class="text-xs bg-indigo-50 text-indigo-600 px-2.5 py-1 rounded-full">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center gap-6 mt-6 pt-4 border-t border-gray-100 text-sm text-gray-500">

                    {{-- Like --}}
                    @auth
                        <form method="POST" action="{{ route('posts.like', $post) }}">
                            @csrf
                            <button type="submit"
                                    class="flex items-center gap-2 hover:text-red-500 transition">
                                <span>♥</span>
                                <span>{{ $post->likes_count }} Likes</span>
                            </button>
                        </form>
                    @else
                        <button onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                class="flex items-center gap-2 hover:text-red-500 transition">
                            <span>♥</span>
                            <span>{{ $post->likes_count }} Likes</span>
                        </button>
                    @endauth

                    {{-- Comment count --}}
                    <span class="flex items-center gap-2">
                        <span>💬</span>
                        <span>{{ $post->comments_count }} Comments</span>
                    </span>

                    {{-- Bookmark --}}
                    @auth
                        <form method="POST" action="{{ route('posts.bookmark', $post) }}">
                            @csrf
                            <button type="submit"
                                    class="flex items-center gap-2 hover:text-yellow-500 transition">
                                🔖 Bookmark
                            </button>
                        </form>
                    @else
                        <button onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                class="flex items-center gap-2 hover:text-yellow-500 transition">
                            🔖 Bookmark
                        </button>
                    @endauth

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
                        <textarea name="content" rows="3"
                                  placeholder="Write a comment..."
                                  class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                  required>{{ old('content') }}</textarea>
                        @error('content')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                class="mt-2 bg-indigo-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-indigo-700">
                            Post Comment
                        </button>
                    </form>
                @else
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg text-sm text-gray-500 text-center">
                        <button onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                class="text-indigo-600 font-medium hover:underline">
                            Log in
                        </button>
                        to join the conversation.
                    </div>
                @endauth

                {{-- Comments List --}}
                <div class="space-y-5">
                    @forelse ($post->comments as $comment)
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs flex-shrink-0">
                                {{ strtoupper(substr($comment->user->display_name, 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <div class="bg-gray-50 rounded-lg px-4 py-3">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ $comment->user->display_name }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $comment->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-700">{{ $comment->content }}</p>
                                </div>

                                {{-- Delete own comment --}}
                                @auth
                                    @if (auth()->id() === $comment->user_id)
                                        <form method="POST"
                                              action="{{ route('comments.destroy', $comment) }}"
                                              class="mt-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-xs text-red-400 hover:text-red-600">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                @endauth

                                {{-- Replies --}}
                                @if ($comment->replies->isNotEmpty())
                                    <div class="mt-3 space-y-3 pl-4 border-l-2 border-gray-100">
                                        @foreach ($comment->replies as $reply)
                                            <div class="flex gap-2">
                                                <div class="w-7 h-7 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-500 font-bold text-xs flex-shrink-0">
                                                    {{ strtoupper(substr($reply->user->display_name, 0, 1)) }}
                                                </div>
                                                <div class="flex-1 bg-gray-50 rounded-lg px-3 py-2">
                                                    <div class="flex items-center justify-between mb-0.5">
                                                        <span class="text-xs font-semibold text-gray-900">
                                                            {{ $reply->user->display_name }}
                                                        </span>
                                                        <span class="text-xs text-gray-400">
                                                            {{ $reply->created_at->diffForHumans() }}
                                                        </span>
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
                                               class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                        <button type="submit"
                                                class="text-xs bg-gray-100 hover:bg-indigo-100 text-gray-700 hover:text-indigo-700 px-3 py-1.5 rounded-lg transition">
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
                   class="text-sm text-indigo-600 hover:underline">
                    View Profile →
                </a>
            </div>
        </aside>

    </div>
</x-app-layout>