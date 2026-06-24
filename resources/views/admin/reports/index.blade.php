<x-app-layout>
    <x-slot name="title">Reports — MiraiStudy Admin</x-slot>

    <div class="px-4 pb-10" x-data="adminFilter()">
        <div class="max-w-5xl mx-auto">

            {{-- Header --}}
            <header class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">
                        Reports
                        @php $pending = \App\Models\Report::where('status','pending')->count(); @endphp
                        @if ($pending > 0)
                            <span class="ml-2 rounded-full bg-accent px-2 py-0.5 text-sm font-bold text-white align-middle">
                                {{ $pending }}
                            </span>
                        @endif
                    </h1>
                    <p class="mt-1 text-sm text-muted">Review and moderate reported content.</p>
                </div>
                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-line bg-surface px-4 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Dashboard
                </a>
            </header>

            <div id="admin-filter-results">
                @include('admin.reports._list', compact('reports'))
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
