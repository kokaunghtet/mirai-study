@props(['item'])

@php
    $icon = match($item->action) {
        'report_filed'  => 'flag',
        'paper_added'   => 'file-text',
        'question_added' => 'circle-help',
        'role_changed'  => 'user-cog',
        default         => 'activity',
    };

    $description = match($item->action) {
        'report_filed'  => 'New report filed',
        'paper_added'   => 'New exam paper added',
        'question_added' => 'New question added',
        'role_changed'  => 'Role changed',
        default         => $item->action,
    };

    $actor = $item->user?->username ?? 'system';
@endphp

<li class="flex items-start gap-3 px-5 py-3">
    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-muted">
        <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5 text-muted"></i>
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-sm text-content">
            {{ $description }}
            @if ($item->user)
                <span class="text-muted">by</span>
                <span class="font-semibold text-content">{{ '@' . $actor }}</span>
            @endif
            @if ($item->action === 'role_changed' && $item->properties)
                <span class="text-muted">—</span>
                <span class="text-content">{{ $item->properties['from_role'] ?? '?' }}</span>
                <span class="text-muted">→</span>
                <span class="font-semibold text-accent">{{ $item->properties['to_role'] ?? '?' }}</span>
            @endif
        </p>
        <p class="mt-0.5 text-[10px] text-muted">{{ $item->created_at->diffForHumans() }}</p>
    </div>
</li>
