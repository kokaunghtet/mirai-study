<x-app-layout>
    <x-slot name="title">{{ $user->display_name }} — MiraiStudy</x-slot>

    <div class="max-w-[720px] mx-auto">

        <!-- Back button to go back to feed page -->
        <a href="{{ route('feed.index') }}"
           class="mb-5 inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-3 py-1.5 text-sm font-semibold text-muted shadow-sm transition-all hover:bg-accent/10 hover:text-accent hover:border-accent/30 active:scale-95">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Back
        </a>

        {{-- Profile Header --}}
        <div class="bg-surface rounded-2xl border border-line p-6 mb-5">
            <div class="flex items-start gap-5">

                {{-- Avatar --}}
                <div class="shrink-0" @if ($user->profile_image) x-data="{ open: false }" @endif>
                    @if ($user->profile_image)
                        <img src="{{ $user->profile_image }}"
                             alt="{{ $user->display_name }}"
                             loading="lazy"
                             @click="open = true"
                             class="w-20 h-20 rounded-full object-cover border-2 border-line cursor-pointer hover:opacity-90 transition-opacity">

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
                        <div class="w-20 h-20 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold text-2xl border-2 border-line">
                            {{ strtoupper(substr($user->display_name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">

                    {{-- Name + Action button --}}
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h1 class="text-xl font-bold text-content leading-tight">
                                {{ $user->display_name }}
                            </h1>
                            <p class="text-sm text-muted mt-0.5">{{ '@'.$user->username }}</p>
                        </div>

                        @if ($isOwnProfile)
                            <a href="{{ route('profile.edit') }}"
                               class="shrink-0 flex items-center gap-1.5 rounded-lg border border-line bg-surface px-3 py-1.5 text-[13px] font-bold text-content hover:bg-surface-muted transition-all active:scale-95">
                                <i data-lucide="square-pen" class="w-3.5 h-3.5"></i>
                                Edit Profile
                            </a>
                        @else
                            <div class="flex items-center gap-2 shrink-0">
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
                                                    ? 'bg-surface border-line text-content hover:border-red-200 hover:text-red-600 hover:bg-red-50'
                                                    : 'bg-gradient-to-tr from-accent-from to-accent-to border-transparent text-white hover:opacity-90'"
                                                class="shrink-0 rounded-lg border px-4 py-1.5 text-[13px] font-bold transition-all active:scale-95">
                                            <span x-text="following ? 'Following' : 'Follow'"></span>
                                        </button>
                                    </div>
                                @else
                                    <button type="button"
                                            onclick="window.dispatchEvent(new Event('open-auth-modal'))"
                                            class="shrink-0 rounded-lg border border-transparent bg-gradient-to-tr from-accent-from to-accent-to px-4 py-1.5 text-[13px] font-bold text-white hover:opacity-90 transition-all active:scale-95">
                                        Follow
                                    </button>
                                @endauth

                                @auth
                                    @if (auth()->user()->isAdmin() || auth()->user()->isModerator())
                                        @if (! $user->isAdmin() && ! (auth()->user()->isModerator() && $user->isModerator()) && ! $user->isBannedNow())
                                        <div x-data="{
                                                open: false,
                                                form: null,
                                                duration: null,
                                                reason: '',
                                                loading: false,
                                                errorMsg: '',
                                                openForm(type) { this.form = type; this.open = false; this.duration = null; this.reason = ''; this.errorMsg = ''; },
                                                async ban() {
                                                    if (this.loading) return;
                                                    this.loading = true;
                                                    this.errorMsg = '';
                                                    try {
                                                        const body = { type: this.form, reason: this.reason };
                                                        if (this.form === 'temp') body.duration = this.duration;
                                                        const res = await fetch('{{ route('admin.users.ban', $user) }}', {
                                                            method: 'POST',
                                                            headers: {
                                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                                'Content-Type': 'application/json',
                                                                'Accept': 'application/json',
                                                            },
                                                            body: JSON.stringify(body),
                                                        });
                                                        if (!res.ok) {
                                                            const err = await res.json().catch(() => ({}));
                                                            this.errorMsg = err.message || 'Action failed.';
                                                            return;
                                                        }
                                                        window.location.reload();
                                                    } catch (e) {
                                                        this.errorMsg = 'Network error.';
                                                    } finally {
                                                        this.loading = false;
                                                    }
                                                }
                                             }" class="relative shrink-0">

                                            {{-- Single wrapper captures outside-clicks for the whole component --}}
                                            <div @click.outside="open = false; form = null"
                                                 @keydown.escape.window="open = false; form = null">

                                                {{-- Trigger --}}
                                                <button @click="form ? form = null : open = !open"
                                                        type="button"
                                                        class="flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-[12px] font-semibold text-red-600 hover:bg-red-100 dark:border-red-900/40 dark:bg-red-950/20 dark:hover:bg-red-950/40 transition-all active:scale-95">
                                                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                                    <span>Mod</span>
                                                    <i data-lucide="chevron-down" class="w-3 h-3" :class="(open || form) && 'rotate-180'" style="transition:transform 0.15s"></i>
                                                </button>

                                                {{-- Dropdown --}}
                                                <div x-show="open" x-cloak
                                                     class="absolute right-0 top-9 z-50 w-36 overflow-hidden rounded-xl border border-line bg-surface py-1 text-[13px] font-semibold shadow-lg">
                                                    <button @click="openForm('temp')"
                                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-amber-600 hover:bg-surface-muted transition-colors">
                                                        <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                                        Temp ban
                                                    </button>
                                                    <button @click="openForm('perm')"
                                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-600 hover:bg-surface-muted transition-colors">
                                                        <i data-lucide="ban" class="h-3.5 w-3.5"></i>
                                                        Perm ban
                                                    </button>
                                                </div>

                                                {{-- Temp ban form --}}
                                                <div x-show="form === 'temp'" x-cloak
                                                     class="absolute right-0 top-9 z-50 w-56 rounded-xl border border-line bg-surface p-3 shadow-lg">
                                                    <p class="mb-2 text-[11px] font-bold text-content">Temp ban duration</p>
                                                    <div class="mb-2 flex flex-wrap gap-1.5">
                                                        @foreach ([1 => '1d', 3 => '3d', 7 => '7d', 30 => '30d'] as $days => $lbl)
                                                            <button type="button"
                                                                    @click="duration = {{ $days }}"
                                                                    :class="duration === {{ $days }} ? 'bg-accent text-white' : 'border border-line bg-surface-muted text-muted hover:text-content'"
                                                                    class="rounded-lg px-2.5 py-1 text-[10px] font-semibold transition-colors">
                                                                {{ $lbl }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                    <label class="mb-1 block text-[10px] text-muted">Reason <span class="text-muted/60">(optional)</span></label>
                                                    <input type="text" x-model="reason" maxlength="200" placeholder="Brief reason…"
                                                           class="mb-2 w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20">
                                                    <p x-show="errorMsg" x-text="errorMsg" x-cloak class="mb-1 text-[10px] text-red-500"></p>
                                                    <div class="flex gap-1.5">
                                                        <button @click="ban()" :disabled="!duration || loading"
                                                                class="flex-1 rounded-lg bg-amber-100 py-1 text-[10px] font-bold text-amber-700 hover:bg-amber-200 disabled:opacity-40 dark:bg-amber-900/30 dark:text-amber-400 transition-colors">
                                                            Confirm
                                                        </button>
                                                        <button @click="form = null" type="button"
                                                                class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- Perm ban form --}}
                                                <div x-show="form === 'perm'" x-cloak
                                                     class="absolute right-0 top-9 z-50 w-56 rounded-xl border border-line bg-surface p-3 shadow-lg">
                                                    <p class="mb-2 text-[11px] font-bold text-content">Permanent ban</p>
                                                    <label class="mb-1 block text-[10px] text-muted">Reason <span class="text-muted/60">(optional)</span></label>
                                                    <input type="text" x-model="reason" maxlength="200" placeholder="Brief reason…"
                                                           class="mb-2 w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20">
                                                    <p x-show="errorMsg" x-text="errorMsg" x-cloak class="mb-1 text-[10px] text-red-500"></p>
                                                    <div class="flex gap-1.5">
                                                        <button @click="ban()" :disabled="loading"
                                                                class="flex-1 rounded-lg bg-red-100 py-1 text-[10px] font-bold text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 transition-colors">
                                                            Confirm
                                                        </button>
                                                        <button @click="form = null" type="button"
                                                                class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </div>

                                            </div>{{-- end @click.outside wrapper --}}
                                        </div>
                                        @endif
                                    @elseif (! $user->isAdmin())
                                        <button type="button"
                                                title="Report this user"
                                                onclick="window.dispatchEvent(new CustomEvent('open-report', { detail: { type: 'user', id: {{ $user->id }} } }))"
                                                class="shrink-0 flex items-center gap-1 rounded-lg border border-line bg-surface px-2.5 py-1.5 text-[12px] font-semibold text-muted hover:text-red-600 hover:border-red-200 hover:bg-red-50 dark:hover:bg-red-950/20 transition-all active:scale-95">
                                            <i data-lucide="flag" class="w-3.5 h-3.5"></i>
                                            <span>Report</span>
                                        </button>
                                    @endif
                                @endauth
                            </div>
                        @endif
                    </div>

                    {{-- Bio --}}
                    @if ($user->bio)
                        <p class="text-sm text-muted mt-3 leading-relaxed">
                            {{ $user->bio }}
                        </p>
                    @endif

                    {{-- Stats --}}
                    <div class="flex items-center gap-6 mt-4">
                        <div class="text-center">
                            <div class="text-[15px] font-bold text-content">
                                {{ number_format($user->posts_count) }}
                            </div>
                            <div class="text-[11px] text-muted mt-0.5">Posts</div>
                        </div>
                        <a href="{{ route('profile.followers', $user->username) }}"
                           class="text-center">
                            <div class="text-[15px] font-bold text-content hover:text-accent transition-colors">
                                {{ number_format($user->followers_count) }}
                            </div>
                            <div class="text-[11px] text-muted hover:text-accent transition-colors mt-0.5">Followers</div>
                        </a>
                        <a href="{{ route('profile.following', $user->username) }}"
                           class="text-center">
                            <div class="text-[15px] font-bold text-content hover:text-accent transition-colors">
                                {{ number_format($user->following_count) }}
                            </div>
                            <div class="text-[11px] text-muted hover:text-accent transition-colors mt-0.5">Following</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 mb-5 bg-surface rounded-xl border border-line p-1">
            <a href="{{ route('profile.show', $user->username) }}?tab=posts"
               class="flex-1 text-center py-2 rounded-lg text-sm font-semibold transition-colors
                       {{ $tab === 'posts'
                          ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white shadow-sm'
                          : 'text-muted hover:bg-surface-muted' }}">
                Posts
            </a>
            @if ($isOwnProfile)
                <a href="{{ route('profile.show', $user->username) }}?tab=bookmarks"
                   class="flex-1 text-center py-2 rounded-lg text-sm font-semibold transition-colors
                           {{ $tab === 'bookmarks'
                               ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white shadow-sm'
                               : 'text-muted hover:bg-surface-muted' }}">
                    Bookmarks
                </a>
            @endif
            @if ($showLikedTab)
                <a href="{{ route('profile.show', $user->username) }}?tab=liked"
                   class="flex-1 text-center py-2 rounded-lg text-sm font-semibold transition-colors
                           {{ $tab === 'liked'
                               ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white shadow-sm'
                               : 'text-muted hover:bg-surface-muted' }}">
                    Liked
                    @if ($isOwnProfile && !($user->preferences?->show_liked_posts ?? true))
                        <span class="ml-1 text-[10px] text-muted">(Private)</span>
                    @endif
                </a>
            @endif
        </div>

        {{-- Posts Container --}}
        <div id="posts-container" class="space-y-4">
            @include('profile._posts')

            @if ($posts->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-14 h-14 rounded-full bg-surface-muted flex items-center justify-center mb-4">
                        <i data-lucide="message-circle" class="h-7 w-7 text-muted"></i>
                    </div>
                    <p class="text-sm font-semibold text-muted">
                        {{ $tab === 'liked' ? 'No liked posts yet' : ($tab === 'bookmarks' ? 'No bookmarked posts yet' : 'No posts yet') }}
                    </p>
                </div>
            @endif
        </div>

        <div id="scroll-sentinel"></div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const container = document.getElementById('posts-container');
            const sentinel  = document.getElementById('scroll-sentinel');
            const SKELETON = `<div class="post-skeleton relative rounded-2xl bg-surface border border-line shadow-sm animate-pulse"><div class="flex items-center justify-between px-4 py-3.5"><div class="flex items-center gap-2.5"><div class="h-[38px] w-[38px] shrink-0 rounded-full bg-surface-muted"></div><div class="space-y-1.5"><div class="h-3 w-24 rounded-md bg-surface-muted"></div><div class="h-2.5 w-16 rounded-md bg-surface-muted"></div></div></div><div class="h-7 w-20 rounded-lg bg-surface-muted"></div></div><div class="px-4 pb-3 space-y-2.5"><div class="h-3 w-full rounded-md bg-surface-muted"></div><div class="h-3 w-4/5 rounded-md bg-surface-muted"></div><div class="h-3 w-3/5 rounded-md bg-surface-muted"></div></div><div class="flex items-center justify-between px-3.5 py-3 border-t border-line"><div class="flex items-center gap-2"><div class="h-7 w-14 rounded-lg bg-surface-muted"></div><div class="h-7 w-14 rounded-lg bg-surface-muted"></div></div><div class="flex items-center gap-2"><div class="h-7 w-8 rounded-lg bg-surface-muted"></div><div class="h-7 w-8 rounded-lg bg-surface-muted"></div></div></div></div>`;
            const showSkeletons = (n = 3) => container.insertAdjacentHTML('beforeend', SKELETON.repeat(n));
            const removeSkeletons = () => container.querySelectorAll('.post-skeleton').forEach(el => el.remove());

            // Per-user + per-tab keys prevent cross-profile conflicts
            const SCROLL_KEY = 'profile_scroll_{{ $user->username }}_{{ $tab }}';
            const PAGE_KEY   = 'profile_page_{{ $user->username }}_{{ $tab }}';

            let currentPage = 1;
            let isFetching  = false;
            let hasMore     = {{ $posts->isEmpty() ? 'false' : 'true' }};

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
                currentPage++;
                showSkeletons();

                try {
                    const [data] = await Promise.all([
                        fetchPage(currentPage),
                        sleep(1000)
                    ]);
                    removeSkeletons();
                    window.appendWithIcons(container, data.html);
                    success = true;

                    if (!data.next_page_url) {
                        hasMore = false;
                        observer.disconnect();
                        if (sentinel) sentinel.remove();
                    }
                } catch (err) {
                    currentPage--;
                    removeSkeletons();
                    console.error('Failed to load:', err);
                } finally {
                    isFetching = false;
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