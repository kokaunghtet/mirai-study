@for ($i = 0; $i < $count; $i++)
    <div class="notification-skeleton flex gap-3.5 px-4 py-4 rounded-xl border border-line bg-surface animate-pulse">
        <div class="shrink-0">
            <div class="w-10 h-10 rounded-full bg-surface-muted"></div>
        </div>
        <div class="flex-1 min-w-0 space-y-2">
            <div class="flex items-center justify-between gap-3">
                <div class="h-3 w-32 rounded-md bg-surface-muted"></div>
                <div class="h-2.5 w-10 rounded-md bg-surface-muted"></div>
            </div>
            <div class="h-2.5 w-4/5 rounded-md bg-surface-muted"></div>
            <div class="h-2.5 w-2/5 rounded-md bg-surface-muted"></div>
        </div>
    </div>
@endfor
