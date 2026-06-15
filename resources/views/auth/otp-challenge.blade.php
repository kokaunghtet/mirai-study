@php($isLogin = $purpose === 'login_verification')
<x-portal-layout :portal="false">
    <div class="mx-auto w-full max-w-[360px] text-center">

        {{-- Icon --}}
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-mirai-lime to-mirai-dark text-white shadow-lg">
            @if ($isLogin)
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            @else
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            @endif
        </div>

        <h1 class="mb-1 bg-gradient-to-r from-mirai-lime to-mirai-dark bg-clip-text text-2xl font-semibold tracking-tight text-transparent">
            {{ $isLogin ? 'Two-step verification' : 'Verify your email' }}
        </h1>
        <p class="mb-6 text-xs text-muted">
            Enter the 6-digit code we sent to
            <span class="font-semibold text-content/80">{{ $maskedEmail }}</span>
            {{ $isLogin ? 'to finish signing in.' : 'to activate your account.' }}
        </p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <div x-data="otpCountdown({ seconds: {{ (int) $secondsRemaining }} })">
            {{-- Code entry --}}
            <form method="POST" action="{{ route('otp.verify') }}" data-loading>
                @csrf

                <x-otp-input />

                <x-input-error :messages="$errors->get('code')" class="mt-3 justify-center text-center" />

                <button type="submit" data-loading-text="Verifying…" :class="{ 'opacity-50': expired }"
                        class="mt-5 w-full rounded-xl bg-accent py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-accent-strong focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                    {{ $isLogin ? 'Verify & sign in' : 'Verify email' }}
                </button>
            </form>

            {{-- Expiry countdown --}}
            <p class="mt-4 text-[11px] font-medium text-muted" x-cloak>
                <span x-show="!expired">
                    Code expires in <span class="font-semibold tabular-nums text-content" x-text="display">10:00</span>
                </span>
                <span x-show="expired" class="font-semibold text-accent">
                    Code expired — request a new one below.
                </span>
            </p>

            {{-- Resend + back --}}
            <div class="mt-5 flex flex-col items-center gap-2 text-[11px] font-medium text-muted">
                <form method="POST" action="{{ route('otp.resend') }}" data-loading>
                    @csrf
                    <span>Didn't get it?</span>
                    <button type="submit" data-loading-text="Sending…" :class="{ 'animate-pulse text-accent-strong': expired }"
                            class="font-semibold text-accent hover:underline">Resend code</button>
                </form>
                <a href="{{ route('login') }}" class="text-muted hover:text-content hover:text-mirai-lime">Back to login</a>
            </div>
        </div>
    </div>
</x-portal-layout>
