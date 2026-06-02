<x-app-layout>
    <x-slot name="title">Feed — MiraiStudy</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Feed --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Create Post Button --}}
            @auth
                <a href="{{ route('posts.create') }}"
                   class="block w-full text-center bg-green-600 text-white font-medium py-3 rounded-xl hover:bg-green-700 transition">
                    + Create Post
                </a>
            @endauth

            {{-- Posts --}}
            <div id="posts-container" class="space-y-4">
                @include('feed._posts')

                @if ($posts->isEmpty())
                    <div class="flex flex-col items-center justify-center py-20 text-center">
                        <p class="text-sm font-semibold text-gray-400">No posts yet</p>
                        <p class="text-xs text-gray-400 mt-1">Be the first to share something.</p>
                    </div>
                @endif
            </div>

            {{-- Intersection Observer watches this sentinel div --}}
            <div id="scroll-sentinel"></div>

            <div id="loading-indicator" style="display:none; text-align:center; padding:1rem;">
                Loading...
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4">
            @guest
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-900 mb-2">Join MiraiStudy</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Connect with learners, share knowledge, and track your study progress.
                    </p>
                    <a href="{{ route('register') }}"
                       class="block w-full text-center bg-green-600 text-white text-sm font-medium py-2.5 rounded-lg hover:bg-green-700">
                        Create Account
                    </a>
                </div>
            @endguest

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Quick Links</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="{{ route('exams.index') }}" class="hover:text-green-600">📄 Exam Papers</a></li>
                    <li><a href="{{ route('quiz.index') }}" class="hover:text-green-600">📝 Take a Quiz</a></li>
                    <li><a href="{{ route('timer.index') }}" class="hover:text-green-600">⏱ Focus Timer</a></li>
                </ul>
            </div>
        </aside>

    </div>

    @push('scripts')
    <script>
        (function () {
            const container = document.getElementById('posts-container');
            const sentinel  = document.getElementById('scroll-sentinel');
            const loader    = document.getElementById('loading-indicator');

            const SCROLL_KEY = 'feed_scroll_y';
            const PAGE_KEY   = 'feed_last_page';

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

                    container.insertAdjacentHTML('beforeend', data.html);
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
                        container.insertAdjacentHTML('beforeend', data.html);
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

