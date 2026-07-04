<x-app-layout>
    <x-slot name="title">Dashboard — MiraiStudy Admin</x-slot>

    <div class="px-4 pb-10" x-data="adminDashboard()">
        <div class="max-w-6xl mx-auto">

            {{-- Header --}}
            <header class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Admin Dashboard</h1>
                    <p class="mt-1 text-sm text-muted">Platform overview and quick actions.</p>
                </div>
                <a href="{{ route('admin.analytics') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-tr from-accent-from to-accent-to px-4 py-2.5 text-sm font-bold text-white hover:opacity-90 transition">
                    <i data-lucide="bar-chart-2" class="h-4 w-4"></i>
                    Analytics
                </a>
            </header>

            {{-- Stat Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">

                {{-- Users --}}
                <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">Users</span>
                        <i data-lucide="users" class="h-4 w-4 text-muted"></i>
                    </div>
                    <div class="text-3xl font-bold text-content">{{ number_format($stats['total_users']) }}</div>
                    <div class="mt-2 space-y-1 text-xs text-muted">
                        <div class="flex items-center gap-1">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            {{ number_format($stats['active_users']) }} active
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
                            {{ number_format($stats['suspended_users']) }} suspended
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
                            {{ number_format($stats['banned_users']) }} banned
                        </div>
                        @if ($trends['users'] !== 0)
                            <div class="flex items-center gap-0.5 {{ $trends['users'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                <i data-lucide="{{ $trends['users'] > 0 ? 'trending-up' : 'trending-down' }}" class="h-3 w-3"></i>
                                {{ $trends['users'] > 0 ? '+' : '' }}{{ $trends['users'] }} this week
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Posts --}}
                <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">Posts</span>
                        <i data-lucide="file-text" class="h-4 w-4 text-muted"></i>
                    </div>
                    <div class="text-3xl font-bold text-content">{{ number_format($stats['total_posts']) }}</div>
                    <div class="mt-2 space-y-1 text-xs text-muted">
                        <div>+{{ number_format($stats['posts_today']) }} today</div>
                        @if ($trends['posts'] !== 0)
                            <div class="flex items-center gap-0.5 {{ $trends['posts'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                <i data-lucide="{{ $trends['posts'] > 0 ? 'trending-up' : 'trending-down' }}" class="h-3 w-3"></i>
                                {{ $trends['posts'] > 0 ? '+' : '' }}{{ $trends['posts'] }} this week
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Reports --}}
                <div class="rounded-2xl border border-line p-5 shadow-sm {{ $stats['pending_reports'] > 0 ? 'bg-accent/10 border-accent/30' : 'bg-surface' }}">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider {{ $stats['pending_reports'] > 0 ? 'text-accent' : 'text-muted' }}">Reports</span>
                        <i data-lucide="flag" class="h-4 w-4 {{ $stats['pending_reports'] > 0 ? 'text-accent' : 'text-muted' }}"></i>
                    </div>
                    <div class="text-3xl font-bold {{ $stats['pending_reports'] > 0 ? 'text-accent' : 'text-content' }}">
                        {{ number_format($stats['pending_reports']) }}
                    </div>
                    <div class="mt-2 space-y-1 text-xs {{ $stats['pending_reports'] > 0 ? 'text-accent/80' : 'text-muted' }}">
                        <div>pending review</div>
                        @if ($trends['reports'] !== 0)
                            <div class="flex items-center gap-0.5 {{ $trends['reports'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                <i data-lucide="{{ $trends['reports'] > 0 ? 'trending-up' : 'trending-down' }}" class="h-3 w-3"></i>
                                {{ $trends['reports'] > 0 ? '+' : '' }}{{ $trends['reports'] }} this week
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Appeals --}}
                <div class="rounded-2xl border border-line p-5 shadow-sm {{ $stats['pending_appeals'] > 0 ? 'bg-red-50 border-red-200 dark:bg-red-950/20 dark:border-red-900/40' : 'bg-surface' }}">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider {{ $stats['pending_appeals'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-muted' }}">Appeals</span>
                        <i data-lucide="shield-check" class="h-4 w-4 {{ $stats['pending_appeals'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-muted' }}"></i>
                    </div>
                    <div class="text-3xl font-bold {{ $stats['pending_appeals'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-content' }}">
                        {{ number_format($stats['pending_appeals']) }}
                    </div>
                    <div class="mt-2 space-y-1 text-xs {{ $stats['pending_appeals'] > 0 ? 'text-red-500 dark:text-red-500' : 'text-muted' }}">
                        <div>pending review</div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">Content</span>
                        <i data-lucide="book-open" class="h-4 w-4 text-muted"></i>
                    </div>
                    <div class="text-3xl font-bold text-content">{{ number_format($stats['total_papers']) }}</div>
                    <div class="mt-2 space-y-1 text-xs text-muted">
                        <div>papers · {{ number_format($stats['total_questions']) }} questions</div>
                        @php $contentDelta = $trends['papers'] + $trends['questions']; @endphp
                        @if ($contentDelta !== 0)
                            <div class="flex items-center gap-0.5 {{ $contentDelta > 0 ? 'text-green-600' : 'text-red-600' }}">
                                <i data-lucide="{{ $contentDelta > 0 ? 'trending-up' : 'trending-down' }}" class="h-3 w-3"></i>
                                {{ $contentDelta > 0 ? '+' : '' }}{{ $contentDelta }} this week
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Quick Nav --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-8">
                <a href="{{ route('admin.users') }}"
                   class="flex items-center gap-2.5 rounded-xl border border-line bg-surface px-4 py-3 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="users" class="h-4 w-4 text-accent shrink-0"></i>
                    Manage Users
                </a>
                <a href="{{ route('admin.reports') }}"
                   class="flex items-center gap-2.5 rounded-xl border border-line bg-surface px-4 py-3 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="flag" class="h-4 w-4 text-accent shrink-0"></i>
                    Reports
                    @if ($stats['pending_reports'] > 0)
                        <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1.5 text-[10px] font-bold text-white">
                            {{ $stats['pending_reports'] }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('admin.papers') }}"
                   class="flex items-center gap-2.5 rounded-xl border border-line bg-surface px-4 py-3 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="file-text" class="h-4 w-4 text-accent shrink-0"></i>
                    Exam Papers
                </a>
                <a href="{{ route('admin.questions') }}"
                   class="flex items-center gap-2.5 rounded-xl border border-line bg-surface px-4 py-3 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="brain" class="h-4 w-4 text-accent shrink-0"></i>
                    Questions
                </a>
                <a href="{{ route('admin.appeals') }}"
                   class="flex items-center gap-2.5 rounded-xl border border-line bg-surface px-4 py-3 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="shield-check" class="h-4 w-4 text-accent shrink-0"></i>
                    Appeals
                    @if ($stats['pending_appeals'] > 0)
                        <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">
                            {{ $stats['pending_appeals'] }}
                        </span>
                    @endif
                </a>
            </div>

            {{-- Activity Feed --}}
            @include('admin.partials._activity-feed', ['activityItems' => $activityItems])

            {{-- Bottom grid: Recent Users + Pending Reports --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Recent Users --}}
                <section class="rounded-2xl border border-line bg-surface shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-line">
                        <h2 class="text-sm font-bold text-content">Recent Users</h2>
                        <a href="{{ route('admin.users') }}" class="text-xs font-semibold text-accent hover:underline">
                            View all
                        </a>
                    </div>
                    @if ($recent_users->isEmpty())
                        <div class="px-5 py-8 text-center text-sm text-muted">No users yet.</div>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($recent_users as $user)
                                <li class="flex items-center gap-3 px-5 py-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent/10 text-xs font-bold text-accent">
                                        {{ strtoupper(substr($user->display_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-semibold text-content">{{ $user->display_name }}</div>
                                        <div class="text-xs text-muted">{{'@'.$user->username }}</div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        @if ($user->role === 'admin')
                                            <span class="rounded-full bg-accent px-2 py-0.5 text-[10px] font-bold text-white">Admin</span>
                                        @elseif ($user->role === 'moderator')
                                            <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[10px] font-bold text-muted border border-line">Mod</span>
                                        @endif
                                        @if ($user->status === 'banned')
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700 dark:bg-red-900/30 dark:text-red-400">Banned</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Pending Reports --}}
                <section class="rounded-2xl border border-line bg-surface shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-line">
                        <h2 class="text-sm font-bold text-content">Pending Reports</h2>
                        <a href="{{ route('admin.reports') }}" class="text-xs font-semibold text-accent hover:underline">
                            View all
                        </a>
                    </div>
                    @if ($recent_reports->isEmpty())
                        <div class="flex flex-col items-center gap-2 px-5 py-8 text-center text-sm text-muted">
                            <i data-lucide="check-circle" class="h-6 w-6 text-green-500"></i>
                            No pending reports.
                        </div>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($recent_reports as $report)
                                <li class="px-5 py-3">
                                    <div class="flex items-center justify-between gap-2 mb-0.5">
                                        <span class="text-xs font-semibold text-content">
                                            {{ '@' . ($report->reporter?->username ?? 'deleted') }}
                                        </span>
                                        <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[10px] font-semibold text-muted border border-line capitalize">
                                            {{ $report->target_type }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-muted truncate">{{ Str::limit($report->reason ?? $report->category, 60) }}</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <p class="flex-1 text-[10px] text-muted">{{ $report->created_at->diffForHumans() }}</p>
                                        <a href="{{ route('admin.reports') }}"
                                           class="rounded-lg bg-accent/10 px-2 py-1 text-[10px] font-semibold text-accent hover:bg-accent/20 transition-colors">
                                            Review →
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

            </div>

            {{-- Pending Appeals --}}
            @if ($stats['pending_appeals'] > 0)
            <section class="mt-6 rounded-2xl border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-950/20 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-red-200 dark:border-red-900/40">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-alert" class="h-4 w-4 text-red-600 dark:text-red-400"></i>
                        <h2 class="text-sm font-bold text-red-700 dark:text-red-400">Pending Appeals</h2>
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">{{ $stats['pending_appeals'] }}</span>
                    </div>
                    <a href="{{ route('admin.appeals') }}" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">
                        Review all
                    </a>
                </div>
                @if ($recent_appeals->isEmpty())
                    <div class="px-5 py-6 text-center text-sm text-muted">No recent appeals.</div>
                @else
                    <ul class="divide-y divide-red-200 dark:divide-red-900/30">
                        @foreach ($recent_appeals as $appeal)
                            <li class="px-5 py-3">
                                <div class="flex items-center justify-between gap-2 mb-0.5">
                                    <span class="text-xs font-semibold text-content">
                                        {{ '@' . ($appeal->user?->username ?? 'deleted') }}
                                    </span>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' => $appeal->ban?->type === 'permanent',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' => $appeal->ban?->type === 'temporary',
                                    ])>
                                        {{ ucfirst($appeal->ban?->type ?? 'ban') }}
                                    </span>
                                </div>
                                <p class="text-xs text-muted truncate italic">"{{ Str::limit($appeal->message, 70) }}"</p>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <p class="flex-1 text-[10px] text-muted">{{ $appeal->created_at->diffForHumans() }}</p>
                                    <a href="{{ route('admin.appeals') }}"
                                       class="rounded-lg bg-red-100 dark:bg-red-900/30 px-2 py-1 text-[10px] font-semibold text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                                        Review →
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
            @endif

            {{-- System Health --}}
            <section class="mt-6 rounded-2xl border border-line bg-surface shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-line">
                    <h2 class="text-sm font-bold text-content">System Health</h2>
                    <i data-lucide="server" class="h-4 w-4 text-muted"></i>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-line">
                    <div class="px-5 py-4">
                        <div class="text-xs font-semibold text-muted mb-1">Queue Jobs</div>
                        <div class="text-lg font-bold {{ $health['queue_size'] > 50 ? 'text-red-600' : 'text-content' }}">{{ $health['queue_size'] }}</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-xs font-semibold text-muted mb-1">Failed Jobs</div>
                        <div class="text-lg font-bold {{ $health['failed_jobs'] > 0 ? 'text-red-600' : 'text-content' }}">{{ $health['failed_jobs'] }}</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-xs font-semibold text-muted mb-1">Storage Used</div>
                        <div class="text-lg font-bold text-content">{{ $health['storage_used'] }}</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-xs font-semibold text-muted mb-1">Last Deploy</div>
                        <div class="text-lg font-bold text-content">{{ $health['last_deploy'] ?? '—' }}</div>
                    </div>
                </div>
            </section>

        </div>
    </div>

    @push('scripts')
    <script>
        function adminDashboard() {
            return {
                resolveReport(reportId, status, btn) {
                    fetch(`/admin/reports/${reportId}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ status }),
                    }).catch(() => {});
                },
            };
        }

        // Auto-refresh activity feed every 60s
        setInterval(() => {
            fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            }).then(r => r.text()).then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const feed = doc.querySelector('[data-activity-feed]');
                if (feed) {
                    const current = document.querySelector('[data-activity-feed]');
                    if (current) {
                        current.innerHTML = feed.innerHTML;
                        window.renderIcons?.(current);
                    }
                }
            }).catch(() => {});
        }, 60000);
    </script>
    @endpush
</x-app-layout>
