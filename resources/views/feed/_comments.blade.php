{{-- _comments.blade.php — reusable comments section for a single $post.
     Used by feed/show.blade.php (full page) and the feed comment drawer (AJAX). --}}
<div data-comments-root data-count="{{ $post->comments_count }}">
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
                        {{ strtoupper(substr($comment->user->display_name, 0, 1)) }}
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
                                            <i data-lucide="trash" class="h-4 w-4"></i>
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
                                                {{ strtoupper(substr($reply->user->display_name, 0, 1)) }}
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
                                                            <i data-lucide="trash" class="h-3.5 w-3.5"></i>
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
</div>
