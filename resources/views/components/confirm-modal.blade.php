<div x-data="confirmModal()"
     x-on:open-confirm.window="open($event.detail)"
     x-show="show"
     x-cloak
     x-transition.opacity
     class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 px-4"
     @click.self="handleCancel()"
     @keydown.escape.window="handleCancel()">
    <div class="w-full max-w-sm rounded-xl bg-surface p-6 shadow-xl">
        <h2 class="text-lg font-bold text-content" x-text="title"></h2>
        <p class="mt-2 text-sm text-muted" x-text="message"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button"
                    @click="handleCancel()"
                    class="rounded-lg border border-line px-4 py-2 text-sm font-semibold text-content hover:bg-surface-muted">
                Cancel
            </button>
            <button type="button"
                    x-ref="confirmBtn"
                    @click="handleConfirm()"
                    :class="danger
                        ? 'bg-red-600 hover:bg-red-700'
                        : 'bg-gradient-to-tr from-accent-from to-accent-to hover:opacity-90'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white">
                <span x-text="confirmLabel"></span>
            </button>
        </div>
    </div>
</div>
