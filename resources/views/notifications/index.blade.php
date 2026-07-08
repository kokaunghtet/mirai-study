<x-app-layout :title="'Notifications'">
    <div class="px-4 lg:px-8 max-w-2xl mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-bold text-content">Notifications</h1>
                @php $unreadTotal = $notifications->getCollection()->filter(fn($n) => !$n->isRead())->count(); @endphp
                @if ($unreadTotal > 0)
                    <p class="text-xs text-muted mt-0.5">{{ $unreadTotal }} unread</p>
                @endif
            </div>
            @if ($notifications->total() > 0)
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="text-xs font-medium text-muted hover:text-accent transition-colors cursor-pointer px-3 py-1.5 rounded-lg border border-line hover:border-accent/30">
                            Mark all read
                        </button>
                    </form>
                    <form method="POST" action="{{ route('notifications.destroy-all') }}"
                          data-confirm="Delete all notifications? This cannot be undone."
                          data-confirm-title="Delete all"
                          data-confirm-label="Delete all">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-xs font-medium text-red-500 hover:text-red-600 transition-colors cursor-pointer px-3 py-1.5 rounded-lg border border-red-200 hover:border-red-300 dark:border-red-500/30 dark:hover:border-red-500/50">
                            Delete all
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- List --}}
        <div id="notifications-container" class="space-y-2">
            @include('notifications._list')
        </div>

        <div id="scroll-sentinel"></div>

    </div>

    @push('scripts')
    <script>
        (function () {
            const container = document.getElementById('notifications-container');
            const sentinel  = document.getElementById('scroll-sentinel');

            let currentPage = {{ $notifications->currentPage() }};
            let isFetching  = false;
            let hasMore     = {{ $notifications->hasMorePages() ? 'true' : 'false' }};

            const SKELETON = `<x-notification-skeleton :count="10" />`.trim();
            const showSkeletons = () => container.insertAdjacentHTML('beforeend', SKELETON);
            const removeSkeletons = () => container.querySelectorAll('.notification-skeleton').forEach(el => el.remove());
            const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

            function buildUrl(page) {
                const params = new URLSearchParams(window.location.search);
                params.set('page', page);
                return '?' + params.toString();
            }

            async function loadMore() {
                if (isFetching || !hasMore) return;

                isFetching = true;
                currentPage++;
                showSkeletons();

                try {
                    const response = await fetch(buildUrl(currentPage), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const data = await response.json();

                    removeSkeletons();
                    window.appendWithIcons(container, data.html);

                    if (!data.next_page_url) {
                        hasMore = false;
                        observer.disconnect();
                        if (sentinel) sentinel.style.display = 'none';
                    }
                } catch (err) {
                    currentPage--;
                    removeSkeletons();
                    console.error('Failed to load notifications:', err);
                } finally {
                    isFetching = false;
                }
            }

            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) loadMore();
            }, { rootMargin: '200px' });

            function startObserving() {
                if (!sentinel) return;
                if (hasMore) {
                    observer.observe(sentinel);
                } else {
                    sentinel.style.display = 'none';
                }
            }

            // ── Initial skeleton on first load ───────────────────────
            // Observer starts only after the swap finishes, so loadMore()
            // can't fire mid-swap and get clobbered by the innerHTML restore.
            @if ($notifications->isNotEmpty())
            const initialHTML = container.innerHTML;
            container.innerHTML = '';
            showSkeletons();
            sleep(1000).then(() => {
                removeSkeletons();
                container.innerHTML = initialHTML;
                window.renderIcons(container);
                startObserving();
            });
            @else
            startObserving();
            @endif
        })();
    </script>
    @endpush
</x-app-layout>
