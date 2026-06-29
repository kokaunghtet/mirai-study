<x-app-layout>
    <x-slot name="title">Account Restricted — MiraiStudy</x-slot>

    <div class="max-w-[520px] mx-auto py-8 px-4">

        {{-- Ban notice card --}}
        <div class="rounded-2xl border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-950/20 p-6 mb-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <i data-lucide="ban" class="w-5 h-5 text-red-600 dark:text-red-400"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h1 class="text-base font-bold text-red-700 dark:text-red-400 mb-1">
                        Your account has been
                        @if ($ban->type === 'temporary') temporarily suspended @else permanently banned @endif
                    </h1>

                    @if ($ban->reason)
                        <p class="text-sm text-red-600/80 dark:text-red-400/70 leading-relaxed mb-2">
                            <span class="font-semibold">Reason:</span> {{ $ban->reason }}
                        </p>
                    @endif

                    @if ($ban->type === 'temporary' && $ban->expires_at)
                        <div class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600 dark:text-red-400"
                             x-data="{ remaining: '' }"
                             x-init="
                                const exp = new Date('{{ $ban->expires_at->toIso8601String() }}');
                                function tick() {
                                    const diff = exp - Date.now();
                                    if (diff <= 0) { remaining = 'Expired'; return; }
                                    const d = Math.floor(diff / 86400000);
                                    const h = Math.floor((diff % 86400000) / 3600000);
                                    const m = Math.floor((diff % 3600000) / 60000);
                                    remaining = d > 0 ? d + 'd ' + h + 'h remaining' : h > 0 ? h + 'h ' + m + 'm remaining' : m + 'm remaining';
                                    setTimeout(tick, 30000);
                                }
                                tick();
                             ">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            <span x-text="remaining"></span>
                            <span class="text-red-400 dark:text-red-600 font-normal ml-1">
                                — expires {{ $ban->expires_at->format('M j, Y \a\t g:i A') }}
                            </span>
                        </div>
                    @endif

                    @if ($ban->bannedBy)
                        <p class="mt-2 text-xs text-muted">
                            Action by: <span class="font-semibold">{{ $ban->bannedBy->display_name }}</span>
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Appeal form --}}
        @if ($openAppeal)
            <div class="rounded-2xl border border-line bg-surface p-6 mb-6">
                <div class="flex items-center gap-3 mb-3">
                    <i data-lucide="clock-4" class="w-5 h-5 text-amber-500 shrink-0"></i>
                    <h2 class="text-sm font-bold text-content">Appeal under review</h2>
                </div>
                <p class="text-sm text-muted leading-relaxed">
                    Your appeal has been submitted and is waiting for an admin to review it.
                    You'll be able to submit a new one if this is rejected.
                </p>
                <div class="mt-4 rounded-xl border border-line bg-surface-muted p-3 text-xs text-muted italic">
                    "{{ Str::limit($openAppeal->message, 200) }}"
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-line bg-surface p-6">
                <h2 class="text-base font-bold text-content mb-1">Submit an appeal</h2>
                <p class="text-sm text-muted mb-5 leading-relaxed">
                    If you believe this action was made in error, explain your situation below.
                    An admin will review your appeal.
                </p>

                @if (session('success'))
                    @push('scripts')
                    <script>(function(d) { window._snackbarComponent ? window._snackbarComponent.show(d) : window._snackbarQueue.push(d); })({ message: @json(session('success')), type: 'success' });</script>
                    @endpush
                @endif

                @if (session('error'))
                    @push('scripts')
                    <script>(function(d) { window._snackbarComponent ? window._snackbarComponent.show(d) : window._snackbarQueue.push(d); })({ message: @json(session('error')), type: 'error' });</script>
                    @endpush
                @endif

                <form method="POST" action="{{ route('appeal.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="message" class="block text-xs font-semibold text-content/80 mb-1.5">
                            Your appeal <span class="text-muted font-normal">(10–1000 characters)</span>
                        </label>
                        <textarea
                            id="message"
                            name="message"
                            rows="5"
                            maxlength="1000"
                            placeholder="Explain why you believe this restriction should be lifted…"
                            class="w-full rounded-xl border border-line bg-canvas px-4 py-3 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 resize-none"
                        >{{ old('message') }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-1.5" />
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-gradient-to-tr from-accent-from to-accent-to py-2.5 text-sm font-semibold text-white transition hover:opacity-90 active:scale-95">
                        Submit appeal
                    </button>
                </form>
            </div>
        @endif

        {{-- Log out option --}}
        <div class="mt-6 text-center">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-xs text-muted hover:text-content transition-colors underline underline-offset-2">
                    Log out
                </button>
            </form>
        </div>

    </div>
</x-app-layout>
