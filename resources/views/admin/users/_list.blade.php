{{-- @list-partial --}}
@php $authId = auth()->id(); @endphp

{{-- Filters --}}
<div class="mb-5 flex flex-wrap items-center gap-2">
    {{-- Search --}}
    <form method="GET" action="{{ route('admin.users') }}" class="flex items-center gap-2" id="user-search-form">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search name, @username, email…"
               class="rounded-xl border border-line bg-surface px-3 py-1.5 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent w-56">
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
    @foreach (['active' => 'Active', 'suspended' => 'Suspended', 'banned' => 'Banned'] as $val => $label)
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
                        <td class="px-4 py-3 w-[220px] max-w-[220px]">
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
                            <div class="max-w-[200px] truncate">{{ $user->email }}</div>
                        </td>

                        {{-- Role --}}
                        <td class="px-4 py-3">
                            @include('admin.partials._role-badge', ['role' => $user->role])
                        </td>

                        {{-- Status badge --}}
                        <td class="px-4 py-3">
                            <span id="status-badge-{{ $user->id }}"
                                  class="rounded-full px-2 py-0.5 text-[10px] font-bold
                                         {{ match (true) {
                                             $user->status === 'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                             $user->status === 'suspended' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                             default => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                         } }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>

                        {{-- Joined --}}
                        <td class="px-4 py-3 text-xs text-muted hidden md:table-cell">
                            {{ $user->created_at->format('M j, Y') }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if ($user->id === $authId)
                                    <span class="text-xs text-muted">-</span>
                                @else
                                    @if ($user->status !== 'banned')
                                        <div x-data="suspendMenu({{ $user->id }})" @keydown.escape.window="open = false" @scroll.window="open = false">
                                            <button @click="toggle($event)" :disabled="loading"
                                                    id="suspend-btn-{{ $user->id }}"
                                                    class="rounded-lg border border-line bg-surface-muted px-3 py-1 text-xs font-semibold text-content transition-colors hover:bg-surface disabled:opacity-50">
                                                Suspend
                                            </button>

                                            <div x-show="open" x-cloak @click.outside="open = false"
                                                 x-transition
                                                 :style="'position:fixed; left:' + dropX + 'px; top:' + dropY + 'px;'"
                                                 class="z-50 w-56 rounded-xl border border-line bg-surface p-3 text-left shadow-lg">
                                                <p class="mb-2 text-[10px] font-bold text-content">Suspend duration</p>
                                                <div class="mb-2 flex flex-wrap gap-1.5">
                                                    @foreach ([1 => '1d', 3 => '3d', 7 => '7d', 30 => '30d'] as $days => $lbl)
                                                        <button type="button" @click="duration = {{ $days }}"
                                                                :class="duration === {{ $days }} ? 'bg-accent text-white' : 'border border-line bg-surface-muted text-muted hover:text-content'"
                                                                class="rounded-lg px-2.5 py-1 text-[10px] font-semibold transition-colors">
                                                            {{ $lbl }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                                <label class="mb-1 block text-[10px] text-muted">Reason <span class="text-muted/60">(optional)</span></label>
                                                <input type="text" x-model="reason" maxlength="200"
                                                       placeholder="Brief reason…"
                                                       class="mb-2 w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20">
                                                <div class="flex gap-1.5">
                                                    <button @click="confirm()" :disabled="!duration || loading"
                                                            class="flex-1 rounded-lg bg-amber-100 py-1 text-[10px] font-bold text-amber-700 transition-colors hover:bg-amber-200 disabled:opacity-40 dark:bg-amber-900/30 dark:text-amber-400">
                                                        <span x-show="!loading">Confirm</span>
                                                        <span x-show="loading">…</span>
                                                    </button>
                                                    <button @click="open = false"
                                                            class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($user->status !== 'banned')
                                        <div x-data="banMenu({{ $user->id }})" @keydown.escape.window="open = false" @scroll.window="open = false">
                                            <button @click="banned ? unban() : toggle($event)" :disabled="loading"
                                                    id="ban-btn-{{ $user->id }}"
                                                    class="rounded-lg border border-line bg-surface-muted px-3 py-1 text-xs font-semibold text-content transition-colors hover:bg-surface disabled:opacity-50">
                                                <span x-text="banned ? 'Unban' : 'Ban'"></span>
                                            </button>

                                            <div x-show="open" x-cloak @click.outside="open = false"
                                                 x-transition
                                                 :style="'position:fixed; left:' + dropX + 'px; top:' + dropY + 'px;'"
                                                 class="z-50 w-56 rounded-xl border border-line bg-surface p-3 text-left shadow-lg">
                                                <p class="mb-2 text-[10px] font-bold text-content">Ban user</p>
                                                <label class="mb-1 block text-[10px] text-muted">Reason <span class="text-red-500">*</span></label>
                                                <input type="text" x-model="reason" maxlength="200"
                                                       placeholder="Reason for ban…"
                                                       class="mb-2 w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20">
                                                <div class="flex gap-1.5">
                                                    <button @click="confirm()" :disabled="!reason.trim() || loading"
                                                            class="flex-1 rounded-lg bg-red-100 py-1 text-[10px] font-bold text-red-700 transition-colors hover:bg-red-200 disabled:opacity-40 dark:bg-red-900/30 dark:text-red-400">
                                                        <span x-show="!loading">Confirm</span>
                                                        <span x-show="loading">…</span>
                                                    </button>
                                                    <button @click="open = false"
                                                            class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div x-data="{ loading: false, done: {{ $user->status === 'suspended' ? 'false' : 'true'}}, reason: '' }"
                                             x-show="!done"
                                             x-on:unsuspend-{{ $user->id }}.window="done = false"
                                             x-on:ban-{{ $user->id }}.window="done = true">
                                            <button @click="
                                                        if (loading) return;
                                                        loading = true;
                                                        try {
                                                            const res = await fetch('/admin/users/{{ $user->id }}/status', {
                                                                method: 'PATCH',
                                                                headers: {
                                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                                    'Content-Type': 'application/json',
                                                                    'Accept': 'application/json',
                                                                },
                                                                body: JSON.stringify({ status: 'active' }),
                                                            });
                                                            if (!res.ok) throw new Error('HTTP ' + res.status);
                                                            const badge = document.getElementById('status-badge-{{ $user->id }}');
                                                            if (badge) {
                                                                badge.textContent = 'Active';
                                                                badge.className = 'rounded-full px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                                                            }
                                                            done = true;
                                                            window._snackbarComponent ? window._snackbarComponent.show({ message: 'User unsuspended.', type: 'success' }) : window._snackbarQueue.push({ message: 'User unsuspended.', type: 'success' });
                                                        } catch (e) {
                                                            window._snackbarComponent ? window._snackbarComponent.show({ message: 'Network error. Try again.', type: 'error' }) : window._snackbarQueue.push({ message: 'Network error. Try again.', type: 'error' });
                                                        } finally {
                                                            loading = false;
                                                        }
                                                    "
                                                    :disabled="loading"
                                                    class="rounded-lg border border-line bg-surface-muted px-3 py-1 text-xs font-semibold text-content transition-colors hover:bg-surface disabled:opacity-50">
                                                <span x-show="!loading">Unsuspend</span>
                                                <span x-show="loading">…</span>
                                            </button>
                                        </div>
                                    @else
                                        <div x-data="unbanDialog({{ $user->id }}, 'unban')" x-show="!done" @keydown.escape.window="open = false" @scroll.window="open = false">
                                            <button @click="toggle($event)" :disabled="loading"
                                                    class="rounded-lg border border-line bg-surface-muted px-3 py-1 text-xs font-semibold text-content transition-colors hover:bg-surface disabled:opacity-50">
                                                Unban
                                            </button>

                                            <div x-show="open" x-cloak @click.outside="open = false"
                                                 x-transition
                                                 :style="'position:fixed; left:' + dropX + 'px; top:' + dropY + 'px;'"
                                                 class="z-50 w-56 rounded-xl border border-line bg-surface p-3 text-left shadow-lg">
                                                <p class="mb-2 text-[10px] font-bold text-content">Unban user</p>
                                                <label class="mb-1 block text-[10px] text-muted">Reason <span class="text-muted/60">(optional)</span></label>
                                                <input type="text" x-model="reason" maxlength="200"
                                                       placeholder="Brief reason…"
                                                       class="mb-2 w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20">
                                                <div class="flex gap-1.5">
                                                    <button @click="confirm()" :disabled="loading"
                                                            class="flex-1 rounded-lg bg-green-100 py-1 text-[10px] font-bold text-green-700 transition-colors hover:bg-green-200 disabled:opacity-40 dark:bg-green-900/30 dark:text-green-400">
                                                        <span x-show="!loading">Confirm</span>
                                                        <span x-show="loading">…</span>
                                                    </button>
                                                    <button @click="open = false"
                                                            class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @include('admin.partials._role-action-dropdown', ['user' => $user])
                                @endif
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
