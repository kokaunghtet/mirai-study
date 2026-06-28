@auth
<div x-data="reportModal()"
     @open-report.window="show($event.detail.type, $event.detail.id)"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" @click="close()"></div>

    {{-- Panel --}}
    <div class="relative w-full max-w-sm bg-surface rounded-2xl shadow-xl border border-line overflow-hidden">

        {{-- Success state --}}
        <div x-show="state === 'success'" class="p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="check" class="w-6 h-6 text-green-600"></i>
            </div>
            <p class="font-semibold text-content">Report submitted</p>
            <p class="text-sm text-muted mt-1">Thanks for helping keep the community safe.</p>
        </div>

        {{-- Error state --}}
        <div x-show="state === 'error'" class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="triangle-alert" class="w-5 h-5 text-red-500 shrink-0"></i>
                <h2 class="font-bold text-content">Something went wrong</h2>
            </div>
            <p class="text-sm text-muted mb-5">Failed to submit report. Please try again.</p>
            <div class="flex gap-2">
                <button @click="state = 'idle'" type="button"
                        class="flex-1 py-2 rounded-lg bg-gradient-to-tr from-accent-from to-accent-to text-white text-sm font-medium hover:opacity-90 transition">
                    Try again
                </button>
                <button @click="close()" type="button"
                        class="flex-1 py-2 rounded-lg border border-line text-sm font-medium text-content hover:bg-surface-muted transition">
                    Cancel
                </button>
            </div>
        </div>

        {{-- Duplicate state --}}
        <div x-show="state === 'duplicate'" class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="flag" class="w-5 h-5 text-accent shrink-0"></i>
                <h2 class="font-bold text-content">Already reported</h2>
            </div>
            <p class="text-sm text-muted mb-5">You've already reported this content. Our team will review it.</p>
            <button @click="close()" type="button"
                    class="w-full py-2 rounded-lg border border-line text-sm font-medium text-content hover:bg-surface-muted transition">
                Close
            </button>
        </div>

        {{-- Admin target state --}}
        <div x-show="state === 'admin'" class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="shield-check" class="w-5 h-5 text-accent shrink-0"></i>
                <h2 class="font-bold text-content">Cannot report admin</h2>
            </div>
            <p class="text-sm text-muted mb-5">Admin accounts cannot be reported. If you have a concern, please contact support directly.</p>
            <button @click="close()" type="button"
                    class="w-full py-2 rounded-lg border border-line text-sm font-medium text-content hover:bg-surface-muted transition">
                Close
            </button>
        </div>

        {{-- Main form --}}
        <div x-show="state === 'idle' || state === 'submitting'">

            <div class="flex items-center justify-between px-5 py-4 border-b border-line">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="flag" class="w-4 h-4 text-accent"></i>
                    <h2 class="font-bold text-content text-sm">Report</h2>
                </div>
                <button @click="close()" type="button"
                        class="grid h-7 w-7 place-items-center rounded-lg text-muted hover:bg-surface-muted transition">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-5">
                <p class="text-xs text-muted mb-3">Why are you reporting this?</p>

                <div class="space-y-0.5">
                    @foreach (['spam' => 'Spam', 'harassment' => 'Harassment or bullying', 'misinformation' => 'Misinformation', 'inappropriate' => 'Inappropriate content', 'other' => 'Other'] as $value => $label)
                        <label class="flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-surface-muted transition"
                               :class="category === '{{ $value }}' ? 'bg-accent/10' : ''">
                            <input type="radio" name="report_category" value="{{ $value }}"
                                   x-model="category"
                                   class="w-4 h-4 shrink-0 accent-[rgb(var(--accent))]">
                            <span class="text-sm text-content">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Optional detail --}}
                <div x-show="category"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mt-3">
                    <textarea x-model="detail"
                              placeholder="Add more detail (optional)"
                              rows="2"
                              maxlength="500"
                              class="w-full bg-surface-muted text-content border border-line rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-accent/40"></textarea>
                </div>
            </div>

            <div class="flex gap-2 px-5 pb-5">
                <button @click="close()" type="button"
                        class="flex-1 py-2 rounded-lg border border-line text-sm font-medium text-content hover:bg-surface-muted transition">
                    Cancel
                </button>
                <button @click="submit()" type="button"
                        :disabled="!category || state === 'submitting'"
                        :class="(!category || state === 'submitting') ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'"
                        class="flex-1 py-2 rounded-lg bg-gradient-to-tr from-accent-from to-accent-to text-white text-sm font-medium transition">
                    <span x-show="state !== 'submitting'">Submit report</span>
                    <span x-show="state === 'submitting'" class="flex items-center justify-center gap-1.5">
                        <i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i>
                        Submitting…
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>
@endauth
