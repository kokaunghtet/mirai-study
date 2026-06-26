<x-app-layout :title="'Notifications'">
    <div class="px-4 lg:px-8 max-w-2xl mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-content">Notifications</h1>
                @php $unreadTotal = $notifications->getCollection()->filter(fn($n) => !$n->isRead())->count(); @endphp
                @if ($unreadTotal > 0)
                    <p class="text-xs text-muted mt-0.5">{{ $unreadTotal }} unread</p>
                @endif
            </div>
            @if ($notifications->total() > 0)
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="text-xs font-medium text-muted hover:text-accent transition-colors cursor-pointer px-3 py-1.5 rounded-lg border border-line hover:border-accent/30">
                            Mark all read
                        </button>
                    </form>
                    <form method="POST" action="{{ route('notifications.destroy-all') }}"
                          data-confirm="Delete all notifications? This cannot be undone."
                          data-confirm-title="Delete all"
                          data-confirm-label="Delete all">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-xs font-medium text-red-500 hover:text-red-600 transition-colors cursor-pointer px-3 py-1.5 rounded-lg border border-red-200 hover:border-red-300 dark:border-red-500/30 dark:hover:border-red-500/50">
                            Delete all
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- List --}}
        <div class="space-y-2">
        @forelse ($notifications as $notification)
            @php
                $typeConfig = match($notification->type) {
                    'like_post'       => ['icon' => 'thumbs-up',     'color' => 'text-red-500',   'bg' => 'bg-red-500/10'],
                    'comment_post'    => ['icon' => 'message-circle', 'color' => 'text-blue-500',  'bg' => 'bg-blue-500/10'],
                    'follow_user'     => ['icon' => 'user-plus',      'color' => 'text-green-500', 'bg' => 'bg-green-500/10'],
                    'report_reviewed' => ['icon' => 'flag',           'color' => 'text-orange-500','bg' => 'bg-orange-500/10'],
                    default           => ['icon' => 'bell',           'color' => 'text-muted',     'bg' => 'bg-surface-muted'],
                };
            @endphp

            <div class="flex gap-3.5 px-4 py-4 rounded-xl border transition-colors
                {{ $notification->isRead()
                    ? 'bg-surface border-line'
                    : 'bg-surface border-accent/40 shadow-sm shadow-accent/5' }}">

                {{-- Avatar --}}
                <div class="shrink-0">
                    @if ($notification->sender?->profile_image)
                        <img src="{{ $notification->sender->profile_image }}"
                             alt="{{ $notification->sender->display_name }}"
                             class="w-10 h-10 rounded-full object-cover">
                    @elseif ($notification->sender)
                        <div class="w-10 h-10 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold text-sm select-none">
                            {{ strtoupper(substr($notification->sender->display_name, 0, 1)) }}
                        </div>
                    @else
                        <div class="w-10 h-10 rounded-full bg-surface-muted flex items-center justify-center">
                            <i data-lucide="bell" class="w-4 h-4 text-muted"></i>
                        </div>
                    @endif
                </div>

                {{-- Body --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-content leading-snug">
                            {{ $notification->title }}
                        </p>
                        <div class="flex items-center gap-2 shrink-0 mt-0.5">
                            @if (!$notification->isRead())
                                <span class="w-2 h-2 rounded-full bg-accent flex-shrink-0"></span>
                            @endif
                            <span class="text-[11px] text-muted whitespace-nowrap">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                            <form method="POST"
                                  action="{{ route('notifications.destroy', $notification) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        title="Delete"
                                        class="text-muted hover:text-red-500 transition-colors cursor-pointer">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <p class="text-xs text-muted mt-0.5 leading-relaxed line-clamp-2">
                        {{ $notification->content }}
                    </p>

                    {{-- Actions row: type icon + View + Dismiss all on same line --}}
                    <div class="flex items-center gap-3 mt-2">
                        {{-- Type icon --}}
                        <span class="w-5 h-5 rounded-full {{ $typeConfig['bg'] }} flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $typeConfig['icon'] }}" class="w-3 h-3 {{ $typeConfig['color'] }}"></i>
                        </span>

                        @if ($notification->url)
                            <a href="{{ $notification->url }}"
                               class="text-xs font-semibold text-accent hover:text-accent-strong transition-colors leading-none">
                                View
                            </a>
                        @endif

                        @if (!$notification->isRead())
                            <form method="POST"
                                  action="{{ route('notifications.read', $notification) }}"
                                  class="inline-flex items-center">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="text-xs text-muted hover:text-content transition-colors cursor-pointer leading-none">
                                    Mark as Read
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-20">
                <div class="w-14 h-14 rounded-full bg-surface-muted border border-line flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="bell" class="w-6 h-6 text-muted"></i>
                </div>
                <p class="text-sm font-medium text-content">All caught up</p>
                <p class="text-xs text-muted mt-1">No notifications yet</p>
            </div>
        @endforelse
        </div>

        {{-- Pagination --}}
        @if ($notifications->hasPages())
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif

    </div>
</x-app-layout>
