{{-- @list-partial --}}

{{-- Filters --}}
<div class="mb-5 flex flex-wrap items-center gap-2">
    @foreach (['pending' => 'Pending', 'resolved' => 'Resolved', 'rejected' => 'Rejected'] as $val => $label)
        @php $on = request('status', 'pending') === $val; @endphp
        <a href="{{ request()->fullUrlWithQuery(['status' => $val, 'page' => null]) }}"
           class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors
                  {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
            {{ $label }}
        </a>
    @endforeach

    <span class="text-muted">·</span>

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
@endphp

@if ($reportGroups->isEmpty())
    <div class="rounded-2xl border border-line bg-surface px-6 py-12 text-center">
        <i data-lucide="check-circle" class="mx-auto mb-3 h-8 w-8 text-green-500"></i>
        <p class="text-sm font-semibold text-content">No pending reports.</p>
        <p class="mt-1 text-xs text-muted">All caught up.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach ($reportGroups as $group)
            @php $groupKey = $group->target_type . '-' . $group->target_id; @endphp

            <div id="group-row-{{ $groupKey }}"
                 class="rounded-2xl border border-line bg-surface shadow-sm overflow-hidden"
                 x-data="{ expanded: false }">

                {{-- Group header --}}
                <div class="flex items-center gap-3 px-4 py-3.5">

                    {{-- Target info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5 mb-1">
                            <span class="rounded-full border border-line bg-surface-muted px-2 py-0.5 text-[10px] font-semibold text-muted capitalize">
                                {{ $group->target_type }}
                            </span>

                            @if ($group->target_type === 'post')
                                @if ($group->target_model)
                                    <a href="{{ route('posts.show', $group->target_model) }}"
                                       class="text-[11px] text-accent hover:underline truncate max-w-xs">
                                        {{ Str::limit($group->target_model->title ?: $group->target_model->content, 50) }}
                                    </a>
                                    @if ($group->target_model->trashed())
                                        <span class="text-[9px] text-red-500 font-semibold">Deleted</span>
                                    @endif
                                @else
                                    <span class="text-[11px] text-muted italic">Deleted post</span>
                                @endif

                            @elseif ($group->target_type === 'user')
                                @if ($group->target_model)
                                    <a href="{{ route('profile.show', $group->target_model->username) }}"
                                       class="text-[11px] text-accent hover:underline">
                                        {{ '@'.$group->target_model->username }}
                                    </a>
                                @else
                                    <span class="text-[11px] text-muted italic">Deleted user</span>
                                @endif

                            @elseif ($group->target_type === 'comment')
                                @if ($group->target_model)
                                    @if ($group->target_parent_post && ! $group->target_parent_post->trashed())
                                        <a href="{{ route('posts.show', $group->target_parent_post) }}"
                                           class="text-[11px] text-accent hover:underline truncate max-w-xs">
                                            {{ Str::limit($group->target_model->content, 50) }}
                                        </a>
                                    @else
                                        <span class="text-[11px] text-muted truncate max-w-xs">{{ Str::limit($group->target_model->content, 50) }}</span>
                                        <span class="text-[9px] text-muted italic">Post deleted</span>
                                    @endif
                                    @if ($group->target_model->trashed())
                                        <span class="text-[9px] text-red-500 font-semibold">Deleted</span>
                                    @endif
                                @else
                                    <span class="text-[11px] text-muted italic">Deleted comment</span>
                                @endif
                            @endif
                        </div>
                        <div class="text-[10px] text-muted">First reported {{ $group->first_reported_at->diffForHumans() }}</div>
                    </div>

                    {{-- Reporter count + expand toggle --}}
                    <button @click="expanded = !expanded"
                            class="flex shrink-0 items-center gap-1.5 rounded-full border border-line bg-surface-muted px-2.5 py-1 text-[11px] font-semibold text-content transition-colors hover:bg-line">
                        <i data-lucide="flag" class="h-3 w-3 text-amber-500"></i>
                        {{ $group->count }} {{ Str::plural('report', $group->count) }}
                        <i data-lucide="chevron-down" class="h-3 w-3 transition-transform duration-150" :class="expanded && 'rotate-180'"></i>
                    </button>

                    {{-- Action menu --}}
                    <div x-data="reportActionMenu({{ $group->primary_id }}, '{{ $group->target_type }}', '{{ $groupKey }}')">
                        <div @click.outside="open = false" @keydown.escape.window="open = false" @scroll.window="open = false">
                            <button @click="toggle($event)"
                                    :disabled="loading"
                                    class="inline-flex items-center gap-1 rounded-lg border border-line bg-surface px-3 py-1.5 text-[11px] font-semibold text-content transition-colors hover:bg-surface-muted disabled:opacity-50">
                                Actions
                                <i data-lucide="chevron-down" class="h-3 w-3 transition-transform" :class="open && 'rotate-180'"></i>
                            </button>

                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 :style="'position:fixed; left:' + dropX + 'px; top:' + dropY + 'px;'"
                                 class="z-50 w-48 origin-top-right rounded-xl border border-line bg-surface py-1.5 shadow-lg">

                                @if ($group->target_type !== 'user')
                                    <button @click="act('remove_content'); open = false"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5 text-orange-500"></i>
                                        Remove content
                                    </button>
                                @endif

                                <button @click="openBanForm('temp'); open = false"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                    <i data-lucide="clock" class="h-3.5 w-3.5 text-amber-500"></i>
                                    Temp ban
                                </button>

                                @if ($group->target_type !== 'user')
                                    <button @click="openBanForm('temp_remove'); open = false"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                        <i data-lucide="clock-arrow-up" class="h-3.5 w-3.5 text-amber-500"></i>
                                        Temp ban &amp; remove
                                    </button>
                                @endif

                                <button @click="openBanForm('perm'); open = false"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                    <i data-lucide="ban" class="h-3.5 w-3.5 text-red-500"></i>
                                    Perm ban
                                </button>

                                @if ($group->target_type !== 'user')
                                    <button @click="openBanForm('perm_remove'); open = false"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-[11px] font-medium text-content hover:bg-surface-muted transition-colors">
                                        <i data-lucide="shield-ban" class="h-3.5 w-3.5 text-red-500"></i>
                                        Perm ban &amp; remove
                                    </button>
                                @endif

                                <div class="my-1 border-t border-line"></div>

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
                            <input type="text" x-model="reason" maxlength="200" placeholder="Brief reason…"
                                   class="w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20 mb-2">
                            <div class="flex gap-1.5">
                                <button @click="act('temp_ban')" :disabled="!duration || loading"
                                        class="flex-1 rounded-lg bg-amber-100 py-1 text-[10px] font-bold text-amber-700 hover:bg-amber-200 disabled:opacity-40 dark:bg-amber-900/30 dark:text-amber-400 transition-colors">
                                    Confirm
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
                            <input type="text" x-model="reason" maxlength="200" placeholder="Brief reason…"
                                   class="w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20 mb-2">
                            <div class="flex gap-1.5">
                                <button @click="act('temp_ban_remove')" :disabled="!duration || loading"
                                        class="flex-1 rounded-lg bg-amber-100 py-1 text-[10px] font-bold text-amber-700 hover:bg-amber-200 disabled:opacity-40 dark:bg-amber-900/30 dark:text-amber-400 transition-colors">
                                    Confirm
                                </button>
                                <button @click="showBanForm = false"
                                        class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        {{-- Perm-ban popover --}}
                        <div x-show="showBanForm && banType === 'perm'" x-cloak
                             class="mt-2 rounded-xl border border-line bg-surface p-3 text-left shadow-lg min-w-[200px]">
                            <p class="text-[10px] font-bold text-content mb-2">Permanent ban</p>
                            <label class="block text-[10px] text-muted mb-1">Reason <span class="text-muted/60">(optional)</span></label>
                            <input type="text" x-model="reason" maxlength="200" placeholder="Brief reason…"
                                   class="w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20 mb-2">
                            <div class="flex gap-1.5">
                                <button @click="act('perm_ban')" :disabled="loading"
                                        class="flex-1 rounded-lg bg-red-100 py-1 text-[10px] font-bold text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 transition-colors">
                                    Confirm ban
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
                            <input type="text" x-model="reason" maxlength="200" placeholder="Brief reason…"
                                   class="w-full rounded-lg border border-line bg-canvas px-2.5 py-1.5 text-xs text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/20 mb-2">
                            <div class="flex gap-1.5">
                                <button @click="act('perm_ban_remove')" :disabled="loading"
                                        class="flex-1 rounded-lg bg-red-100 py-1 text-[10px] font-bold text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 transition-colors">
                                    Confirm ban &amp; remove
                                </button>
                                <button @click="showBanForm = false"
                                        class="rounded-lg border border-line px-2.5 py-1 text-[10px] text-muted hover:text-content transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        <p x-show="errorMsg" x-cloak class="mt-1 text-[10px] text-red-500" x-text="errorMsg"></p>
                    </div>
                </div>

                {{-- Expandable reporters list --}}
                <div x-show="expanded" x-collapse class="border-t border-line divide-y divide-line">
                    @foreach ($group->reports as $report)
                        <div class="flex items-start gap-3 px-4 py-2.5">
                            <span class="shrink-0 w-28 text-[11px] text-muted truncate">{{ '@'.($report->reporter?->username ?? 'deleted') }}</span>
                            <span class="shrink-0 rounded-full bg-surface-muted px-2 py-0.5 text-[10px] font-semibold text-content border border-line">
                                {{ $categoryLabels[$report->category] ?? $report->category }}
                            </span>
                            @if ($report->reason)
                                <span class="text-[10px] text-muted truncate flex-1" title="{{ $report->reason }}">{{ Str::limit($report->reason, 80) }}</span>
                            @else
                                <span class="flex-1"></span>
                            @endif
                            <span class="shrink-0 text-[10px] text-muted">{{ $report->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>

            </div>
        @endforeach
    </div>
@endif
