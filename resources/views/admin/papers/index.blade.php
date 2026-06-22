<x-app-layout>
    <x-slot name="title">Manage Papers — MiraiStudy</x-slot>

    <div class="px-4" x-data="adminFilter()">
        <div class="max-w-4xl mx-auto">

            {{-- Header --}}
            <header class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Manage Papers</h1>
                    <p class="mt-1 text-sm text-muted">Upload and remove past exam papers.</p>
                </div>
                <a href="{{ route('admin.papers.create') }}"
                   class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-accent px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-accent-strong">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Upload</span>
                </a>
            </header>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 rounded-xl border border-accent/30 bg-accent/10 px-4 py-3 text-sm font-medium text-content">
                    {{ session('success') }}
                </div>
            @endif

            <div id="admin-filter-results">
                @include('admin.papers._list', compact('papers', 'categories', 'years', 'counts'))
            </div>

        </div>
    </div>
@push('scripts')
<script>
function adminFilter() {
    return {
        init() {
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
            } finally {
                el.style.opacity = '';
                el.style.pointerEvents = '';
            }
        }
    };
}
function paperHistory(paperId) {
    return {
        open: false,
        loaded: false,
        loading: false,
        revisions: [],
        toggle() {
            this.open = !this.open;
            if (this.open && !this.loaded) this.fetch();
        },
        async fetch() {
            this.loading = true;
            try {
                const res = await fetch(`/admin/papers/${paperId}/history`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error(res.status);
                this.revisions = await res.json();
                this.loaded = true;
            } catch (e) {
                console.error('Paper history fetch failed:', e);
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
</x-app-layout>
