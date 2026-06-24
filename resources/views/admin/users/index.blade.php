<x-app-layout>
    <x-slot name="title">Users — MiraiStudy Admin</x-slot>

    <div class="px-4 pb-10" x-data="adminFilter()">
        <div class="max-w-5xl mx-auto">

            {{-- Flash messages --}}
            @if (session('success'))
                <div x-data="{ show: true }"
                     x-init="setTimeout(() => show = false, 3000)"
                     x-show="show"
                     x-transition.opacity
                     class="mb-4 rounded-lg bg-accent/10 border border-accent/30 px-4 py-3 text-sm font-semibold text-accent">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ show: true }"
                     x-init="setTimeout(() => show = false, 3000)"
                     x-show="show"
                     x-transition.opacity
                     class="mb-4 rounded-lg bg-red-100 border border-red-300 px-4 py-3 text-sm font-semibold text-red-700 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400">
                    {{ session('error') }}
                </div>
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
        },
        async load(url) {
            url = typeof url === 'string' ? url : url.toString();
            history.pushState(null, '', url);
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
