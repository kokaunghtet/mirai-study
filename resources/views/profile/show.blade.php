<x-app-layout>
    <x-slot name="title">{{ $user->display_name }} — MiraiStudy</x-slot>

    <div class="max-w-[720px] mx-auto">

        {{-- Profile Header --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-5">
            <div class="flex items-start gap-5">

                {{-- Avatar --}}
                <div class="shrink-0" @if ($user->profile_image) x-data="{ open: false }" @endif>
                    @if ($user->profile_image)
                        <img src="{{ $user->profile_image }}"
                             alt="{{ $user->display_name }}"
                             loading="lazy"
                             @click="open = true"
                             class="w-20 h-20 rounded-full object-cover border-2 border-gray-100 cursor-pointer hover:opacity-90 transition-opacity">

                        {{-- Full-size lightbox --}}
                        <div x-show="open" x-cloak
                             @click="open = false"
                             @keydown.escape.window="open = false"
                             x-transition.opacity
                             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4">
                            <img src="{{ $user->profile_image }}"
                                 alt="{{ $user->display_name }}"
                                 @click.stop
                                 class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain shadow-2xl">
                            <button type="button"
                                    @click="open = false"
                                    class="absolute top-4 right-4 grid h-10 w-10 place-items-center rounded-full bg-white/10 text-white/80 hover:bg-white/20 hover:text-white transition-colors"
                                    aria-label="Close">
                                <i data-lucide="x" class="h-5 w-5"></i>
                            </button>
                        </div>
                    @else
                        <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold text-2xl border-2 border-gray-100">
                            {{ strtoupper(substr($user->display_name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">

                    {{-- Name + Action button --}}
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 leading-tight">
                                {{ $user->display_name }}
                            </h1>
                            <p class="text-sm text-gray-400 mt-0.5">{{ '@'.$user->username }}</p>
                        </div>

                        @if ($isOwnProfile)
                            <a href="{{ route('profile.edit') }}"
                               class="shrink-0 flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-[13px] font-bold text-gray-700 hover:bg-gray-50 transition-all active:scale-95">
                                <i data-lucide="square-pen" class="w-3.5 h-3.5"></i>
                                Edit Profile
                            </a>
                        @else
                            @auth
                                <div x-data="{
                                        following: {{ $isFollowing ? 'true' : 'false' }},
                                        loading: false,
                                        async toggle() {
                                            if (this.loading) return;
                                            this.loading = true;
                                            try {
                                                const res = await fetch('{{ route('users.follow', $user) }}', {
                                                    method: 'POST',
                                                    headers: {
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                        'Accept': 'application/json',
                                                    }
                                                });
                                                const data = await res.json();
                                                this.following = data.following;
                                            } finally {
                                                this.loading = false;
                                            }
                                        }
                                     }">
                                    <button type="button"
                                            @click="toggle()"
                                            :disabled="loading"
                                            :class="following
                                                ? 'bg-white border-gray-200 text-gray-700 hover:border-red-200 hover:text-red-600 hover:bg-red-50'
                                                : 'bg-green-600 border-transparent text-white hover:bg-green-700'"
                                            class="shrink-0 rounded-lg border px-4 py-1.5 text-[13px] font-bold transition-all active:scale-95">
                                        <span x-text="following ? 'Following' : 'Follow'"></span>
                                    </button>
                                </div>
                            @else
                                <button type="button"
                                        onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                        class="shrink-0 rounded-lg border border-transparent bg-green-600 px-4 py-1.5 text-[13px] font-bold text-white hover:bg-green-700 transition-all active:scale-95">
                                    Follow
                                </button>
                            @endauth
                        @endif
                    </div>

                    {{-- Bio --}}
                    @if ($user->bio)
                        <p class="text-sm text-gray-600 mt-3 leading-relaxed">
                            {{ $user->bio }}
                        </p>
                    @endif

                    {{-- Stats --}}
                    <div class="flex items-center gap-6 mt-4">
                        <div class="text-center">
                            <div class="text-[15px] font-bold text-gray-900">
                                {{ number_format($user->posts_count) }}
                            </div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Posts</div>
                        </div>
                        <a href="{{ route('profile.followers', $user->username) }}"
                           class="text-center">
                            <div class="text-[15px] font-bold text-gray-900 hover:text-green-600 transition-colors">
                                {{ number_format($user->followers_count) }}
                            </div>
                            <div class="text-[11px] text-gray-400 hover:text-green-500 transition-colors mt-0.5">Followers</div>
                        </a>
                        <a href="{{ route('profile.following', $user->username) }}"
                           class="text-center">
                            <div class="text-[15px] font-bold text-gray-900 hover:text-green-600 transition-colors">
                                {{ number_format($user->following_count) }}
                            </div>
                            <div class="text-[11px] text-gray-400 hover:text-green-500 transition-colors mt-0.5">Following</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 mb-5 bg-white rounded-xl border border-gray-200 p-1">
            <a href="{{ route('profile.show', $user->username) }}?tab=posts"
               class="flex-1 text-center py-2 rounded-lg text-sm font-semibold transition-colors
                      {{ $tab === 'posts'
                          ? 'bg-green-600 text-white shadow-sm'
                          : 'text-gray-500 hover:bg-gray-50' }}">
                Posts
            </a>
            @if ($showLikedTab)
                <a href="{{ route('profile.show', $user->username) }}?tab=liked"
                   class="flex-1 text-center py-2 rounded-lg text-sm font-semibold transition-colors
                          {{ $tab === 'liked'
                              ? 'bg-green-600 text-white shadow-sm'
                              : 'text-gray-500 hover:bg-gray-50' }}">
                    Liked
                    @if ($isOwnProfile && !($user->preferences?->show_liked_posts ?? true))
                        <span class="ml-1 text-[10px] text-gray-400">(Private)</span>
                    @endif
                </a>
            @endif
        </div>

        {{-- Posts Container --}}
        <div id="posts-container" class="space-y-4">
            @include('profile._posts')

            @if ($posts->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <i data-lucide="message-circle" class="h-7 w-7 text-gray-400"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-400">
                        {{ $tab === 'liked' ? 'No liked posts yet' : 'No posts yet' }}
                    </p>
                </div>
            @endif
        </div>

        <div id="scroll-sentinel"></div>
        <div id="loading-indicator" style="display:none; text-align:center; padding:1rem;">
            Loading...
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const container = document.getElementById('posts-container');
            const sentinel  = document.getElementById('scroll-sentinel');
            const loader    = document.getElementById('loading-indicator');

            // Per-user + per-tab keys prevent cross-profile conflicts
            const SCROLL_KEY = 'profile_scroll_{{ $user->username }}_{{ $tab }}';
            const PAGE_KEY   = 'profile_page_{{ $user->username }}_{{ $tab }}';

            let currentPage = 1;
            let isFetching  = false;
            let hasMore     = true;

            const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

            async function fetchPage(page) {
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                const response = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            }

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
                    console.error('Failed to load:', err);
                    loader.innerHTML = 'Failed to load. Scroll to retry.';
                    loader.style.display = 'block';
                    setTimeout(() => {
                        loader.style.display = 'none';
                        loader.innerHTML = 'Loading...';
                    }, 3000);
                } finally {
                    isFetching = false;
                    if (success) loader.style.display = 'none';
                }
            }

            const observer = new IntersectionObserver(
                (entries) => { if (entries[0].isIntersecting) loadMore(); },
                { rootMargin: '200px' }
            );

            if (sentinel) observer.observe(sentinel);

            container.addEventListener('click', (e) => {
                const postLink = e.target.closest('a[href*="/posts/"]');
                if (postLink) {
                    sessionStorage.setItem(SCROLL_KEY, window.scrollY);
                    sessionStorage.setItem(PAGE_KEY, currentPage);
                }
            });

            async function restoreScrollState() {
                const savedScroll = sessionStorage.getItem(SCROLL_KEY);
                const savedPage   = parseInt(sessionStorage.getItem(PAGE_KEY) || '1');

                if (!savedScroll || savedPage <= 1) return;

                sessionStorage.removeItem(SCROLL_KEY);
                sessionStorage.removeItem(PAGE_KEY);

                loader.style.display = 'block';
                loader.textContent = 'Restoring your place...';

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
                        break;
                    }
                }

                loader.style.display = 'none';
                loader.textContent = 'Loading...';

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        window.scrollTo({ top: parseInt(savedScroll), behavior: 'instant' });
                    });
                });
            }

            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    const savedScroll = sessionStorage.getItem(SCROLL_KEY);
                    if (savedScroll) {
                        sessionStorage.removeItem(SCROLL_KEY);
                        sessionStorage.removeItem(PAGE_KEY);
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                window.scrollTo({ top: parseInt(savedScroll), behavior: 'instant' });
                            });
                        });
                    }
                } else {
                    restoreScrollState();
                }
            });
        })();
    </script>
    @endpush
</x-app-layout>