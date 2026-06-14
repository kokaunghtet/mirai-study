<x-portal-layout :portal="false">
    <div class="portal-step mx-auto w-full max-w-[360px] text-center"
         x-data="{
            showPw: false,
            password: '',
            confirmation: '',
            get strength() {
                const p = this.password;
                if (!p) return 0;
                let s = 0;
                if (p.length >= 8) s++;
                if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
                if (/\d/.test(p) || /[^A-Za-z0-9]/.test(p)) s++;
                return Math.max(1, s);
            },
            get strengthPct() { return [0, 34, 67, 100][this.strength] || 0; },
            get strengthLabel() { return ['', 'Weak', 'Fair', 'Strong'][this.strength] || ''; },
         }">

        {{-- Icon --}}
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-mirai-lime to-mirai-dark text-white shadow-lg">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><circle cx="12" cy="16" r="1"/></svg>
        </div>

        <h1 class="mb-1 bg-gradient-to-r from-mirai-lime to-mirai-dark bg-clip-text text-2xl font-semibold tracking-tight text-transparent">
            Choose a new password
        </h1>
        <p class="mb-6 text-xs text-muted">
            Your code checked out. Set a new password to finish.
        </p>

        <form method="POST" action="{{ route('password.store') }}" class="text-left">
            @csrf

            {{-- New password --}}
            <label class="mb-1.5 block pl-0.5 text-[11px] font-semibold text-content/80">New password</label>
            <div class="relative">
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input :type="showPw ? 'text' : 'password'" name="password" x-model="password" required autocomplete="new-password" placeholder="••••••••"
                       class="w-full rounded-xl border border-line bg-surface py-2.5 pl-11 pr-10 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                <button type="button" @click="showPw = !showPw" tabindex="-1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-content">
                    <svg x-show="!showPw" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="showPw" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                </button>
            </div>
            {{-- strength meter --}}
            <div class="mt-1.5 flex items-center gap-2 pl-0.5" x-show="password.length > 0" x-transition.opacity x-cloak>
                <div class="h-1 flex-1 overflow-hidden rounded-full bg-surface-muted">
                    <div class="h-full rounded-full transition-all duration-300"
                         :class="{ 'bg-red-500': strength === 1, 'bg-amber-500': strength === 2, 'bg-accent': strength === 3 }"
                         :style="`width: ${strengthPct}%`"></div>
                </div>
                <span class="text-[9px] font-bold uppercase tracking-wide text-muted" x-text="strengthLabel"></span>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 px-0.5" />

            {{-- Confirm password --}}
            <label class="mb-1.5 mt-4 block pl-0.5 text-[11px] font-semibold text-content/80">Confirm password</label>
            <div class="relative">
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </span>
                <input :type="showPw ? 'text' : 'password'" name="password_confirmation" x-model="confirmation" required autocomplete="new-password" placeholder="••••••••"
                       class="w-full rounded-xl border border-line bg-surface py-2.5 pl-11 pr-10 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                <span x-show="confirmation.length > 0 && confirmation === password" x-transition.opacity x-cloak class="absolute right-3.5 top-1/2 -translate-y-1/2 text-accent">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 px-0.5" />

            <button type="submit"
                    class="mt-5 w-full rounded-xl bg-accent py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-accent-strong focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                Reset password
            </button>
        </form>

        <a href="{{ route('login') }}" class="mt-5 inline-block text-[11px] font-medium text-muted hover:text-content hover:underline">Back to login</a>
    </div>
</x-portal-layout>
