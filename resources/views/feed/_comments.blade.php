{{-- _comments.blade.php — reusable comments section for a single $post.
     Used by feed/show.blade.php (full page) and the feed comment drawer (AJAX). --}}
<div data-comments-root data-count="{{ $post->comments_count }}">
    <h2 class="font-semibold text-content mb-5">
        Comments ({{ $post->comments_count }})
    </h2>

    {{-- Comment Form --}}
    @auth
        <form method="POST" action="{{ route('comments.store', $post) }}" data-loading class="mb-6">
            @csrf
            <textarea name="content" rows="2"
                      placeholder="Write a comment… (Enter to post, Shift+Enter for new line)"
                      class="w-full bg-surface-muted text-content border border-line rounded-lg px-4 py-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-accent/40"
                      required
                      @keydown.enter.prevent="if (!$event.shiftKey) $el.closest('form').requestSubmit()">{{ old('content') }}</textarea>
            @error('content')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <button type="submit" data-loading-text="Posting…"
                    class="mt-2 bg-gradient-to-tr from-accent-from to-accent-to text-white text-sm font-medium px-5 py-2 rounded-lg hover:opacity-90">
                Post Comment
            </button>
        </form>
    @else
        <div class="mb-6 p-4 bg-surface-muted rounded-lg text-sm text-muted text-center">
            <button onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                    class="text-accent font-medium hover:underline">
                Log in
            </button>
            to join the conversation.
        </div>
    @endauth

    {{-- Comments List --}}
    <div class="space-y-5">
        @forelse ($post->comments as $comment)
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold text-sm shrink-0">
                @if ($comment->user->profile_image)
                    <img src="{{ $comment->user->profile_image }}"
                        alt="{{ $comment->user->display_name }}"
                        loading="lazy"
                        class="h-full w-full rounded-full object-cover">
                @else
                    <div class="grid h-full w-full place-items-center rounded-full bg-accent/15 text-sm font-bold text-accent">
                        {{ strtoupper(substr($comment->user->display_name, 0, 1)) }}
                    </div>
                @endif
            </div>
                <div class="flex-1">
                    <div class="bg-surface-muted rounded-lg px-4 py-3">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-content">
                                    {{ $comment->user->display_name }}
                                </span>
                                <span class="text-xs text-muted">
                                    {{ $comment->created_at->diffForHumans() }}
                                </span>
                            </div>
                            {{-- More menu: Delete own / Report others' --}}
                            @auth
                                <div class="relative shrink-0" x-data="{ open: false }">
                                    <button @click="open = !open" @click.outside="open = false"
                                            type="button"
                                            class="grid h-7 w-7 place-items-center rounded-lg text-muted hover:bg-surface hover:text-content transition-colors">
                                        <i data-lucide="ellipsis" class="h-4 w-4"></i>
                                    </button>
                                    <div x-show="open"
                                         class="absolute right-0 top-8 z-50 w-32 overflow-hidden rounded-xl bg-surface py-1 text-[13px] font-semibold shadow-lg border border-line">
                                        @if (auth()->id() === $comment->user_id)
                                            <form method="POST"
                                                  action="{{ route('comments.destroy', $comment) }}"
                                                  data-confirm="Delete this comment?"
                                                  data-loading>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">
                                                    Delete
                                                </button>
                                            </form>
                                        @else
                                            <button type="button"
                                                    @click="$dispatch('open-report', { type: 'comment', id: {{ $comment->id }} }); open = false"
                                                    class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">
                                                Report
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endauth
                        </div>
                        <p class="text-sm text-content">{{ $comment->content }}</p>
                    </div>

                    {{-- Replies --}}
                    @if ($comment->replies->isNotEmpty())
                        <div class="mt-3 space-y-3 pl-4 border-l-2 border-line">
                            @foreach ($comment->replies as $reply)
                                <div class="flex gap-2">
                                    <div class="w-8 h-8 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold text-sm shrink-0">
                                        @if ($reply->user->profile_image)
                                            <img src="{{ $reply->user->profile_image }}"
                                                alt="{{ $reply->user->display_name }}"
                                                loading="lazy"
                                                class="h-full w-full rounded-full object-cover">
                                        @else
                                            <div class="grid h-full w-full place-items-center rounded-full bg-accent/15 text-sm font-bold text-accent">
                                                {{ strtoupper(substr($reply->user->display_name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 bg-surface-muted rounded-lg px-3 py-2">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-semibold text-content">
                                                    {{ $reply->user->display_name }}
                                                </span>
                                                <span class="text-xs text-muted">
                                                    {{ $reply->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            {{-- More menu: Delete own / Report others' --}}
                                            @auth
                                                <div class="relative shrink-0" x-data="{ open: false }">
                                                    <button @click="open = !open" @click.outside="open = false"
                                                            type="button"
                                                            class="grid h-6 w-6 place-items-center rounded-lg text-muted hover:bg-surface hover:text-content transition-colors">
                                                        <i data-lucide="ellipsis" class="h-3.5 w-3.5"></i>
                                                    </button>
                                                    <div x-show="open"
                                                         class="absolute right-0 top-7 z-50 w-32 overflow-hidden rounded-xl bg-surface py-1 text-[13px] font-semibold shadow-lg border border-line">
                                                        @if (auth()->id() === $reply->user_id)
                                                            <form method="POST"
                                                                  action="{{ route('comments.destroy', $reply) }}"
                                                                  data-confirm="Delete this reply?"
                                                                  data-loading>
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        @else
                                                            <button type="button"
                                                                    @click="$dispatch('open-report', { type: 'comment', id: {{ $reply->id }} }); open = false"
                                                                    class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">
                                                                Report
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endauth
                                        </div>
                                        <p class="text-xs text-content">{{ $reply->content }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Reply form --}}
                    @auth
                        <form method="POST"
                              action="{{ route('comments.store', $post) }}"
                              data-loading
                              class="mt-2 flex gap-2">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                            <input type="text" name="content"
                                   placeholder="Reply..."
                                   class="flex-1 bg-surface-muted text-content border border-line rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-accent/40">
                            <button type="submit"
                                    class="text-xs bg-surface-muted hover:bg-accent/15 text-content hover:text-accent px-3 py-1.5 rounded-lg transition">
                                Reply
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        @empty
            <p class="text-sm text-muted text-center py-4">
                No comments yet. Be the first to comment.
            </p>
        @endforelse
    </div>
</div>
