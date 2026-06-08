<x-app-layout>
    <x-slot name="title">Bookmarks — MiraiStudy</x-slot>

    <div class="flex justify-center">
        <div class="w-full max-w-[720px]">

            {{-- Header --}}
            <div class="mb-5"> 
                <h1 class="text-xl font-bold text-gray-900">Bookmarks</h1>
                <p class="text-sm text-gray-400 mt-0.5">Posts you've saved for later</p>
            </div>

            {{-- Posts --}}
            <div id="posts-container" class="space-y-4">
                @include('bookmarks._posts')

                @if ($posts->isEmpty())
                    <div class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <i data-lucide="bookmark" class="h-7 w-7 text-gray-400"></i>
                        </div>
                        <p class="text-sm font-semibold text-gray-500">No bookmarks yet</p>
                        <p class="text-xs text-gray-400 mt-1">Posts you bookmark will appear here.</p>
                        <a href="{{ route('feed.index') }}"
                        class="mt-4 text-sm font-semibold text-green-600 hover:underline">
                            Browse the feed →
                        </a>
                    </div>
                @endif
            </div>

            {{-- Intersection Observer watches this sentinel div --}}
            <div id="scroll-sentinel"></div>

            <div id="loading-indicator" style="display:none; text-align:center; padding:1rem;">
                Loading...
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const container = document.getElementById('posts-container');
            const sentinel  = document.getElementById('scroll-sentinel');
            const loader    = document.getElementById('loading-indicator');

            const SCROLL_KEY = 'bookmarks_scroll_y';
            const PAGE_KEY   = 'bookmarks_last_page';

            let currentPage = 1;
            let isFetching  = false;
            let hasMore     = true;

            const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

            // ── Shared fetch helper ──────────────────────────────────
            async function fetchPage(page) {
                const response = await fetch(`?page=${page}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            }

            // ── Infinite scroll ──────────────────────────────────────
            async function loadMore() {
                if (isFetching || !hasMore) return;

                isFetching  = true;
                let success = false;
                loader.style.display = 'block';
                currentPage++;

                try {
                    const [data] = await Promise.all([
                        fetchPage(currentPage),
                        sleep(1000)
                    ]);

                    window.appendWithIcons(container, data.html);
                    success = true;

                    if (!data.next_page_url) {
                        hasMore = false;
                        observer.disconnect();
                        if (sentinel) sentinel.remove();
                    }
                } catch (err) {
                    currentPage--;
                    console.error('Failed to load posts:', err);
                    loader.innerHTML = 'Failed to load posts. Scroll down to retry.';
                    loader.style.display = 'block';
                    setTimeout(() => {
                        loader.style.display = 'none';
                        loader.innerHTML = 'Loading...';
                    }, 3000);
                } finally {
                    isFetching = false;
                    if (success) loader.style.display = 'none';
                    // If not success, catch already manages the loader
                }
            }

            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) loadMore();
            }, { rootMargin: '200px' });

            if (sentinel) observer.observe(sentinel);

            // ── Save position when navigating to a post ──────────────
            // We listen on the container so it works for dynamically
            // loaded posts too (infinite scroll adds new cards to DOM)
            container.addEventListener('click', (e) => {
                const postLink = e.target.closest('a[href*="/posts/"]');
                if (postLink) {
                    sessionStorage.setItem(SCROLL_KEY, window.scrollY);
                    sessionStorage.setItem(PAGE_KEY, currentPage);
                }
            });

            // ── Restore on back navigation ───────────────────────────
            async function restoreScrollState() {
                const savedScroll = sessionStorage.getItem(SCROLL_KEY);
                const savedPage   = parseInt(sessionStorage.getItem(PAGE_KEY) || '1');

                // Nothing saved — normal page load, do nothing
                if (!savedScroll || savedPage <= 1) return;

                // Clear immediately so a manual refresh doesn't restore again
                sessionStorage.removeItem(SCROLL_KEY);
                sessionStorage.removeItem(PAGE_KEY);

                // Show restore indicator
                loader.style.display = 'block';
                loader.textContent = 'Restoring your place...';

                // Reload pages 2 → savedPage without the artificial delay
                for (let page = 2; page <= savedPage; page++) {
                    try {
                        const data = await fetchPage(page);
                        window.appendWithIcons(container, data.html);
                        currentPage = page;

                        if (!data.next_page_url) {
                            hasMore = false;
                            observer.disconnect();
                            if (sentinel) sentinel.remove();
                            break;
                        }
                    } catch (err) {
                        console.error('Restore failed at page', page, err);
                        break;
                    }
                }

                loader.style.display = 'none';
                loader.textContent = 'Loading...';

                // Double rAF ensures browser has painted new content
                // before we jump to the saved position
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        window.scrollTo({
                            top: parseInt(savedScroll),
                            behavior: 'instant'
                        });
                    });
                });
            }

            // pageshow fires on both normal load AND bfcache restore
            // (bfcache = browser's back/forward in-memory cache)
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    // Browser restored page from memory cache —
                    // content is already in DOM, just scroll
                    const savedScroll = sessionStorage.getItem(SCROLL_KEY);
                    if (savedScroll) {
                        sessionStorage.removeItem(SCROLL_KEY);
                        sessionStorage.removeItem(PAGE_KEY);
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                window.scrollTo({
                                    top: parseInt(savedScroll),
                                    behavior: 'instant'
                                });
                            });
                        });
                    }
                } else {
                    // Normal page load — check if we need to restore
                    restoreScrollState();
                }
            });

        })();
    </script>
    @endpush
</x-app-layout>