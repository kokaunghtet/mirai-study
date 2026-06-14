<x-portal-layout :portal="false">
    <div class="portal-step mx-auto w-full max-w-[360px] text-center">

        {{-- Icon --}}
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-mirai-lime to-mirai-dark text-white shadow-lg">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
        </div>

        <h1 class="mb-1 bg-gradient-to-r from-mirai-lime to-mirai-dark bg-clip-text text-2xl font-semibold tracking-tight text-transparent">
            Enter your code
        </h1>
        <p class="mb-6 text-xs text-muted">
            Enter the 6-digit code we sent to
            <span class="font-semibold text-content/80">{{ $maskedEmail }}</span>
            to reset your password.
        </p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{-- Code entry --}}
        <form method="POST" action="{{ route('password.code.verify') }}">
            @csrf

            <x-otp-input />

            <x-input-error :messages="$errors->get('code')" class="mt-3 justify-center text-center" />

            <button type="submit"
                    class="mt-5 w-full rounded-xl bg-accent py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-accent-strong focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                Verify code
            </button>
        </form>

        {{-- Resend + back --}}
        <div class="mt-5 flex flex-col items-center gap-2 text-[11px] font-medium text-muted">
            <form method="POST" action="{{ route('password.code.resend') }}">
                @csrf
                <span>Didn't get it?</span>
                <button type="submit" class="font-semibold text-accent hover:underline">Resend code</button>
            </form>
            <a href="{{ route('login') }}" class="text-muted hover:text-content hover:underline">Back to login</a>
        </div>
    </div>
</x-portal-layout>
