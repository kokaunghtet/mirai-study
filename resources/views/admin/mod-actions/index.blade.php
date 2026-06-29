<x-app-layout>
    <x-slot name="title">Mod Actions — MiraiStudy Admin</x-slot>

    <div class="px-4 pb-10">
        <div class="max-w-5xl mx-auto">

            {{-- Header --}}
            <header class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Mod Actions</h1>
                    <p class="mt-1 text-sm text-muted">Direct staff actions — content removals and bans outside the report queue.</p>
                </div>
                <a href="{{ route('admin.reports') }}"
                   class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-line bg-surface px-4 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Reports
                </a>
            </header>

            {{-- Action type filter chips --}}
            <div class="mb-5 flex flex-wrap items-center gap-2">
                @foreach (['' => 'All', 'content_removed' => 'Removed', 'user_temp_banned' => 'Temp banned', 'user_perm_banned' => 'Perm banned'] as $val => $label)
                    @php $on = request('action', '') === $val; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['action' => $val ?: null, 'page' => null]) }}"
                       class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors
                              {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if ($logs->isEmpty())
                <div class="rounded-2xl border border-line bg-surface px-6 py-12 text-center">
                    <i data-lucide="shield-check" class="mx-auto mb-3 h-8 w-8 text-green-500"></i>
                    <p class="text-sm font-semibold text-content">No actions recorded.</p>
                    <p class="mt-1 text-xs text-muted">Nothing matches the current filter.</p>
                </div>
            @else
                <div class="rounded-2xl border border-line bg-surface shadow-sm overflow-visible">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-line bg-surface-muted">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted rounded-tl-2xl">Actor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Target</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden md:table-cell">Details</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden sm:table-cell rounded-tr-2xl">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($logs as $log)
                                @php
                                    $subject = match ($log->subject_type) {
                                        'Post'    => $postsMap[$log->subject_id] ?? null,
                                        'Comment' => $commentsMap[$log->subject_id] ?? null,
                                        'User'    => $usersMap[$log->subject_id] ?? null,
                                        default   => null,
                                    };
                                    $props = $log->properties ?? [];
                                @endphp
                                <tr class="hover:bg-surface-muted transition-colors">

                                    {{-- Actor --}}
                                    <td class="px-4 py-3 text-xs text-muted">
                                        @if ($log->user)
                                            <a href="{{ route('profile.show', $log->user->username) }}"
                                               class="font-semibold text-content hover:text-accent transition-colors">
                                                {{ '@'.$log->user->username }}
                                            </a>
                                            <div class="mt-0.5">
                                                <span class="rounded-full border border-line bg-surface-muted px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-muted">
                                                    {{ $log->user->role }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="italic">Deleted</span>
                                        @endif
                                    </td>

                                    {{-- Action badge --}}
                                    <td class="px-4 py-3">
                                        @php
                                            [$actionLabel, $actionClass] = match ($log->action) {
                                                'content_removed'  => ['Removed',     'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
                                                'user_temp_banned' => ['Temp banned', 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                                                'user_perm_banned' => ['Perm banned', 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
                                                default            => [$log->action,  'bg-surface-muted text-muted border border-line'],
                                            };
                                        @endphp
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $actionClass }}">
                                            {{ $actionLabel }}
                                        </span>
                                    </td>

                                    {{-- Target --}}
                                    <td class="px-4 py-3 text-xs">
                                        <span class="rounded-full border border-line bg-surface-muted px-2 py-0.5 text-[10px] font-semibold text-muted capitalize">
                                            {{ strtolower($log->subject_type) }}
                                        </span>
                                        @if ($log->subject_type === 'User' && $subject)
                                            <a href="{{ route('profile.show', $subject->username) }}"
                                               class="mt-0.5 block truncate max-w-[100px] text-[10px] text-accent hover:underline">
                                                {{ '@'.$subject->username }}
                                            </a>
                                        @elseif ($log->subject_type === 'User' && isset($props['username']))
                                            <span class="mt-0.5 block text-[10px] text-muted italic">@{{ $props['username'] }} (deleted)</span>
                                        @elseif ($log->subject_type === 'Post' && $subject)
                                            <a href="{{ route('posts.show', $subject) }}"
                                               class="mt-0.5 block truncate max-w-[120px] text-[10px] text-accent hover:underline">
                                                {{ Str::limit($subject->title ?: $subject->content, 30) }}
                                            </a>
                                            @if ($subject->trashed())
                                                <span class="text-[9px] font-semibold text-red-500">Deleted</span>
                                            @endif
                                        @elseif ($log->subject_type === 'Comment' && $subject)
                                            <span class="mt-0.5 block truncate max-w-[120px] text-[10px] text-muted">
                                                {{ Str::limit($subject->content, 30) }}
                                            </span>
                                            @if ($subject->trashed())
                                                <span class="text-[9px] font-semibold text-red-500">Deleted</span>
                                            @endif
                                        @else
                                            <span class="mt-0.5 block text-[10px] text-muted italic">Deleted</span>
                                        @endif
                                    </td>

                                    {{-- Details --}}
                                    <td class="px-4 py-3 text-xs text-muted hidden md:table-cell">
                                        @if (isset($props['days']))
                                            <span class="inline-block rounded-full bg-surface-muted border border-line px-2 py-0.5 text-[10px] font-semibold text-content">
                                                {{ $props['days'] }}d
                                            </span>
                                        @endif
                                        @if (isset($props['reason']) && $props['reason'])
                                            <div class="mt-0.5 truncate max-w-[160px]" title="{{ $props['reason'] }}">
                                                {{ Str::limit($props['reason'], 50) }}
                                            </div>
                                        @elseif (isset($props['report_id']))
                                            <span class="text-[10px] text-muted/60">via report #{{ $props['report_id'] }}</span>
                                        @else
                                            <span class="text-muted/50">—</span>
                                        @endif
                                    </td>

                                    {{-- When --}}
                                    <td class="px-4 py-3 text-xs text-muted hidden sm:table-cell whitespace-nowrap">
                                        {{ $log->created_at->diffForHumans() }}
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
