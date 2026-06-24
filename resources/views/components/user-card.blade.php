@props(['user'])

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
</div>
