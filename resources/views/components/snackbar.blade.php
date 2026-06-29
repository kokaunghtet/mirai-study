<div x-data="snackbar()"
     x-on:show-snackbar.window="show($event.detail)"
     x-show="visible"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-x-4"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-4"
     class="fixed top-6 right-6 z-[70] flex items-center gap-3 rounded-xl px-4 py-3 shadow-lg ring-1 max-w-sm w-[calc(100%-3rem)]"
     :class="{
         'bg-surface ring-line text-content': type === 'info',
         'bg-accent/70 dark:bg-accent/70 ring-accent/90 text-white': type === 'success',
         'bg-red-50 dark:bg-red-900 ring-red-200 dark:ring-red-800 text-red-800 dark:text-red-200': type === 'error',
         'bg-amber-50 dark:bg-amber-900 ring-amber-200 dark:ring-amber-800 text-amber-800 dark:text-amber-200': type === 'warning',
     }">
    {{-- Icon --}}
    <div class="shrink-0" x-show="type === 'success'">
        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div class="shrink-0" x-show="type === 'error'">
        <svg class="h-5 w-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    </div>
    <div class="shrink-0" x-show="type === 'warning'">
        <svg class="h-5 w-5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </div>
    <div class="shrink-0" x-show="type === 'info'">
        <svg class="h-5 w-5 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    </div>

    {{-- Message --}}
    <p class="text-sm font-medium flex-1" x-text="message"></p>

    {{-- Dismiss --}}
    <button type="button"
            @click="hide()"
            class="shrink-0 -mr-1 rounded-md p-1 transition-colors"
            :class="type === 'success' ? 'hover:bg-white/20' : 'hover:bg-black/5 dark:hover:bg-white/10'">
        <svg class="h-4 w-4" :class="type === 'success' ? 'opacity-80' : 'opacity-60'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
</div>

<script>
window._snackbarQueue = window._snackbarQueue || [];

function snackbar() {
    return {
        visible: false,
        message: '',
        type: 'info',
        timeout: null,

        init() {
            window._snackbarComponent = this;
            // Flush any queued events that fired before Alpine was ready
            window._snackbarQueue.forEach(detail => this.show(detail));
            window._snackbarQueue = [];
        },

        show(detail) {
            clearTimeout(this.timeout);
            this.message = detail.message || detail;
            this.type = detail.type || 'info';
            this.visible = true;
            const duration = detail.duration || 2000;
            this.timeout = setTimeout(() => this.hide(), duration);
        },

        hide() {
            clearTimeout(this.timeout);
            this.visible = false;
        },
    };
}
</script>
