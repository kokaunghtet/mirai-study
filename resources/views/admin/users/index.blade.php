<x-app-layout>
    <x-slot name="title">Users — MiraiStudy Admin</x-slot>

    <div class="px-4 pb-10" x-data="adminFilter()">
        <div class="max-w-5xl mx-auto">

            @if (session('success'))
                @push('scripts')
                <script>(function(d) { window._snackbarComponent ? window._snackbarComponent.show(d) : window._snackbarQueue.push(d); })({ message: @json(session('success')), type: 'success' });</script>
                @endpush
            @endif

            @if (session('error'))
                @push('scripts')
                <script>(function(d) { window._snackbarComponent ? window._snackbarComponent.show(d) : window._snackbarQueue.push(d); })({ message: @json(session('error')), type: 'error' });</script>
                @endpush
            @endif

            {{-- Header --}}
            <header class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Manage Users</h1>
                    <p class="mt-1 text-sm text-muted">Search, filter, and manage user accounts.</p>
                </div>
                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-line bg-surface px-4 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Dashboard
                </a>
            </header>

            <div id="admin-filter-results">
                @include('admin.users._list', compact('users'))
            </div>

        </div>
    </div>

@push('scripts')
<script>
const ADMIN_USERS_SKELETON = @json(view('admin.users._skeleton')->render());

function suspendMenu(userId) {
    return {
        userId,
        loading: false,
        open: false,
        duration: null,
        reason: '',
        dropX: 0,
        dropY: 0,

        toggle(event) {
            if (this.open) {
                this.open = false;
                return;
            }
            const rect = event.currentTarget.getBoundingClientRect();
            this.dropX = rect.right - 224; // w-56 = 224px
            this.dropY = rect.bottom + 6;
            this.open = true;
        },

        notify(message, type) {
            const detail = { message, type };
            window._snackbarComponent ? window._snackbarComponent.show(detail) : window._snackbarQueue.push(detail);
        },

        async confirm() {
            if (this.loading || !this.duration) return;
            this.loading = true;

            try {
                const res = await fetch(`/admin/users/${this.userId}/ban`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ type: 'temp', duration: this.duration, reason: this.reason }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    this.notify(err.message || 'Suspend failed (HTTP ' + res.status + ')', 'error');
                    return;
                }

                const badge = document.getElementById('status-badge-' + this.userId);
                if (badge) {
                    badge.textContent = 'Suspended';
                    badge.className = 'rounded-full px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                }

                const toggleBtn = document.getElementById('toggle-btn-' + this.userId);
                if (toggleBtn) {
                    toggleBtn.textContent = 'Unban';
                    toggleBtn.setAttribute('onclick', `toggleUserStatus(${this.userId}, 'suspended')`);
                }

                this.notify('User suspended for ' + this.duration + ' day' + (this.duration > 1 ? 's' : '') + '.', 'success');
                this.open = false;
                this.duration = null;
                this.reason = '';
            } catch (e) {
                console.error('Failed to suspend user:', e);
                this.notify('Network error. Try again.', 'error');
            } finally {
                this.loading = false;
            }
        },
    };
}

function banMenu(userId) {
    return {
        userId,
        loading: false,
        open: false,
        banned: false,
        reason: '',
        dropX: 0,
        dropY: 0,

        toggle(event) {
            if (this.open) {
                this.open = false;
                return;
            }
            const rect = event.currentTarget.getBoundingClientRect();
            this.dropX = rect.right - 224; // w-56 = 224px
            this.dropY = rect.bottom + 6;
            this.open = true;
        },

        notify(message, type) {
            const detail = { message, type };
            window._snackbarComponent ? window._snackbarComponent.show(detail) : window._snackbarQueue.push(detail);
        },

        async confirm() {
            if (this.loading || !this.reason.trim()) return;
            this.loading = true;

            try {
                const res = await fetch(`/admin/users/${this.userId}/ban`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ type: 'perm', reason: this.reason.trim() }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    this.notify(err.message || 'Ban failed (HTTP ' + res.status + ')', 'error');
                    return;
                }

                const badge = document.getElementById('status-badge-' + this.userId);
                if (badge) {
                    badge.textContent = 'Banned';
                    badge.className = 'rounded-full px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                }

                this.banned = true;
                this.notify('User banned.', 'success');
                this.open = false;
                this.reason = '';
            } catch (e) {
                console.error('Failed to ban user:', e);
                this.notify('Network error. Try again.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async unban() {
            if (this.loading) return;
            this.loading = true;

            try {
                const res = await fetch(`/admin/users/${this.userId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: 'active' }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    this.notify(err.message || 'Unban failed (HTTP ' + res.status + ')', 'error');
                    return;
                }

                const badge = document.getElementById('status-badge-' + this.userId);
                if (badge) {
                    badge.textContent = 'Active';
                    badge.className = 'rounded-full px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                }

                this.banned = false;
                this.notify('User unbanned.', 'success');
            } catch (e) {
                console.error('Failed to unban user:', e);
                this.notify('Network error. Try again.', 'error');
            } finally {
                this.loading = false;
            }
        },
    };
}

function unbanDialog(userId, type) {
    return {
        userId,
        type,
        loading: false,
        open: false,
        reason: '',
        dropX: 0,
        dropY: 0,

        toggle(event) {
            if (this.open) {
                this.open = false;
                return;
            }
            const rect = event.currentTarget.getBoundingClientRect();
            this.dropX = rect.right - 224;
            this.dropY = rect.bottom + 6;
            this.open = true;
        },

        notify(message, type) {
            const detail = { message, type };
            window._snackbarComponent ? window._snackbarComponent.show(detail) : window._snackbarQueue.push(detail);
        },

        async confirm() {
            if (this.loading) return;
            this.loading = true;

            try {
                const res = await fetch(`/admin/users/${this.userId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: 'active', reason: this.reason.trim() || null }),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    this.notify(err.message || this.type.charAt(0).toUpperCase() + this.type.slice(1) + ' failed (HTTP ' + res.status + ')', 'error');
                    return;
                }

                const badge = document.getElementById('status-badge-' + this.userId);
                if (badge) {
                    badge.textContent = 'Active';
                    badge.className = 'rounded-full px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                }

                const label = this.type === 'unsuspend' ? 'User unsuspended.' : 'User unbanned.';
                this.notify(label, 'success');
                this.open = false;
                this.reason = '';
            } catch (e) {
                console.error('Failed to ' + this.type + ' user:', e);
                this.notify('Network error. Try again.', 'error');
            } finally {
                this.loading = false;
            }
        },
    };
}

function adminFilter() {
    return {
        _st: null,
        _ac: null,
        _rid: 0,
        init() {
            window.addEventListener('popstate', () => {
                this.load(window.location.href);
            });
            this.$el.addEventListener('click', e => {
                const a = e.target.closest('a[href]');
                if (!a) return;
                let url;
                try { url = new URL(a.href); } catch { return; }
                if (url.pathname !== window.location.pathname) return;
                e.preventDefault();
                this.load(a.href);
            });
            this.$el.addEventListener('submit', e => {
                if (e.target.closest('#user-search-form')) e.preventDefault();
            });
            this.$el.addEventListener('input', e => {
                const input = e.target.closest('input[name="search"]');
                if (!input) return;
                clearTimeout(this._st);
                this._st = setTimeout(() => {
                    const url = new URL(window.location.href);
                    if (input.value) url.searchParams.set('search', input.value);
                    else url.searchParams.delete('search');
                    url.searchParams.delete('page');
                    this.load(url.toString());
                }, 300);
            });
        },
        async load(url) {
            const u = new URL(typeof url === 'string' ? url : url.toString());
            u.protocol = location.protocol;
            url = u.toString();

            if (this._ac) this._ac.abort();
            this._ac = new AbortController();
            const signal = this._ac.signal;
            const myRid = ++this._rid;

            const el = document.getElementById('admin-filter-results');
            const typedSearch = el.querySelector('input[name="search"]')?.value ?? '';
            el.innerHTML = ADMIN_USERS_SKELETON;

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal,
                });
                if (myRid !== this._rid) return;
                if (!res.ok) return;
                const { html } = await res.json();
                if (myRid !== this._rid) return;
                el.innerHTML = html;
                window.Alpine.initTree(el);
                window.renderIcons(el);
                history.replaceState(null, '', url);
                const s = el.querySelector('input[name="search"]');
                if (s) { s.value = typedSearch; s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
            } catch (err) {
                if (err.name === 'AbortError') return;
                console.error('Admin filter load failed:', err);
            }
        },
    };
}

(function () {
    const el = document.getElementById('admin-filter-results');
    if (!el || !el.children.length) return;
    const initialHTML = el.innerHTML;
    el.innerHTML = ADMIN_USERS_SKELETON;
    setTimeout(() => {
        el.innerHTML = initialHTML;
        window.Alpine.initTree(el);
        window.renderIcons(el);
    }, 1000);
})();
</script>
@endpush
</x-app-layout>
