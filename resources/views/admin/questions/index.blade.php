<x-app-layout>
    <x-slot name="title">Manage Questions — MiraiStudy</x-slot>

    <div class="px-4" x-data="adminFilter()">
        <div class="max-w-4xl mx-auto">

            {{-- Header --}}
            <header class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Manage Questions</h1>
                    <p class="mt-1 text-sm text-muted">Add and remove quiz questions.</p>
                </div>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.questions.create') }}"
                       class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-gradient-to-tr from-accent-from to-accent-to px-4 py-2 text-sm font-bold text-white transition-colors hover:opacity-90">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        <span>New question</span>
                    </a>
                @endif
            </header>

            @if (session('success'))
                @push('scripts')
                <script>(function(d) { window._snackbarComponent ? window._snackbarComponent.show(d) : window._snackbarQueue.push(d); })({ message: @json(session('success')), type: 'success' });</script>
                @endpush
            @endif

            <div id="admin-filter-results">
                @include('admin.questions._list', compact('questions', 'categories', 'sections', 'counts'))
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
            const u = new URL(url); u.protocol = location.protocol; url = u.toString();
            const el = document.getElementById('admin-filter-results');
            el.style.opacity = '0.5';
            el.style.pointerEvents = 'none';
            el.style.position = 'relative';
            const loader = document.createElement('div');
            loader.style.cssText = 'position:absolute;top:4rem;left:50%;transform:translateX(-50%);z-index:10;';
            loader.innerHTML = '<span class="mirai-loader" style="font-size:1.75rem" aria-hidden="true"></span>';
            el.appendChild(loader);
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
                el.style.position = '';
                loader.remove();
            }
        }
    };
}
function questionHistory(questionId) {
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
                const res = await fetch(`/admin/questions/${questionId}/history`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error(res.status);
                this.revisions = await res.json();
                this.loaded = true;
            } catch (e) {
                console.error('Question history fetch failed:', e);
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
</x-app-layout>
