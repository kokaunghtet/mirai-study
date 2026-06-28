@props(['user'])

@php
    $isSelf = $user->id === auth()->id();
    $currentRole = $user->role;
    $isBanned = $user->isBannedNow();
@endphp

@if ($isSelf)
    <span class="text-xs text-muted">You</span>
@else
    <div x-data="{ open: false, userName: @js($user->display_name), userId: {{ $user->id }} }" class="relative inline-block text-left">
        <button
            @click="open = !open"
            @keydown.escape.window="open = false"
            @keydown.enter.prevent="open = !open"
            @keydown.space.prevent="open = !open"
            class="rounded-lg border border-line bg-surface-muted px-3 py-1 text-xs font-semibold text-content transition-colors hover:bg-surface"
            aria-haspopup="true"
            :aria-expanded="open"
            aria-label="Change role for {{ $user->username }}">
            Role
            <i data-lucide="chevron-down" class="ml-1 inline h-3 w-3"></i>
        </button>

        <div x-show="open"
             x-cloak
             @click.outside="open = false"
             x-transition
             class="absolute right-0 z-50 mt-1 w-48 rounded-lg border border-line bg-surface shadow-lg"
             role="menu">

            {{-- Promote options (hidden for banned users) --}}
            @if ($currentRole === 'user' && $isBanned)
                <p class="px-4 py-2 text-xs text-muted">Unban user before promoting.</p>
            @endif
            @if ($currentRole === 'user' && ! $isBanned)
                <button
                    @click="open = false; window.dispatchEvent(new CustomEvent('open-confirm', { detail: { title: 'Promote ' + userName + ' to Moderator?', message: 'They will be able to moderate content and manage exam papers.', confirmLabel: 'Make Moderator', danger: false, onConfirm: () => { document.getElementById('role-form-' + userId + '-moderator').submit(); } } }))"
                    class="block w-full px-4 py-2 text-left text-sm text-content hover:bg-surface-muted transition-colors"
                    role="menuitem">
                    Make Moderator
                </button>
                <button
                    @click="open = false; window.dispatchEvent(new CustomEvent('open-confirm', { detail: { title: 'Promote ' + userName + ' to Admin?', message: 'They will have full access to all admin features.', confirmLabel: 'Make Admin', danger: false, onConfirm: () => { document.getElementById('role-form-' + userId + '-admin').submit(); } } }))"
                    class="block w-full px-4 py-2 text-left text-sm text-content hover:bg-surface-muted transition-colors"
                    role="menuitem">
                    Make Admin
                </button>
            @endif

            @if ($currentRole === 'moderator')
                @if (! $isBanned)
                <button
                    @click="open = false; window.dispatchEvent(new CustomEvent('open-confirm', { detail: { title: 'Promote ' + userName + ' to Admin?', message: 'They will have full access to all admin features.', confirmLabel: 'Make Admin', danger: false, onConfirm: () => { document.getElementById('role-form-' + userId + '-admin').submit(); } } }))"
                    class="block w-full px-4 py-2 text-left text-sm text-content hover:bg-surface-muted transition-colors"
                    role="menuitem">
                    Make Admin
                </button>
                @endif
                <button
                    @click="open = false; window.dispatchEvent(new CustomEvent('open-confirm', { detail: { title: 'Demote ' + userName + ' from Moderator to User?', message: 'This will remove their moderation privileges.', confirmLabel: 'Demote to User', danger: true, onConfirm: () => { document.getElementById('role-form-' + userId + '-user').submit(); } } }))"
                    class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-surface-muted transition-colors"
                    role="menuitem">
                    Demote to User
                </button>
            @endif

            @if ($currentRole === 'admin')
                <button
                    @click="open = false; window.dispatchEvent(new CustomEvent('open-confirm', { detail: { title: 'Demote ' + userName + ' from Admin to User?', message: 'This will remove their admin privileges. They can be re-promoted later.', confirmLabel: 'Demote to User', danger: true, onConfirm: () => { document.getElementById('role-form-' + userId + '-user').submit(); } } }))"
                    class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-surface-muted transition-colors"
                    role="menuitem">
                    Demote to User
                </button>
            @endif
        </div>
    </div>

    {{-- Hidden forms for each possible role transition --}}
    @foreach (['user', 'moderator', 'admin'] as $r)
        @if ($r !== $currentRole)
            <form id="role-form-{{ $user->id }}-{{ $r }}"
                  action="{{ route('admin.users.role', $user) }}"
                  method="POST"
                  class="hidden">
                @csrf
                @method('PATCH')
                <input type="hidden" name="role" value="{{ $r }}">
            </form>
        @endif
    @endforeach
@endif
