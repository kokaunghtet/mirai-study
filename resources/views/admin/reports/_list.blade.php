{{-- @list-partial --}}

{{-- Filters --}}
<div class="mb-5 flex flex-wrap items-center gap-2">
    {{-- Status chips --}}
    @foreach (['pending' => 'Pending', 'resolved' => 'Resolved', 'rejected' => 'Rejected'] as $val => $label)
        @php $on = request('status', 'pending') === $val; @endphp
        <a href="{{ request()->fullUrlWithQuery(['status' => $val, 'page' => null]) }}"
           class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors
                  {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
            {{ $label }}
        </a>
    @endforeach

    <span class="text-muted">·</span>

    {{-- Type chips --}}
    @foreach (['post' => 'Post', 'comment' => 'Comment', 'user' => 'User'] as $val => $label)
        @php $on = request('type') === $val; @endphp
        <a href="{{ request()->fullUrlWithQuery(['type' => $on ? null : $val, 'page' => null]) }}"
           class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors
                  {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@php
$categoryLabels = [
    'spam'           => 'Spam',
    'harassment'     => 'Harassment',
    'misinformation' => 'Misinformation',
    'inappropriate'  => 'Inappropriate',
    'other'          => 'Other',
];
$actionLabels = [
    'removed_content' => ['label' => 'Removed', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
    'temp_banned'     => ['label' => 'Temp banned', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
    'perm_banned'     => ['label' => 'Banned', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
    'temp_banned_removed' => ['label' => 'Temp banned + Removed', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
    'perm_banned_removed' => ['label' => 'Perm banned + Removed', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
    'none'            => ['label' => 'No action', 'class' => 'bg-surface-muted text-muted border border-line'],
];
@endphp

@if ($reports->isEmpty())
    <div class="rounded-2xl border border-line bg-surface px-6 py-12 text-center">
        <i data-lucide="check-circle" class="mx-auto mb-3 h-8 w-8 text-green-500"></i>
        <p class="text-sm font-semibold text-content">No reports found.</p>
        <p class="mt-1 text-xs text-muted">Nothing matches the current filter.</p>
    </div>
@else
    <div class="rounded-2xl border border-line bg-surface shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line bg-surface-muted">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Reporter</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Target</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden md:table-cell">Category / Detail</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden sm:table-cell">Reported</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden lg:table-cell">Reviewed by</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-muted">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($reports as $report)
                    <tr class="hover:bg-surface-muted transition-colors" id="report-row-{{ $report->id }}">

                        {{-- Reporter --}}
                        <td class="px-4 py-3 text-xs text-muted">
                            {{ '@' . ($report->reporter?->username ?? 'deleted') }}
                        </td>

                        {{-- Target type + link --}}
                        <td class="px-4 py-3">
                            <span class="rounded-full border border-line bg-surface-muted px-2 py-0.5 text-[10px] font-semibold text-muted capitalize">
                                {{ $report->target_type }}
                            </span>
                            @if ($report->target_type === 'user')
                                @php $targetUser = $report->target_model; @endphp
                                @if ($targetUser)
                                    <a href="{{ route('profile.show', $targetUser->username) }}"
                                       class="block mt-0.5 text-[10px] text-accent hover:underline truncate max-w-[80px]">
                                        {{ '@'.$targetUser->username }}
                                    </a>
                                @else
                                    <span class="block mt-0.5 text-[10px] text-muted italic">Deleted</span>
                                @endif
                            @elseif ($report->target_type === 'post')
                                @php $targetPost = $report->target_model; @endphp
                                @if ($targetPost)
                                    <a href="{{ route('posts.show', $targetPost) }}"
                                       class="block mt-0.5 text-[10px] text-accent hover:underline truncate max-w-[120px]">
                                        {{ Str::limit($targetPost->title ?: Str::limit($targetPost->content, 30), 25) }}
                                    </a>
                                    @if ($targetPost->trashed())
                                        <span class="text-[9px] text-red-500 font-semibold">Deleted</span>
                                    @endif
                                @else
                                    <span class="block mt-0.5 text-[10px] text-muted italic">Deleted</span>
                                @endif
                            @elseif ($report->target_type === 'comment')
                                @php $targetComment = $report->target_model; @endphp
                                @if ($targetComment)
                                    @php $parentPost = $report->target_parent_post; @endphp
                                    @if ($parentPost && ! $parentPost->trashed())
                                        <a href="{{ route('posts.show', $parentPost) }}"
                                           class="block mt-0.5 text-[10px] text-accent hover:underline truncate max-w-[120px]">
                                            {{ Str::limit($targetComment->content, 30) }}
                                        </a>
                                    @else
                                        <span class="block mt-0.5 text-[10px] text-muted truncate max-w-[120px]">
                                            {{ Str::limit($targetComment->content, 30) }}
                                        </span>
                                        <span class="text-[9px] text-muted italic">Post deleted</span>
                                    @endif
                                    @if ($targetComment->trashed())
                                        <span class="text-[9px] text-red-500 font-semibold">Deleted</span>
                                    @endif
                                @else
                                    <span class="block mt-0.5 text-[10px] text-muted italic">Deleted</span>
                                @endif
                            @endif
                        </td>

                        {{-- Category / Detail --}}
                        <td class="px-4 py-3 text-xs text-muted hidden md:table-cell max-w-xs">
                            <span class="inline-block rounded-full bg-surface-muted px-2 py-0.5 text-[10px] font-semibold text-content border border-line mb-0.5">
                                {{ $categoryLabels[$report->category] ?? $report->category }}
                            </span>
                            @if ($report->reason)
                                <div title="{{ $report->reason }}" class="mt-0.5 truncate max-w-[200px]">{{ Str::limit($report->reason, 60) }}</div>
                            @endif
                        </td>

                        {{-- Reported at --}}
                        <td class="px-4 py-3 text-xs text-muted hidden sm:table-cell whitespace-nowrap">
                            {{ $report->created_at->diffForHumans() }}
                        </td>

                        {{-- Reviewed by + action taken --}}
                        <td class="px-4 py-3 text-xs text-muted hidden lg:table-cell">
                            <div>{{ $report->reviewer?->username ?? '—' }}</div>
                            @if ($report->action_taken && isset($actionLabels[$report->action_taken]))
                                @php $al = $actionLabels[$report->action_taken]; @endphp
                                <span class="mt-0.5 inline-block rounded-full px-1.5 py-0.5 text-[9px] font-bold {{ $al['class'] }}">
                                    {{ $al['label'] }}
                                </span>
                            @endif
                        </td>

                        {{-- Status badge --}}
                        <td class="px-4 py-3">
                            <span id="report-badge-{{ $report->id }}"
                                  @class([
                                      'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                      'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' => $report->status === 'pending',
                                      'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $report->status === 'resolved',
                                      'bg-surface-muted text-muted border border-line' => $report->status === 'rejected',
                                  ])>
                                {{ ucfirst($report->status) }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right">
                            @if ($report->status === 'pending')
                                <div id="report-actions-{{ $report->id }}"
                                     x-data="reportActionMenu({{ $report->id }}, '{{ $report->target_type }}')">

                                    {{-- Dropdown trigger --}}
                                    <div @click.outside="open = false" @keydown.escape.window="open = false" @scroll.window="open = false">
                                        <button @click="toggle($event)"
                                                :disabled="loading"
                                                class="inline-flex items-center gap-1 rounded-lg border border-line bg-surface px-3 py-1.5 text-[11px] font-semibold text-content transition-colors hover:bg-surface-muted disabled:opacity-50">
                                            Actions
                                            <i data-lucide="chevron-down" class="h-3 w-3 transition-transform" :class="open && 'rotate-180'"></i>
                                        </button>

                                        {{-- Dropdown menu (fixed to escape overflow-hidden) --}}
                                        <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             :style="'position:fixed; left:' + dropX + 'px; top:' + dropY + 'px;'"
                                             class="z-50 w-48 origin-top-right rounded-xl border border-line bg-surface py-1.5 shadow-lg">

                                            {{-- Remove content --}}
                                            @if ($report->target_type !== 'user')
                                                <button @click="act('remove_content'); open = false"
                                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                                    <i data-lucide="trash-2" class="h-3.5 w-3.5 text-orange-500"></i>
                                                    Remove content
                                                </button>
                                            @endif

                                            {{-- Temp ban --}}
                                            <button @click="openBanForm('temp'); open = false"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                                <i data-lucide="clock" class="h-3.5 w-3.5 text-amber-500"></i>
                                                Temp ban
                                            </button>

                                            {{-- Temp ban & remove --}}
                                            @if ($report->target_type !== 'user')
                                                <button @click="openBanForm('temp_remove'); open = false"
                                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                                    <i data-lucide="clock-arrow-up" class="h-3.5 w-3.5 text-amber-500"></i>
                                                    Temp ban &amp; remove
                                                </button>
                                            @endif

                                            {{-- Perm ban --}}
                                            <button @click="openBanForm('perm'); open = false"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                                <i data-lucide="ban" class="h-3.5 w-3.5 text-red-500"></i>
                                                Perm ban
                                            </button>

                                            {{-- Perm ban & remove --}}
                                            @if ($report->target_type !== 'user')
                                                <button @click="openBanForm('perm_remove'); open = false"
                                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                                    <i data-lucide="shield-ban" class="h-3.5 w-3.5 text-red-500"></i>
                                                    Perm ban &amp; remove
                                                </button>
                                            @endif

                                            <div class="my-1 border-t border-line"></div>

                                            {{-- Reject --}}
                                            <button @click="act('reject'); open = false"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-muted hover:bg-surface-muted hover:text-content transition-colors">
                                                <i data-lucide="x-circle" class="h-3.5 w-3.5"></i>
                                                Reject report
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Temp-ban popover --}}
                                    <div x-show="showBanForm && banType === 'temp'" x-cloak
                                         class="mt-2 rounded-xl border border-line bg-surface p-3 text-left shadow-lg min-w-[200px]">
                                        <p class="text-[10px] font-bold text-content mb-2">Temporary ban duration</p>
                                        <div class="flex flex-wrap gap-1.5 mb-2">
                                            @foreach ([1 => '1d', 3 => '3d', 7 => '7d', 30 => '30d'] as $days => $lbl)
                                                <button type="button"
                                                        @click="duration = {{ $days }}"
                                                        :class="duration === {{ $days }} ? 'bg-accent text-white' : 'border border-line bg-surface-muted text-muted hover:text-content'"
                                                        class="rounded-lg px-2.5 py-1 text-[10px] font-semibold transition-colors">
                                                    {{ $lbl }}
                                                </button>
                                            @endforeach
                                        </div>
                                        <label class="block text-[10px] text-muted mb-1">Reason <span class="text-muted/60">(optional)</span></label>
                                        <input type="text" x-model="reason" maxlength="200"
                                               placeholder="Brief reason…"
                                               class="w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20 mb-2">
                                        <div class="flex gap-1.5">
                                            <button @click="act('temp_ban')" :disabled="!duration || loading"
                                                    class="flex-1 rounded-lg bg-amber-100 py-1 text-[10px] font-bold text-amber-700 hover:bg-amber-200 disabled:opacity-40 dark:bg-amber-900/30 dark:text-amber-400 transition-colors">
                                                <svg x-show="loading" style="display:none" class="leaf-spin inline-block h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                                                <span x-show="!loading">Confirm</span>
                                            </button>
                                            <button @click="showBanForm = false"
                                                    class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Temp-ban & remove popover --}}
                                    <div x-show="showBanForm && banType === 'temp_remove'" x-cloak
                                         class="mt-2 rounded-xl border border-line bg-surface p-3 text-left shadow-lg min-w-[200px]">
                                        <p class="text-[10px] font-bold text-content mb-2">Temp ban & remove content</p>
                                        <div class="flex flex-wrap gap-1.5 mb-2">
                                            @foreach ([1 => '1d', 3 => '3d', 7 => '7d', 30 => '30d'] as $days => $lbl)
                                                <button type="button"
                                                        @click="duration = {{ $days }}"
                                                        :class="duration === {{ $days }} ? 'bg-accent text-white' : 'border border-line bg-surface-muted text-muted hover:text-content'"
                                                        class="rounded-lg px-2.5 py-1 text-[10px] font-semibold transition-colors">
                                                    {{ $lbl }}
                                                </button>
                                            @endforeach
                                        </div>
                                        <label class="block text-[10px] text-muted mb-1">Reason <span class="text-muted/60">(optional)</span></label>
                                        <input type="text" x-model="reason" maxlength="200"
                                               placeholder="Brief reason…"
                                               class="w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20 mb-2">
                                        <div class="flex gap-1.5">
                                            <button @click="act('temp_ban_remove')" :disabled="!duration || loading"
                                                    class="flex-1 rounded-lg bg-amber-100 py-1 text-[10px] font-bold text-amber-700 hover:bg-amber-200 disabled:opacity-40 dark:bg-amber-900/30 dark:text-amber-400 transition-colors">
                                                <svg x-show="loading" style="display:none" class="leaf-spin inline-block h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                                                <span x-show="!loading">Confirm</span>
                                            </button>
                                            <button @click="showBanForm = false"
                                                    class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Perm-ban reason popover --}}
                                    <div x-show="showBanForm && banType === 'perm'" x-cloak
                                         class="mt-2 rounded-xl border border-line bg-surface p-3 text-left shadow-lg min-w-[200px]">
                                        <p class="text-[10px] font-bold text-content mb-2">Permanent ban</p>
                                        <label class="block text-[10px] text-muted mb-1">Reason <span class="text-muted/60">(optional)</span></label>
                                        <input type="text" x-model="reason" maxlength="200"
                                               placeholder="Brief reason…"
                                               class="w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20 mb-2">
                                        <div class="flex gap-1.5">
                                            <button @click="act('perm_ban')" :disabled="loading"
                                                    class="flex-1 rounded-lg bg-red-100 py-1 text-[10px] font-bold text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 transition-colors">
                                                <svg x-show="loading" style="display:none" class="leaf-spin inline-block h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                                                <span x-show="!loading">Confirm ban</span>
                                            </button>
                                            <button @click="showBanForm = false"
                                                    class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Perm-ban & remove popover --}}
                                    <div x-show="showBanForm && banType === 'perm_remove'" x-cloak
                                         class="mt-2 rounded-xl border border-line bg-surface p-3 text-left shadow-lg min-w-[200px]">
                                        <p class="text-[10px] font-bold text-content mb-2">Perm ban & remove content</p>
                                        <label class="block text-[10px] text-muted mb-1">Reason <span class="text-muted/60">(optional)</span></label>
                                        <input type="text" x-model="reason" maxlength="200"
                                               placeholder="Brief reason…"
                                               class="w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20 mb-2">
                                        <div class="flex gap-1.5">
                                            <button @click="act('perm_ban_remove')" :disabled="loading"
                                                    class="flex-1 rounded-lg bg-red-100 py-1 text-[10px] font-bold text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 transition-colors">
                                                <svg x-show="loading" style="display:none" class="leaf-spin inline-block h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                                                <span x-show="!loading">Confirm ban &amp; remove</span>
                                            </button>
                                            <button @click="showBanForm = false"
                                                    class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Error toast --}}
                                    <p x-show="errorMsg" x-cloak class="mt-1 text-[10px] text-red-500" x-text="errorMsg"></p>
                                </div>
                            @else
                                <span id="report-actions-{{ $report->id }}" class="text-xs text-muted">—</span>
                            @endif
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($reports->hasPages())
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    @endif
@endif
