<x-app-layout>
    <x-slot name="title">{{ $user->display_name }} is Following — MiraiStudy</x-slot>

    <div class="max-w-[560px] mx-auto"
         x-data="{ followingCount: {{ $user->following_count }}, remaining: {{ $following->count() }} }"
         @following-decremented.window="followingCount = Math.max(0, followingCount - 1); remaining = Math.max(0, remaining - 1)">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('profile.show', $user->username) }}"
               class="grid h-9 w-9 place-items-center rounded-lg text-muted hover:bg-surface-muted transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-content">Following</h1>
                <p class="text-xs text-muted">{{ $user->display_name }} follows <span x-text="followingCount.toLocaleString()"></span> people</p>
            </div>
        </div>

        {{-- List --}}
        <div class="bg-surface rounded-2xl border border-line divide-y divide-line overflow-hidden">
            @foreach ($following as $followed)
                <div class="follow-row flex items-center justify-between px-4 py-3.5">
                    <a href="{{ route('profile.show', $followed->username) }}"
                       class="flex items-center gap-3 min-w-0 flex-1">
                        @if ($followed->profile_image)
                            <img src="{{ $followed->profile_image }}"
                                 alt="{{ $followed->display_name }}"
                                 loading="lazy"
                                 class="w-10 h-10 rounded-full object-cover shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold text-sm shrink-0">
                                {{ strtoupper(substr($followed->display_name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-content truncate">
                                {{ $followed->display_name }}
                            </div>
                            <div class="text-xs text-muted truncate">{{'@'.$followed->username }}</div>
                        </div>
                    </a>

                    @auth
                        @if (auth()->id() !== $followed->id)
                            @php $isFollowing = in_array($followed->id, $authFollowingIds); @endphp
                            <div x-data="{
                                    following: {{ $isFollowing ? 'true' : 'false' }},
                                    isOwnList: {{ auth()->id() === $user->id ? 'true' : 'false' }},
                                    hovered: false,
                                    loading: false,
                                    async toggle() {
                                        if (this.loading) return;
                                        this.loading = true;
                                        try {
                                            const res = await fetch('{{ route('users.follow', $followed) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                    'Accept': 'application/json',
                                                }
                                            });
                                            const data = await res.json();
                                            this.following = data.following;
                                            if (!data.following && this.isOwnList) {
                                                window.dispatchEvent(new Event('following-decremented'));
                                                const row = this.$el.closest('.follow-row');
                                                row.style.transition = 'opacity 0.3s, max-height 0.3s';
                                                row.style.opacity = '0';
                                                row.style.maxHeight = '0';
                                                row.style.overflow = 'hidden';
                                                row.style.padding = '0';
                                                setTimeout(() => row?.remove(), 350);
                                            }
                                        } finally {
                                            this.loading = false;
                                        }
                                    }
                                }" class="shrink-0 ml-3">
                                <button type="button"
                                        @click="toggle()"
                                        @mouseenter="hovered = true"
                                        @mouseleave="hovered = false"
                                        :disabled="loading"
                                        :class="following
                                            ? (hovered ? 'border-red-200 text-red-600 bg-red-50 hover:bg-red-100' : 'border-line text-content bg-surface hover:bg-surface-muted')
                                            : 'border-transparent bg-gradient-to-tr from-accent-from to-accent-to text-white hover:opacity-90'"
                                        class="rounded-lg border px-3 py-1.5 text-[12px] font-bold transition-all active:scale-95">
                                    <span x-text="following ? (hovered ? 'Unfollow' : 'Following') : 'Follow'"></span>
                                </button>
                            </div>
                        @endif
                    @endauth
                </div>
            @endforeach

            <div x-show="remaining === 0" x-cloak class="flex flex-col items-center justify-center py-16 text-center">
                <p class="text-sm font-semibold text-muted">Not following anyone yet</p>
            </div>
        </div>

        @if ($following->hasPages())
            <div class="mt-4">{{ $following->links() }}</div>
        @endif
    </div>
</x-app-layout>