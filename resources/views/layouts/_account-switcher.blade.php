{{-- Session account switcher, shared by the mobile bottom sheet and the sidebar user menu.
     $compact: tighter sizing for the sidebar dropdown. $close: Alpine expression that closes
     the containing menu when "Add account" is clicked. Expects $switchableAccounts in scope. --}}
@php
    $avatar = $compact ? 'h-7 w-7' : 'h-8 w-8';
    $gap = $compact ? 'gap-2.5' : 'gap-3';
@endphp
<p class="{{ $compact ? 'px-2 ' : '' }}pb-1 text-[10px] font-semibold uppercase tracking-wide text-muted">Accounts</p>
@foreach ($switchableAccounts as $account)
    @if ($account->id === auth()->id())
        <div class="flex items-center {{ $gap }} overflow-hidden rounded-lg bg-accent/10 px-2 py-2">
            <div class="{{ $avatar }} shrink-0 rounded-full bg-accent/15 flex items-center justify-center text-xs font-bold text-accent overflow-hidden">
                @if ($account->profile_image)
                    <img src="{{ $account->profile_image }}" alt="{{ $account->display_name }}" class="h-full w-full object-cover">
                @else
                    {{ strtoupper(substr($account->display_name, 0, 1)) }}
                @endif
            </div>
            <div class="min-w-0 flex-1 text-left">
                <div class="truncate text-sm font-semibold text-content">{{ $account->display_name }}</div>
                <div class="truncate text-[11px] text-muted">{{ '@' . $account->username }}</div>
            </div>
            <i data-lucide="check" class="h-4 w-4 shrink-0 text-accent"></i>
        </div>
    @else
        <div class="flex items-center gap-1 overflow-hidden">
            <form method="POST" action="{{ route('accounts.switch', $account) }}" class="min-w-0 flex-1">
                @csrf
                <button type="submit" class="flex w-full items-center {{ $gap }} rounded-lg px-2 py-2 text-left hover:bg-surface-muted transition-colors">
                    <div class="{{ $avatar }} shrink-0 rounded-full bg-accent/15 flex items-center justify-center text-xs font-bold text-accent overflow-hidden">
                        @if ($account->profile_image)
                            <img src="{{ $account->profile_image }}" alt="{{ $account->display_name }}" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr($account->display_name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-content">{{ $account->display_name }}</div>
                        <div class="truncate text-[11px] text-muted">{{ '@' . $account->username }}</div>
                    </div>
                </button>
            </form>
            <form method="POST" action="{{ route('accounts.remove', $account) }}" x-on:click.stop>
                @csrf
                @method('DELETE')
                <button type="submit" title="Remove {{ $account->display_name }}"
                        class="shrink-0 rounded-lg p-1.5 text-muted hover:text-red-500 hover:bg-red-500/10 transition-colors">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            </form>
        </div>
    @endif
@endforeach
@if ($switchableAccounts->count() < \App\Services\LinkedAccountService::MAX_ACCOUNTS)
    <a href="{{ route('accounts.add') }}"
       @click="{{ $close }}"
       class="flex items-center {{ $gap }} rounded-lg px-2 py-2 text-sm font-medium text-accent hover:bg-surface-muted transition-colors">
        <i data-lucide="user-plus" class="h-4 w-4"></i>
        Add account
    </a>
@endif
