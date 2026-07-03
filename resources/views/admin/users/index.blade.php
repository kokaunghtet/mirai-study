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
function adminFilter() {
    return {
        _st: null,
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
                }, 400);
            });
        },
        async load(url) {
            const u = new URL(typeof url === 'string' ? url : url.toString()); u.protocol = location.protocol; url = u.toString();
            const el = document.getElementById('admin-filter-results');
            el.style.opacity = '0.5';
            el.style.pointerEvents = 'none';
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const { html } = await res.json();
                el.innerHTML = html;
                window.Alpine.initTree(el);
                window.renderIcons(el);
                history.pushState(null, '', url);
                const s = el.querySelector('input[name="search"]');
                if (s) { s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
            } finally {
                el.style.opacity = '';
                el.style.pointerEvents = '';
            }
        },
    };
}
</script>
@endpush
</x-app-layout>
