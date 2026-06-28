{{-- @list-partial --}}
@php $authId = auth()->id(); @endphp

{{-- Filters --}}
<div class="mb-5 flex flex-wrap items-center gap-2">
    {{-- Search --}}
    <form method="GET" action="{{ route('admin.users') }}" class="flex items-center gap-2" id="user-search-form" @submit.prevent>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search name, @username, email…"
               class="rounded-xl border border-line bg-surface px-3 py-1.5 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent w-56"
               @input="clearTimeout(this._t); this._t = setTimeout(() => { const url = new URL('{{ route('admin.users') }}'); const val = $el.value; if (val) url.searchParams.set('search', val); @foreach (['role', 'status'] as $k) @if (request($k)) url.searchParams.set('{{ $k }}', '{{ request($k) }}'); @endif @endforeach load(url); }, 400)">
        @foreach (['role', 'status'] as $k)
            @if (request($k))
                <input type="hidden" name="{{ $k }}" value="{{ request($k) }}">
            @endif
        @endforeach
    </form>

    {{-- Role chips --}}
    @foreach (['admin' => 'Admin', 'moderator' => 'Mod', 'user' => 'User'] as $val => $label)
        @php $on = request('role') === $val; @endphp
        <a href="{{ request()->fullUrlWithQuery(['role' => $on ? null : $val, 'page' => null]) }}"
           class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors
                  {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
            {{ $label }}
        </a>
    @endforeach

    {{-- Status chips --}}
    @foreach (['active' => 'Active', 'banned' => 'Banned'] as $val => $label)
        @php $on = request('status') === $val; @endphp
        <a href="{{ request()->fullUrlWithQuery(['status' => $on ? null : $val, 'page' => null]) }}"
           class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors
                  {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if ($users->isEmpty())
    <div class="rounded-2xl border border-line bg-surface px-6 py-12 text-center">
        <i data-lucide="users" class="mx-auto mb-3 h-8 w-8 text-muted"></i>
        <p class="text-sm font-semibold text-content">No users found.</p>
        <p class="mt-1 text-xs text-muted">Try adjusting your filters.</p>
    </div>
@else
    <div class="rounded-2xl border border-line bg-surface shadow-sm overflow-visible">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line bg-surface-muted">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted rounded-tl-2xl">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden sm:table-cell">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden md:table-cell">Joined</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-muted rounded-tr-2xl">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($users as $user)
                    <tr class="hover:bg-surface-muted transition-colors" id="user-row-{{ $user->id }}">
                        {{-- Name + username --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent/10 text-xs font-bold text-accent">
                                    {{ strtoupper(substr($user->display_name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-content">{{ $user->display_name }}</div>
                                    <div class="text-xs text-muted">{{"@".$user->username }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Email --}}
                        <td class="px-4 py-3 text-xs text-muted hidden sm:table-cell">
                            {{ $user->email }}
                        </td>

                        {{-- Role --}}
                        <td class="px-4 py-3">
                            @include('admin.partials._role-badge', ['role' => $user->role])
                        </td>

                        {{-- Status badge --}}
                        <td class="px-4 py-3">
                            <span id="status-badge-{{ $user->id }}"
                                  class="rounded-full px-2 py-0.5 text-[10px] font-bold
                                         {{ $user->status === 'active'
                                             ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                             : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>

                        {{-- Joined --}}
                        <td class="px-4 py-3 text-xs text-muted hidden md:table-cell">
                            {{ $user->created_at->format('M j, Y') }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($user->id === $authId)
                                    <span class="text-xs text-muted">You</span>
                                @else
                                    <button
                                        onclick="toggleUserStatus({{ $user->id }}, '{{ $user->status }}')"
                                        id="toggle-btn-{{ $user->id }}"
                                        class="rounded-lg border border-line bg-surface-muted px-3 py-1 text-xs font-semibold text-content transition-colors hover:bg-surface">
                                        {{ $user->status === 'active' ? 'Ban' : 'Unban' }}
                                    </button>
                                @endif
                                @include('admin.partials._role-action-dropdown', ['user' => $user])
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($users->hasPages())
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    @endif
@endif

<script>
async function toggleUserStatus(userId, currentStatus) {
    const next   = currentStatus === 'active' ? 'banned' : 'active';
    const btn    = document.getElementById('toggle-btn-' + userId);
    const badge  = document.getElementById('status-badge-' + userId);
    if (!btn || !badge) return;

    btn.disabled = true;
    btn.textContent = '…';

    try {
        const res = await fetch(`/admin/users/${userId}/status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status: next }),
        });

        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();

        // Swap badge
        badge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
        badge.className = 'rounded-full px-2 py-0.5 text-[10px] font-bold ' +
            (data.status === 'active'
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400');

        btn.textContent = data.status === 'active' ? 'Ban' : 'Unban';
        btn.dataset.status = data.status;
        // Update onclick to reflect new current status
        btn.setAttribute('onclick', `toggleUserStatus(${userId}, '${data.status}')`);
    } catch (e) {
        btn.textContent = currentStatus === 'active' ? 'Ban' : 'Unban';
    } finally {
        btn.disabled = false;
    }
}
</script>
