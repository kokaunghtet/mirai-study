@props(['activityItems'])

<section class="rounded-2xl border border-line bg-surface shadow-sm overflow-hidden mb-6" data-activity-feed>
    <div class="flex items-center justify-between px-5 py-4 border-b border-line">
        <h2 class="text-sm font-bold text-content">Recent Activity</h2>
        <i data-lucide="activity" class="h-4 w-4 text-muted"></i>
    </div>

    @if ($activityItems->isEmpty())
        <div class="px-5 py-8 text-center">
            <i data-lucide="inbox" class="mx-auto mb-2 h-6 w-6 text-muted"></i>
            <p class="text-sm text-muted">No recent activity.</p>
            <p class="mt-1 text-xs text-muted">Platform events will appear here as they happen.</p>
        </div>
    @else
        <ul class="divide-y divide-line">
            @foreach ($activityItems as $item)
                @include('admin.partials._activity-item', ['item' => $item])
            @endforeach
        </ul>
    @endif
</section>

<script>
    window.renderIcons?.();
</script>
