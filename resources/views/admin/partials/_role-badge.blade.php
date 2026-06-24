@props(['role'])

@if ($role === 'admin')
    <span class="rounded-full bg-accent px-2 py-0.5 text-[10px] font-bold text-white">Admin</span>
@elseif ($role === 'moderator')
    <span class="rounded-full border border-line bg-surface-muted px-2 py-0.5 text-[10px] font-bold text-muted">Mod</span>
@else
    <span class="text-xs text-muted">User</span>
@endif
