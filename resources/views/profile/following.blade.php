<x-app-layout>
    <x-slot name="title">{{ $user->display_name }} is Following — MiraiStudy</x-slot>

    <div class="max-w-[560px] mx-auto"
         x-data="{ followingCount: {{ $user->following_count }}, remaining: {{ $following->count() }} }"
         @following-decremented.window="followingCount = Math.max(0, followingCount - 1); remaining = Math.max(0, remaining - 1)">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('profile.show', $user->username) }}"
               class="grid h-9 w-9 place-items-center rounded-lg text-gray-400 hover:bg-gray-100 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-gray-900">Following</h1>
                <p class="text-xs text-gray-400">{{ $user->display_name }} follows <span x-text="followingCount.toLocaleString()"></span> people</p>
            </div>
        </div>

        {{-- List --}}
        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
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
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-sm shrink-0">
                                {{ strtoupper(substr($followed->display_name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-gray-900 truncate">
                                {{ $followed->display_name }}
                            </div>
                            <div class="text-xs text-gray-400 truncate">{{'@'.$followed->username }}</div>
                        </div>
                    </a>

                    @auth
                        @if (auth()->id() !== $followed->id)
                            <div x-data="{
                                    following: true,
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
                                            if (!data.following) {
                                                window.dispatchEvent(new Event('following-decremented'));
                                                this.$el.closest('.follow-row').style.transition = 'opacity 0.3s, max-height 0.3s';
                                                this.$el.closest('.follow-row').style.opacity = '0';
                                                this.$el.closest('.follow-row').style.maxHeight = '0';
                                                this.$el.closest('.follow-row').style.overflow = 'hidden';
                                                this.$el.closest('.follow-row').style.padding = '0';
                                                setTimeout(() => this.$el.closest('.follow-row')?.remove(), 350);
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
                                        :class="hovered
                                            ? 'border-red-200 text-red-600 bg-red-50 hover:bg-red-100'
                                            : 'border-gray-200 text-gray-700 bg-white hover:bg-gray-50'"
                                        class="rounded-lg border px-3 py-1.5 text-[12px] font-bold transition-all active:scale-95">
                                    <span x-text="hovered ? 'Unfollow' : 'Following'"></span>
                                </button>
                            </div>
                        @endif
                    @endauth
                </div>
            @endforeach

            <div x-show="remaining === 0" x-cloak class="flex flex-col items-center justify-center py-16 text-center">
                <p class="text-sm font-semibold text-gray-400">Not following anyone yet</p>
            </div>
        </div>

        @if ($following->hasPages())
            <div class="mt-4">{{ $following->links() }}</div>
        @endif
    </div>
</x-app-layout>