<x-app-layout>
    <x-slot name="title">Feed — MiraiStudy</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Feed --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Create Post Button --}}
            @auth
                <a href="{{ route('posts.create') }}"
                   class="block w-full text-center bg-gradient-to-tr from-accent-from to-accent-to text-white font-medium py-3 rounded-xl hover:bg-accent-strong transition">
                    + Create Post
                </a>
            @endauth

            {{-- Search & Filters --}}
            <div class="flex flex-row gap-2 sm:gap-3">
                <div class="flex-1 hidden sm:block">
                    <input type="text" id="filter-search" name="search" placeholder="Search posts, authors..." value="{{ request('search') }}"
                           class="w-full rounded-xl bg-surface-muted border-line focus:border-accent focus:ring focus:ring-accent/30 focus:ring-opacity-50 text-sm">
                </div>
                <div class="flex-1 sm:flex-none sm:w-40">
                    <select id="filter-sort" class="w-full rounded-xl bg-surface-muted border-line focus:border-accent focus:ring focus:ring-accent/30 focus:ring-opacity-50 text-sm">
                        <option value="for_you" @selected(($sort ?? 'for_you') === 'for_you')>For You</option>
                        <option value="recent" @selected(($sort ?? 'for_you') === 'recent')>Recent</option>
                        <option value="popular" @selected(($sort ?? 'for_you') === 'popular')>Popular</option>
                    </select>
                </div>
                <div class="flex-1 sm:flex-none sm:w-40">
                    <select id="filter-tag" name="tag" class="w-full rounded-xl bg-surface-muted border-line focus:border-accent focus:ring focus:ring-accent/30 focus:ring-opacity-50 text-sm">
                        <option value="">All Tags</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(request('tag') == $tag->id)>{{ $tag->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="shrink-0">
                    <button type="button" id="clear-filters" class="w-full sm:w-auto px-4 py-2 bg-surface-muted hover:bg-surface-muted text-content text-sm font-medium rounded-xl border border-line transition">
                        Clear
                    </button>
                </div>
            </div>

            {{-- User profile card (shown when search matches a user) --}}
            <div id="user-card-container" class="space-y-[10px]">
                @foreach ($profileUsers as $profileUser)
                    <x-user-card :user="$profileUser" />
                @endforeach
            </div>

            {{-- Posts --}}
            <div id="posts-container" class="space-y-4">
                @include('feed._posts')

                @if ($posts->isEmpty())
                    <div class="flex flex-col items-center justify-center py-20 text-center">
                        <p class="text-sm font-semibold text-muted">No posts yet</p>
                        <p class="text-xs text-muted mt-1">Be the first to share something.</p>
                    </div>
                @endif
            </div>

            {{-- Intersection Observer watches this sentinel div --}}
            <div id="scroll-sentinel"></div>

        </div>

        {{-- Sidebar --}}
        <aside
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
            @include('feed._comment-drawer')

            @guest
                <div class="bg-surface border border-line rounded-xl p-5">
                    <h3 class="font-semibold text-content mb-2">Join MiraiStudy</h3>
                    <p class="text-sm text-muted mb-4">
                        Connect with learners, share knowledge, and track your study progress.
                    </p>
                    <a href="{{ route('register') }}"
                       class="block w-full text-center bg-gradient-to-tr from-accent-from to-accent-to text-white text-sm font-medium py-2.5 rounded-lg hover:opacity-90">
                        Create Account
                    </a>
                </div>
            @endguest

                @guest
                    <div id="quick-links" x-show="!isOpen" class="mt-4 hidden lg:block bg-surface border border-line rounded-xl p-5">
                @else
                    <div id="quick-links" x-show="!isOpen" class="hidden lg:block bg-surface border border-line rounded-xl p-5">
                @endguest

                <h3 class="font-semibold text-content mb-3">Quick Links</h3>
                <ul class="space-y-2 text-sm text-muted">
                    <li><a href="{{ route('exams.index') }}" class="hover:text-accent">Exam Papers</a></li>
                    <li><a href="{{ route('quiz.index') }}" class="hover:text-accent">Take a Quiz</a></li>
                    <li><a href="{{ route('timer.index') }}" class="hover:text-accent">Focus Timer</a></li>
                </ul>
            </div>
        </aside>

    </div>

    @push('scripts')
    @include('feed._comment-drawer-script')
    <script>
        (function () {
            const container = document.getElementById('posts-container');
            const sentinel  = document.getElementById('scroll-sentinel');
            const loader = { style: {} };
            const setLoaderText = () => {};

            const SKELETON = `<div class="post-skeleton relative rounded-2xl bg-surface border border-line shadow-sm animate-pulse"><div class="flex items-center justify-between px-4 py-3.5"><div class="flex items-center gap-2.5"><div class="h-[38px] w-[38px] shrink-0 rounded-full bg-surface-muted"></div><div class="space-y-1.5"><div class="h-3 w-24 rounded-md bg-surface-muted"></div><div class="h-2.5 w-16 rounded-md bg-surface-muted"></div></div></div><div class="h-7 w-20 rounded-lg bg-surface-muted"></div></div><div class="px-4 pb-3 space-y-2.5"><div class="h-3 w-full rounded-md bg-surface-muted"></div><div class="h-3 w-4/5 rounded-md bg-surface-muted"></div><div class="h-3 w-3/5 rounded-md bg-surface-muted"></div></div><div class="flex items-center justify-between px-3.5 py-3 border-t border-line"><div class="flex items-center gap-2"><div class="h-7 w-14 rounded-lg bg-surface-muted"></div><div class="h-7 w-14 rounded-lg bg-surface-muted"></div></div><div class="flex items-center gap-2"><div class="h-7 w-8 rounded-lg bg-surface-muted"></div><div class="h-7 w-8 rounded-lg bg-surface-muted"></div></div></div></div>`;
            const showSkeletons = (n = 3) => container.insertAdjacentHTML('beforeend', SKELETON.repeat(n));
            const removeSkeletons = () => container.querySelectorAll('.post-skeleton').forEach(el => el.remove());

            const SCROLL_KEY = 'feed_scroll_y';
            const PAGE_KEY   = 'feed_last_page';

            let currentPage = {{ $posts->currentPage() }};
            let isFetching  = false;
            let hasMore     = {{ $posts->hasMorePages() ? 'true' : 'false' }};

            const filterSearch = document.getElementById('filter-search');
            const filterTag = document.getElementById('filter-tag');
            const clearFiltersBtn = document.getElementById('clear-filters');
            const userCardContainer = document.getElementById('user-card-container');

            let currentAbortController = null;
            // Bumped on every applyFilters() run. An in-flight loadMore() or a
            // superseded applyFilters() compares against it and bails if it no
            // longer matches, so stale responses can't touch shared state.
            let requestId = 0;

            const filterSort = document.getElementById('filter-sort');
            let currentSort = filterSort ? filterSort.value : 'for_you';

            function buildUrl(page) {
                const params = new URLSearchParams(window.location.search);
                params.set('page', page);
                params.set('sort', currentSort);
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
                return '?' + params.toString();
            }

            const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

            // ── Initial skeleton on first load ───────────────────────
            @if ($posts->isNotEmpty())
            (function () {
                const initialHTML = container.innerHTML;
                container.innerHTML = '';
                showSkeletons();
                sleep(1000).then(() => {
                    removeSkeletons();
                    container.innerHTML = initialHTML;
                    window.renderIcons(container);
                });
            })();
            @endif

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
                showSkeletons();
                if (sentinel) {
                    sentinel.style.display = 'none';
                    observer.disconnect();
                }

                const url = buildUrl(currentPage);
                history.replaceState(null, '', url);

                try {
                    const data = await fetchPage(currentPage, signal);

                    // A newer filter run superseded this one while awaiting.
                    if (myRequestId !== requestId) return;

                    if (userCardContainer) {
                        userCardContainer.innerHTML = data.user_card_html ?? '';
                        if (data.user_card_html) window.renderIcons(userCardContainer);
                    }

                    if (data.html.trim() === '') {
                        container.innerHTML = `
                            <div class="flex flex-col items-center justify-center py-20 text-center">
                                <p class="text-sm font-semibold text-muted">No results found</p>
                                <p class="text-xs text-muted mt-1">Try adjusting your search or filters.</p>
                            </div>
                        `;
                    } else {
                        removeSkeletons();
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
                    removeSkeletons();
                    console.error('Filter fetch failed:', err);
                } finally {
                    if (myRequestId === requestId) {
                        isFetching = false;
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
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', () => {
                    if (filterSearch) filterSearch.value = '';
                    if (filterTag) filterTag.value = '';
                    if (userCardContainer) userCardContainer.innerHTML = '';
                    applyFilters();
                });
            }

            if (filterSort) filterSort.addEventListener('change', () => {
                currentSort = filterSort.value;
                applyFilters();
            });

            // ── Infinite scroll ──────────────────────────────────────
            async function loadMore() {
                if (isFetching || !hasMore) return;

                isFetching  = true;
                const myRequestId = requestId;
                let success = false;
                currentPage++;
                showSkeletons();

                try {
                    const [data] = await Promise.all([
                        fetchPage(currentPage),
                        sleep(1000)
                    ]);

                    // A filter change started a fresh result set while this
                    // page was loading — it's stale, so drop it instead of
                    // appending. applyFilters already reset currentPage/state.
                    if (myRequestId !== requestId) return;

                    removeSkeletons();
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
                    removeSkeletons();
                    console.error('Failed to load posts:', err);
                } finally {
                    if (myRequestId === requestId) {
                        isFetching = false;
                    }
                }
            }

            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) loadMore();
            }, { rootMargin: '200px' });

            if (sentinel) {
                if (hasMore) {
                    observer.observe(sentinel);
                } else {
                    sentinel.style.display = 'none';
                }
            }

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
                setLoaderText('Restoring your place...');

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
                setLoaderText('');

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

