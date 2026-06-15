<x-portal-layout :portal="false">
    <div class="portal-step mx-auto w-full max-w-[360px] text-center">

        {{-- Icon --}}
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-mirai-lime to-mirai-dark text-white shadow-lg">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
        </div>

        <h1 class="mb-1 bg-gradient-to-r from-mirai-lime to-mirai-dark bg-clip-text text-2xl font-semibold tracking-tight text-transparent">
            Forgot your password?
        </h1>
        <p class="mb-6 text-xs text-muted">
            Enter your account email and we'll send you a 6-digit code to reset it.
        </p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" data-loading class="text-left">
            @csrf

            <label class="mb-1.5 block pl-0.5 text-[11px] font-semibold text-content/80">Email address</label>
            <div class="relative">
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       placeholder="name@example.com"
                       class="w-full rounded-xl border border-line bg-surface py-2.5 pl-11 pr-4 text-sm font-medium text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 px-0.5" />

            <button type="submit" data-loading-text="Sending…"
                    class="mt-5 w-full rounded-xl bg-accent py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-accent-strong focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                Send reset code
            </button>
        </form>

        <a href="{{ route('login') }}" class="mt-5 inline-block text-[11px] font-medium text-muted hover:text-content hover:underline">Back to login</a>
    </div>
</x-portal-layout>
