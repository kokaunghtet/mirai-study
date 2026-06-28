{{-- @list-partial --}}

{{-- Status filter chips --}}
<div class="mb-5 flex flex-wrap items-center gap-2">
    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
        @php $on = request('status', 'pending') === $val; @endphp
        <a href="{{ request()->fullUrlWithQuery(['status' => $val, 'page' => null]) }}"
           class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors
                  {{ $on ? 'bg-gradient-to-tr from-accent-from to-accent-to text-white' : 'border border-line bg-surface text-muted hover:text-content' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if ($appeals->isEmpty())
    <div class="rounded-2xl border border-line bg-surface px-6 py-12 text-center">
        <i data-lucide="inbox" class="mx-auto mb-3 h-8 w-8 text-muted"></i>
        <p class="text-sm font-semibold text-content">No appeals found.</p>
        <p class="mt-1 text-xs text-muted">Nothing matches the current filter.</p>
    </div>
@else
    <div class="rounded-2xl border border-line bg-surface shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line bg-surface-muted">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Appellant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Ban type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden md:table-cell">Reason / Appeal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden sm:table-cell">Banned by</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted hidden lg:table-cell">Submitted</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-muted">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-muted">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($appeals as $appeal)
                    <tr class="hover:bg-surface-muted transition-colors" id="appeal-row-{{ $appeal->id }}">

                        {{-- Appellant --}}
                        <td class="px-4 py-3 text-xs text-muted">
                            <a href="{{ route('profile.show', $appeal->user?->username ?? '#') }}"
                               class="font-semibold text-content hover:text-accent transition-colors">
                                {{ '@' . ($appeal->user?->username ?? 'deleted') }}
                            </a>
                        </td>

                        {{-- Ban type --}}
                        <td class="px-4 py-3">
                            @php $banType = $appeal->ban?->type ?? 'unknown'; @endphp
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' => $banType === 'permanent',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' => $banType === 'temporary',
                                'bg-surface-muted text-muted border border-line' => $banType === 'unknown',
                            ])>
                                {{ ucfirst($banType) }}
                            </span>
                        </td>

                        {{-- Reason + appeal message --}}
                        <td class="px-4 py-3 text-xs text-muted hidden md:table-cell max-w-xs">
                            @if ($appeal->ban?->reason)
                                <div class="mb-1 font-semibold text-content truncate max-w-[220px]" title="{{ $appeal->ban->reason }}">
                                    {{ Str::limit($appeal->ban->reason, 50) }}
                                </div>
                            @endif
                            <div class="italic truncate max-w-[220px]" title="{{ $appeal->message }}">
                                "{{ Str::limit($appeal->message, 60) }}"
                            </div>
                        </td>

                        {{-- Banned by --}}
                        <td class="px-4 py-3 text-xs text-muted hidden sm:table-cell whitespace-nowrap">
                            {{ $appeal->ban?->bannedBy?->username ?? '—' }}
                        </td>

                        {{-- Submitted --}}
                        <td class="px-4 py-3 text-xs text-muted hidden lg:table-cell whitespace-nowrap">
                            {{ $appeal->created_at->diffForHumans() }}
                        </td>

                        {{-- Status badge --}}
                        <td class="px-4 py-3">
                            <span id="appeal-badge-{{ $appeal->id }}"
                                  @class([
                                      'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                      'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' => $appeal->status === 'pending',
                                      'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $appeal->status === 'approved',
                                      'bg-surface-muted text-muted border border-line' => $appeal->status === 'rejected',
                                  ])>
                                {{ ucfirst($appeal->status) }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right">
                            @if ($appeal->status === 'pending')
                                <div id="appeal-actions-{{ $appeal->id }}" class="flex items-center justify-end gap-1.5">
                                    <button
                                        onclick="reviewAppeal({{ $appeal->id }}, 'approve')"
                                        class="rounded-lg bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 transition-colors hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50">
                                        Approve
                                    </button>
                                    <button
                                        onclick="reviewAppeal({{ $appeal->id }}, 'reject')"
                                        class="rounded-lg border border-line bg-surface-muted px-2.5 py-1 text-xs font-semibold text-muted transition-colors hover:bg-surface">
                                        Reject
                                    </button>
                                </div>
                            @else
                                <span id="appeal-actions-{{ $appeal->id }}" class="text-xs text-muted">—</span>
                            @endif
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($appeals->hasPages())
        <div class="mt-4">
            {{ $appeals->links() }}
        </div>
    @endif
@endif
