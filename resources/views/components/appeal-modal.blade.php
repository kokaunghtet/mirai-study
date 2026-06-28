@auth
@php
    $__isBanned = auth()->user()->isBannedNow();
    if ($__isBanned) {
        $__ban = auth()->user()->activeBan();
        $__hasPending = (bool) ($__ban?->hasOpenAppeal());
        $__openAppeal = $__hasPending ? $__ban->appeals()->where('status', 'pending')->first() : null;
    }
@endphp
@if ($__isBanned && isset($__ban))
<div x-data="appealModal({
         hasPendingAppeal: @js($__hasPending),
         autoOpen: @js((bool) session('show_appeal_modal'))
     })"
     @open-appeal-modal.window="show()"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center sm:p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60" @click="close()"></div>

    {{-- Panel --}}
    <div class="relative w-full sm:max-w-md bg-surface rounded-t-2xl sm:rounded-2xl shadow-xl border border-line overflow-hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="translate-y-0 sm:scale-100">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-line">
            <div class="flex items-center gap-2.5">
                <i data-lucide="ban" class="w-4 h-4 text-red-500"></i>
                <h2 class="font-bold text-content text-sm">Account Restricted</h2>
            </div>
            <button @click="close()" type="button"
                    class="grid h-7 w-7 place-items-center rounded-lg text-muted hover:bg-surface-muted transition">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Success state --}}
        <div x-show="state === 'success'" class="p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="check" class="w-6 h-6 text-green-600 dark:text-green-400"></i>
            </div>
            <p class="font-semibold text-content">Appeal submitted</p>
            <p class="text-sm text-muted mt-1">An admin will review your appeal soon.</p>
            <button @click="close()" type="button"
                    class="mt-5 w-full py-2 rounded-lg border border-line text-sm font-medium text-content hover:bg-surface-muted transition">
                Close
            </button>
        </div>

        {{-- Error state --}}
        <div x-show="state === 'error'" class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="triangle-alert" class="w-5 h-5 text-red-500 shrink-0"></i>
                <h2 class="font-bold text-content">Something went wrong</h2>
            </div>
            <p class="text-sm text-muted mb-5">Failed to submit your appeal. Please try again.</p>
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

        {{-- Main content (idle / submitting / pending) --}}
        <div x-show="state !== 'success' && state !== 'error'">
            <div class="p-5">

                {{-- Ban notice --}}
                <div class="rounded-xl border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-950/20 p-4 mb-4">
                    <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-1">
                        Your account has been
                        @if ($__ban->type === 'temporary') temporarily suspended @else permanently banned @endif
                    </p>
                    @if ($__ban->reason)
                        <p class="text-xs text-red-600/80 dark:text-red-400/70 leading-relaxed">
                            <span class="font-semibold">Reason:</span> {{ $__ban->reason }}
                        </p>
                    @endif
                    @if ($__ban->type === 'temporary' && $__ban->expires_at)
                        <p class="mt-1.5 text-xs text-red-500 dark:text-red-400 font-semibold">
                            Expires {{ $__ban->expires_at->format('M j, Y \a\t g:i A') }}
                        </p>
                    @endif
                </div>

                {{-- Pending appeal --}}
                <div x-show="state === 'pending'">
                    <div class="flex items-center gap-2.5 mb-2">
                        <i data-lucide="clock-4" class="w-4 h-4 text-amber-500 shrink-0"></i>
                        <h3 class="text-sm font-bold text-content">Appeal under review</h3>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        Your appeal is waiting for an admin to review it.
                        You'll be able to submit a new one if rejected.
                    </p>
                    @if ($__openAppeal)
                        <div class="mt-3 rounded-lg border border-line bg-surface-muted p-3 text-xs text-muted italic">
                            "{{ Str::limit($__openAppeal->message, 160) }}"
                        </div>
                    @endif
                    <button @click="close()" type="button"
                            class="mt-4 w-full py-2 rounded-lg border border-line text-sm font-medium text-content hover:bg-surface-muted transition">
                        Close
                    </button>
                </div>

                {{-- Appeal form --}}
                <div x-show="state === 'idle' || state === 'submitting'">
                    <p class="text-sm text-muted mb-3 leading-relaxed">
                        Believe this was a mistake? Explain your situation and an admin will review it.
                    </p>
                    <div class="mb-4">
                        <textarea x-model="message"
                                  rows="4"
                                  maxlength="1000"
                                  placeholder="Explain why you believe this restriction should be lifted…"
                                  :disabled="state === 'submitting'"
                                  class="w-full rounded-xl border border-line bg-canvas px-4 py-3 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 resize-none disabled:opacity-60"></textarea>
                        <div class="flex justify-between mt-1">
                            <p x-show="message.length > 0 && message.length < 10"
                               class="text-xs text-red-500">Minimum 10 characters</p>
                            <p class="text-xs text-muted ml-auto" x-text="message.length + '/1000'"></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="close()" type="button"
                                class="flex-1 py-2 rounded-lg border border-line text-sm font-medium text-content hover:bg-surface-muted transition">
                            Cancel
                        </button>
                        <button @click="submit()" type="button"
                                :disabled="message.length < 10 || state === 'submitting'"
                                :class="(message.length < 10 || state === 'submitting') ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'"
                                class="flex-1 py-2 rounded-lg bg-gradient-to-tr from-accent-from to-accent-to text-white text-sm font-medium transition">
                            <span x-show="state !== 'submitting'">Submit appeal</span>
                            <span x-show="state === 'submitting'" class="flex items-center justify-center gap-1.5">
                                <i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i>
                                Submitting…
                            </span>
                        </button>
                    </div>
                </div>

            </div>

            {{-- Log out link --}}
            <div class="border-t border-line px-5 py-3 flex justify-center">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-muted hover:text-content transition-colors">
                        Log out instead
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endif
@endauth
