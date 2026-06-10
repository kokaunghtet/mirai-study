<x-app-layout>
    <x-slot name="title">Feed — MiraiStudy</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Feed --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Create Post Button --}}
            @auth
                <a href="{{ route('posts.create') }}"
                   class="block w-full text-center bg-gradient-to-tr from-mirai-lime to-mirai-dark text-white font-medium py-3 rounded-xl hover:bg-green-700 transition">
                    + Create Post
                </a>
            @endauth

            {{-- Search & Filters --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" id="filter-search" name="search" placeholder="Search posts, authors..." value="{{ request('search') }}"
                           class="w-full rounded-xl bg-gray-50 border-gray-200 focus:border-green-400 focus:ring focus:ring-green-200 focus:ring-opacity-50 text-sm">
                </div>
                <div class="w-full sm:w-40 shrink-0">
                    <select id="filter-tag" name="tag" class="w-full rounded-xl bg-gray-50 border-gray-200 focus:border-green-400 focus:ring focus:ring-green-200 focus:ring-opacity-50 text-sm">
                        <option value="">All Tags</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(request('tag') == $tag->id)>{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-40 shrink-0">
                    <select id="filter-sort" name="sort" class="w-full rounded-xl bg-gray-50 border-gray-200 focus:border-green-400 focus:ring focus:ring-green-200 focus:ring-opacity-50 text-sm">
                        <option value="latest" @selected(request('sort') === 'latest')>Latest</option>
                        <option value="popular" @selected(request('sort') === 'popular')>Popular</option>
                    </select>
                </div>
                <div class="w-full sm:w-auto shrink-0">
                    <button type="button" id="clear-filters" class="w-full sm:w-auto px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl border border-gray-200 transition">
                        Clear
                    </button>
                </div>
            </div>

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
        <aside class="space-y-4"
               x-data="commentDrawer()"
               x-on:open-comments.window="open($event.detail)">

            {{-- ─────────────────────────────────────────────────────
                 Comment box — appears at the top of the sidebar when
                 a post's comment button is clicked. Slides in from the
                 right; no backdrop, the feed stays fully visible.

                 It stays in-flow in the sidebar column and grows along
                 with the content area when the nav sidebar is collapsed
                 (see <main> max-width in layouts/app.blade.php), so the
                 whole layout widens in one continuous, synced motion.
            ───────────────────────────────────────────────────────── --}}
            <div x-show="isOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-6"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-6"
                 class="sticky top-4 z-30 flex max-h-[calc(100vh-2rem)] flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
                    <h3 class="truncate pr-3 font-semibold text-gray-900" x-text="title">Comments</h3>
                    <button type="button" @click="close()"
                            class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                            title="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <div x-show="loading" class="py-10 text-center text-sm text-gray-400">
                        Loading comments…
                    </div>
                    {{-- Comments markup is injected here --}}
                    <div x-ref="content" x-show="!loading"></div>
                </div>
            </div>

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
                    <li><a href="{{ route('exams.index') }}" class="hover:text-green-600">Exam Papers</a></li>
                    <li><a href="{{ route('quiz.index') }}" class="hover:text-green-600">Take a Quiz</a></li>
                    <li><a href="{{ route('timer.index') }}" class="hover:text-green-600">Focus Timer</a></li>
                </ul>
            </div>
        </aside>

    </div>

    @push('scripts')
    <script>
        function commentDrawer() {
            return {
                isOpen: false,
                loading: false,
                title: 'Comments',
                url: null,
                postId: null,

                async open({ url, title, id }) {
                    this.url     = url;
                    this.title   = title || 'Comments';
                    this.postId  = id ?? null;
                    this.isOpen  = true;
                    this.loading = true;
                    await this.load();
                },

                close() {
                    this.isOpen = false;
                },

                // Read the comment count baked into the partial and tell the
                // matching post card to refresh its badge.
                syncCount() {
                    const root = this.$refs.content.querySelector('[data-comments-root]');
                    if (!root || this.postId === null) return;
                    window.dispatchEvent(new CustomEvent('comments-updated', {
                        detail: { postId: this.postId, count: Number(root.dataset.count) }
                    }));
                },

                async load() {
                    this.loading = true;
                    try {
                        const res = await fetch(this.url, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        this.$refs.content.innerHTML = await res.text();
                        window.renderIcons(this.$refs.content);
                        this.syncCount();
                    } catch (err) {
                        console.error('Failed to load comments:', err);
                        this.$refs.content.innerHTML =
                            '<p class="py-10 text-center text-sm text-gray-400">Could not load comments. Please try again.</p>';
                    } finally {
                        this.loading = false;
                    }
                },

                // Intercept comment / reply / delete form submits inside the
                // drawer so they post via AJAX and refresh in place.
                init() {
                    const token = document.querySelector('meta[name=csrf-token]')?.content;

                    this.$refs.content.addEventListener('submit', async (e) => {
                        const form = e.target.closest('form');
                        if (!form) return;

                        // A delete form's inline confirm() may have already
                        // cancelled the submit — respect that and bail.
                        if (e.defaultPrevented) return;

                        e.preventDefault();

                        const submitBtn = form.querySelector('[type=submit]');
                        if (submitBtn) submitBtn.disabled = true;

                        try {
                            const res = await fetch(form.action, {
                                method: 'POST', // _method spoofing handles DELETE
                                body: new FormData(form),
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': token,
                                    'Accept': 'text/html',
                                }
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            this.$refs.content.innerHTML = await res.text();
                            window.renderIcons(this.$refs.content);
                            this.syncCount();
                        } catch (err) {
                            console.error('Comment action failed:', err);
                            if (submitBtn) submitBtn.disabled = false;
                        }
                    });
                },
            };
        }
    </script>
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

            const filterSearch = document.getElementById('filter-search');
            const filterTag = document.getElementById('filter-tag');
            const filterSort = document.getElementById('filter-sort');
            const clearFiltersBtn = document.getElementById('clear-filters');

            let currentAbortController = null;
            // Bumped on every applyFilters() run. An in-flight loadMore() or a
            // superseded applyFilters() compares against it and bails if it no
            // longer matches, so stale responses can't touch shared state.
            let requestId = 0;

            function buildUrl(page) {
                const params = new URLSearchParams(window.location.search);
                params.set('page', page);
                if (filterSearch.value.trim()) {
                    params.set('search', filterSearch.value.trim());
                } else {
                    params.delete('search');
                }
                if (filterTag.value) {
                    params.set('tag', filterTag.value);
                } else {
                    params.delete('tag');
                }
                if (filterSort.value && filterSort.value !== 'latest') {
                    params.set('sort', filterSort.value);
                } else {
                    params.delete('sort');
                }
                return '?' + params.toString();
            }

            const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

            // ── Shared fetch helper ──────────────────────────────────
            async function fetchPage(page, signal = null) {
                const response = await fetch(buildUrl(page), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: signal
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            }

            // ── Filtering Logic ──────────────────────────────────────
            async function applyFilters() {
                if (currentAbortController) {
                    currentAbortController.abort();
                }
                currentAbortController = new AbortController();
                const signal = currentAbortController.signal;
                const myRequestId = ++requestId; // invalidates any in-flight loadMore

                currentPage = 1;
                hasMore = true;
                isFetching = true;
                
                container.innerHTML = '';
                if (sentinel) {
                    sentinel.style.display = 'none';
                    observer.disconnect();
                }
                loader.textContent = 'Loading...';
                loader.style.display = 'block';

                const url = buildUrl(currentPage);
                history.replaceState(null, '', url);

                try {
                    const data = await fetchPage(currentPage, signal);

                    // A newer filter run superseded this one while awaiting.
                    if (myRequestId !== requestId) return;

                    if (data.html.trim() === '') {
                        container.innerHTML = `
                            <div class="flex flex-col items-center justify-center py-20 text-center">
                                <p class="text-sm font-semibold text-gray-400">No results found</p>
                                <p class="text-xs text-gray-400 mt-1">Try adjusting your search or filters.</p>
                            </div>
                        `;
                    } else {
                        window.appendWithIcons(container, data.html);
                    }

                    if (!data.next_page_url) {
                        hasMore = false;
                    } else {
                        if (sentinel) {
                            sentinel.style.display = 'block';
                            observer.observe(sentinel);
                        }
                    }
                } catch (err) {
                    if (err.name === 'AbortError') return;
                    console.error('Filter fetch failed:', err);
                    loader.textContent = 'Failed to load results.';
                } finally {
                    // Only the latest run owns the shared loader/fetch flag; a
                    // superseded (aborted) run must not reset them mid-load.
                    if (myRequestId === requestId) {
                        isFetching = false;
                        loader.style.display = 'none';
                    }
                }
            }

            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            if (filterSearch) filterSearch.addEventListener('input', debounce(applyFilters, 300));
            if (filterTag) filterTag.addEventListener('change', applyFilters);
            if (filterSort) filterSort.addEventListener('change', applyFilters);
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', () => {
                    if (filterSearch) filterSearch.value = '';
                    if (filterTag) filterTag.value = '';
                    if (filterSort) filterSort.value = 'latest';
                    applyFilters();
                });
            }

            // ── Infinite scroll ──────────────────────────────────────
            async function loadMore() {
                if (isFetching || !hasMore) return;

                isFetching  = true;
                const myRequestId = requestId;
                let success = false;
                loader.style.display = 'block';
                currentPage++;

                try {
                    const [data] = await Promise.all([
                        fetchPage(currentPage),
                        sleep(1000)
                    ]);

                    // A filter change started a fresh result set while this
                    // page was loading — it's stale, so drop it instead of
                    // appending. applyFilters already reset currentPage/state.
                    if (myRequestId !== requestId) return;

                    window.appendWithIcons(container, data.html);
                    success = true;

                    if (!data.next_page_url) {
                        hasMore = false;
                        observer.disconnect();
                        if (sentinel) sentinel.style.display = 'none';
                    }
                } catch (err) {
                    if (myRequestId !== requestId) return; // stale failure — ignore
                    currentPage--;
                    console.error('Failed to load posts:', err);
                    loader.innerHTML = 'Failed to load posts. Scroll down to retry.';
                    loader.style.display = 'block';
                    setTimeout(() => {
                        loader.style.display = 'none';
                        loader.innerHTML = 'Loading...';
                    }, 3000);
                } finally {
                    // If a newer filter run superseded us, it owns the shared
                    // state now — leave isFetching/loader for it to manage.
                    if (myRequestId === requestId) {
                        isFetching = false;
                        if (success) loader.style.display = 'none';
                        // If not success, catch already manages the loader
                    }
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
                            if (sentinel) sentinel.style.display = 'none';
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

