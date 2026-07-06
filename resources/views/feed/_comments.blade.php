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
                {{-- Avatar --}}
                <div class="w-8 h-8 shrink-0 rounded-full overflow-hidden bg-accent/15 flex items-center justify-center">
                    @if ($comment->user->profile_image)
                        <img src="{{ $comment->user->profile_image }}"
                             alt="{{ $comment->user->display_name }}"
                             loading="lazy"
                             class="h-full w-full object-cover">
                    @else
                        <span class="text-sm font-bold text-accent">
                            {{ strtoupper(substr($comment->user->display_name, 0, 1)) }}
                        </span>
                    @endif
                </div>

                <div class="flex-1">
                    {{-- Card --}}
                    <div class="bg-surface-muted rounded-lg px-4 py-3">
                        {{-- Header: name + timestamp left, meatball right --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-sm font-semibold text-content truncate max-w-[10rem] lg:max-w-none">{{ $comment->user->display_name }}</span>
                                <span class="text-xs text-muted shrink-0">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            @if (auth()->id() === $comment->user_id || ! $comment->user->isAdmin())
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
                                            <form method="POST" action="{{ route('comments.destroy', $comment) }}"
                                                  data-confirm="Delete this comment?" data-loading>
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">Delete</button>
                                            </form>
                                        @elseif (auth()->user()->isAdmin() || auth()->user()->isModerator())
                                            <form method="POST" action="{{ route('comments.destroy', $comment) }}"
                                                  data-confirm="Remove this comment?" data-loading>
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">Remove</button>
                                            </form>
                                        @else
                                            <button type="button"
                                                    @click="$dispatch('open-report', { type: 'comment', id: {{ $comment->id }} }); open = false"
                                                    class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">Report</button>
                                        @endif
                                    </div>
                                </div>
                            @endauth
                            @endif
                        </div>

                        <hr class="border-line my-2">

                        <p class="text-sm text-content">{{ $comment->content }}</p>
                    </div>

                    {{-- Like — outside card, right-aligned --}}
                    @auth
                        <?php $commentLiked = $comment->likes->contains('user_id', auth()->id()); ?>
                        <div class="flex justify-end mt-1"
                             x-data="{
                                liked: {{ $commentLiked ? 'true' : 'false' }},
                                count: {{ $comment->likes->count() }},
                                busy: false,
                                async toggle() {
                                    if (this.busy) return;
                                    this.busy = true;
                                    try {
                                        const res = await fetch('{{ route('comments.like', $comment) }}', {
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
                                    :class="liked ? 'text-accent font-semibold' : 'text-muted hover:text-accent'"
                                    class="flex items-center gap-1 text-xs transition-all px-1">
                                <i data-lucide="thumbs-up" class="h-3.5 w-3.5" :fill="liked ? 'currentColor' : 'none'"></i>
                                <span x-show="count > 0" x-text="count"></span>
                            </button>
                        </div>
                    @else
                        <div class="flex justify-end mt-1">
                            <button type="button"
                                    onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                    class="flex items-center gap-1 text-xs text-muted hover:text-accent transition-all px-1">
                                <i data-lucide="thumbs-up" class="h-3.5 w-3.5"></i>
                                @if ($comment->likes->count() > 0)
                                    <span>{{ $comment->likes->count() }}</span>
                                @endif
                            </button>
                        </div>
                    @endauth

                    {{-- Replies --}}
                    @if ($comment->replies->isNotEmpty())
                        <div class="mt-3 space-y-3 pl-4 border-l-2 border-line">
                            @foreach ($comment->replies as $reply)
                                <div class="flex gap-2">
                                    {{-- Avatar --}}
                                    <div class="w-7 h-7 shrink-0 rounded-full overflow-hidden bg-accent/15 flex items-center justify-center">
                                        @if ($reply->user->profile_image)
                                            <img src="{{ $reply->user->profile_image }}"
                                                 alt="{{ $reply->user->display_name }}"
                                                 loading="lazy"
                                                 class="h-full w-full object-cover">
                                        @else
                                            <span class="text-xs font-bold text-accent">
                                                {{ strtoupper(substr($reply->user->display_name, 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex-1">
                                        {{-- Card --}}
                                        <div class="bg-surface-muted rounded-lg px-3 py-2">
                                            {{-- Header: name + timestamp left, meatball right --}}
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="text-xs font-semibold text-content truncate max-w-[10rem] lg:max-w-none">{{ $reply->user->display_name }}</span>
                                                    <span class="text-xs text-muted shrink-0">{{ $reply->created_at->diffForHumans() }}</span>
                                                </div>
                                                @if (auth()->id() === $reply->user_id || ! $reply->user->isAdmin())
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
                                                                <form method="POST" action="{{ route('comments.destroy', $reply) }}"
                                                                      data-confirm="Delete this reply?" data-loading>
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">Delete</button>
                                                                </form>
                                                            @elseif (auth()->user()->isAdmin() || auth()->user()->isModerator())
                                                                <form method="POST" action="{{ route('comments.destroy', $reply) }}"
                                                                      data-confirm="Remove this reply?" data-loading>
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">Remove</button>
                                                                </form>
                                                            @else
                                                                <button type="button"
                                                                        @click="$dispatch('open-report', { type: 'comment', id: {{ $reply->id }} }); open = false"
                                                                        class="w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 transition">Report</button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endauth
                                                @endif
                                            </div>

                                            <hr class="border-line my-1.5">

                                            <p class="text-xs text-content">{{ $reply->content }}</p>
                                        </div>

                                        {{-- Like — outside card, right-aligned --}}
                                        @auth
                                            <?php $replyLiked = $reply->likes->contains('user_id', auth()->id()); ?>
                                            <div class="flex justify-end mt-1"
                                                 x-data="{
                                                    liked: {{ $replyLiked ? 'true' : 'false' }},
                                                    count: {{ $reply->likes->count() }},
                                                    busy: false,
                                                    async toggle() {
                                                        if (this.busy) return;
                                                        this.busy = true;
                                                        try {
                                                            const res = await fetch('{{ route('comments.like', $reply) }}', {
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
                                                        :class="liked ? 'text-accent font-semibold' : 'text-muted hover:text-accent'"
                                                        class="flex items-center gap-1 text-xs transition-all px-1">
                                                    <i data-lucide="thumbs-up" class="h-3.5 w-3.5" :fill="liked ? 'currentColor' : 'none'"></i>
                                                    <span x-show="count > 0" x-text="count"></span>
                                                </button>
                                            </div>
                                        @else
                                            <div class="flex justify-end mt-1">
                                                <button type="button"
                                                        onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                                        class="flex items-center gap-1 text-xs text-muted hover:text-accent transition-all px-1">
                                                    <i data-lucide="thumbs-up" class="h-3.5 w-3.5"></i>
                                                    @if ($reply->likes->count() > 0)
                                                        <span>{{ $reply->likes->count() }}</span>
                                                    @endif
                                                </button>
                                            </div>
                                        @endauth
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
                                   class="flex-1 bg-surface-muted text-content border border-line rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-accent/40" required>
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
