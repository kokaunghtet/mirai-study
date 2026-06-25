@props(['user'])

@php
    $authUser = auth()->user();
    $isSelf = $authUser && $authUser->id === $user->id;
    $isFollowing = !$isSelf && $authUser
        ? $authUser->following()->where('following_id', $user->id)->exists()
        : false;
@endphp

<div class="bg-surface border border-line rounded-2xl p-4 flex items-center gap-4">
    <a href="{{ route('profile.show', $user->username) }}" class="shrink-0">
        <div class="grid h-14 w-14 place-items-center overflow-hidden rounded-full border border-line">
            @if ($user->profile_image)
                <img src="{{ $user->profile_image }}"
                     alt="{{ $user->display_name }}"
                     class="h-full w-full object-cover">
            @else
                <div class="grid h-full w-full place-items-center rounded-full bg-accent/15 text-lg font-bold text-accent">
                    {{ strtoupper(substr($user->display_name, 0, 1)) }}
                </div>
            @endif
        </div>
    </a>

    <div class="flex-1 min-w-0">
        <a href="{{ route('profile.show', $user->username) }}"
           class="block font-bold text-content hover:text-accent transition-colors truncate">
            {{ $user->display_name }}
        </a>
        <div class="text-xs text-muted">{{'@'.$user->username }}</div>
        <div class="flex gap-4 mt-2 text-xs text-muted">
            <span><span class="font-bold text-content">{{ $user->posts_count }}</span> Posts</span>
            <span><span class="font-bold text-content">{{ $user->followers_count }}</span> Followers</span>
            <span><span class="font-bold text-content">{{ $user->following_count }}</span> Following</span>
        </div>
    </div>

    @if (!$isSelf)
        @auth
            <div x-data="{
                    following: {{ $isFollowing ? 'true' : 'false' }},
                    loading: false,
                    async toggle() {
                        if (this.loading) return;
                        this.loading = true;
                        try {
                            const res = await fetch('{{ route('users.follow', $user) }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json',
                                }
                            });
                            const data = await res.json();
                            this.following = data.following;
                        } finally {
                            this.loading = false;
                        }
                    }
                }" class="shrink-0">
                <button type="button"
                        @click="toggle()"
                        :disabled="loading"
                        :class="following
                            ? 'bg-surface border-line text-content hover:border-red-200 hover:text-red-600 hover:bg-red-50'
                            : 'bg-accent border-transparent text-white hover:bg-accent-strong'"
                        class="rounded-lg border px-4 py-1.5 text-[13px] font-bold transition-all active:scale-95">
                    <span x-text="following ? 'Following' : 'Follow'"></span>
                </button>
            </div>
        @else
            <button type="button"
                    onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                    class="shrink-0 rounded-lg border border-transparent bg-accent px-4 py-1.5 text-[13px] font-bold text-white hover:bg-accent-strong transition-all active:scale-95">
                Follow
            </button>
        @endauth
    @endif
</div>
