<x-app-layout>
    <x-slot name="title">{{ $user->display_name }}'s Followers — MiraiStudy</x-slot>

    <div class="max-w-[560px] mx-auto"
         x-data="{ followersCount: {{ $user->followers_count }}, remaining: {{ $followers->count() }} }"
         @follower-removed.window="followersCount = Math.max(0, followersCount - 1); remaining = Math.max(0, remaining - 1)">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('profile.show', $user->username) }}"
               class="grid h-9 w-9 place-items-center rounded-lg text-gray-400 hover:bg-gray-100 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-gray-900">Followers</h1>
                <p class="text-xs text-gray-400"><span x-text="followersCount.toLocaleString()"></span> people follow {{ $user->display_name }}</p>
            </div>
        </div>

        {{-- List --}}
        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
            @foreach ($followers as $follower)
                <div class="follower-row flex items-center justify-between px-4 py-3.5">
                    <a href="{{ route('profile.show', $follower->username) }}"
                       class="flex items-center gap-3 min-w-0 flex-1">
                        @if ($follower->profile_image)
                            <img src="{{ $follower->profile_image }}"
                                 alt="{{ $follower->display_name }}"
                                 loading="lazy"
                                 class="w-10 h-10 rounded-full object-cover shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-sm shrink-0">
                                {{ strtoupper(substr($follower->display_name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-gray-900 truncate">
                                {{ $follower->display_name }}
                            </div>
                            <div class="text-xs text-gray-400 truncate">{{'@'.$follower->username }}</div>
                        </div>
                    </a>

                    {{-- Remove button — only shown to the profile owner --}}
                    @auth
                        @if (auth()->id() === $user->id && auth()->id() !== $follower->id)
                            <div x-data="{
                                    loading: false,
                                    async remove() {
                                        if (this.loading) return;
                                        this.loading = true;
                                        try {
                                            const res = await fetch('{{ route('users.remove-follower', $follower) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                    'Accept': 'application/json',
                                                }
                                            });
                                            const data = await res.json();
                                            if (data.removed) {
                                                window.dispatchEvent(new Event('follower-removed'));
                                                const row = this.$el.closest('.follower-row');
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
                                        @click="remove()"
                                        :disabled="loading"
                                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-[12px] font-bold text-red-600 hover:bg-red-100 transition-all active:scale-95">
                                    Remove
                                </button>
                            </div>
                        @endif
                    @endauth
                </div>
            @endforeach

            <div x-show="remaining === 0" x-cloak class="flex flex-col items-center justify-center py-16 text-center">
                <p class="text-sm font-semibold text-gray-400">No followers yet</p>
            </div>
        </div>

        @if ($followers->hasPages())
            <div class="mt-4">{{ $followers->links() }}</div>
        @endif
    </div>
</x-app-layout>