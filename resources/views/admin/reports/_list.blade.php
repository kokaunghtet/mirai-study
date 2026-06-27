{{-- @list-partial --}}

{{-- Filters --}}
<div class="mb-5 flex flex-wrap items-center gap-2">
    {{-- Status chips --}}
    @foreach (['pending' => 'Pending', 'reviewed' => 'Reviewed', 'resolved' => 'Resolved', 'rejected' => 'Rejected'] as $val => $label)
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
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden md:table-cell">Category / Detail</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden sm:table-cell">Reported</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden lg:table-cell">Reviewed by</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-muted">Actions</th>
                </tr>
            </thead>
            @php
                $categoryLabels = [
                    'spam'           => 'Spam',
                    'harassment'     => 'Harassment',
                    'misinformation' => 'Misinformation',
                    'inappropriate'  => 'Inappropriate',
                    'other'          => 'Other',
                ];
            @endphp
            <tbody class="divide-y divide-line">
                @foreach ($reports as $report)
                    <tr class="hover:bg-surface-muted transition-colors" id="report-row-{{ $report->id }}">

                        {{-- Reporter --}}
                        <td class="px-4 py-3 text-xs text-muted">
                            {{ '@' . ($report->reporter?->username ?? 'deleted') }}
                        </td>

                        {{-- Target type --}}
                        <td class="px-4 py-3">
                            <span class="rounded-full border border-line bg-surface-muted px-2 py-0.5 text-[10px] font-semibold text-muted capitalize">
                                {{ $report->target_type }}
                            </span>
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

                        {{-- Reviewed by --}}
                        <td class="px-4 py-3 text-xs text-muted hidden lg:table-cell">
                            {{ $report->reviewer?->username ?? '—' }}
                        </td>

                        {{-- Status badge --}}
                        <td class="px-4 py-3">
                            <span id="report-badge-{{ $report->id }}"
                                  @class([
                                      'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                      'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' => $report->status === 'pending',
                                      'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'   => $report->status === 'reviewed',
                                      'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $report->status === 'resolved',
                                      'bg-surface-muted text-muted border border-line' => $report->status === 'rejected',
                                  ])>
                                {{ ucfirst($report->status) }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right">
                            @if ($report->status === 'pending')
                                <div id="report-actions-{{ $report->id }}" class="flex items-center justify-end gap-1.5">
                                    <button
                                        onclick="resolveReport({{ $report->id }}, 'resolved')"
                                        class="rounded-lg bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 transition-colors hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50">
                                        Resolve
                                    </button>
                                    <button
                                        onclick="resolveReport({{ $report->id }}, 'rejected')"
                                        class="rounded-lg border border-line bg-surface-muted px-2.5 py-1 text-xs font-semibold text-muted transition-colors hover:bg-surface">
                                        Reject
                                    </button>
                                </div>
                            @else
                                <span class="text-xs text-muted">—</span>
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

