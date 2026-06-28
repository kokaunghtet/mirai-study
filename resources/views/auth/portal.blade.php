{{--
    Shared auth portal body. Rendered inside <x-portal-layout>.
    Holds the Login/Register pill toggle and both forms; switching cross-slides
    the two forms (login ⇄ register) while the card tilts + shines. Each form
    posts to its real route. Alpine ('portal' in app.js) drives the toggle
    choreography, username suggestions, and live field validation.
--}}
@php($banAppeal = session('ban_appeal'))

@if($banAppeal)

{{-- ─────────────── BAN APPEAL ─────────────── --}}
<div class="mx-auto w-full max-w-[360px]">

    {{-- Ban notice --}}
    <div class="mb-5 rounded-2xl border border-red-500/20 bg-red-500/10 p-5">
        <div class="flex items-start gap-3">
            <div class="shrink-0 flex h-9 w-9 items-center justify-center rounded-full bg-red-500/20">
                <i data-lucide="ban" class="h-4 w-4 text-red-400"></i>
            </div>
            <div>
                <p class="mb-1 text-sm font-bold text-red-400">
                    {{ $banAppeal['ban_type'] === 'temporary' ? 'Account temporarily suspended' : 'Account permanently banned' }}
                </p>
                @if (! empty($banAppeal['ban_reason']))
                    <p class="text-xs leading-relaxed text-red-400/70">{{ $banAppeal['ban_reason'] }}</p>
                @endif
            </div>
        </div>
    </div>

    @if ($banAppeal['has_open_appeal'])
        {{-- Pending appeal --}}
        <div class="rounded-2xl border border-white/[0.06] bg-surface/60 p-5">
            <div class="mb-2 flex items-center gap-3">
                <i data-lucide="clock-4" class="h-5 w-5 shrink-0 text-amber-400"></i>
                <p class="text-sm font-bold text-content">Appeal under review</p>
            </div>
            <p class="text-xs leading-relaxed text-muted">
                Your appeal has been submitted and is waiting for an admin to review it.
            </p>
        </div>
    @else
        {{-- Appeal form --}}
        <div class="rounded-2xl border border-white/[0.06] bg-surface/60 p-5">
            <h2 class="mb-1 text-sm font-bold text-content">Submit an appeal</h2>
            <p class="mb-4 text-xs leading-relaxed text-muted">
                If you believe this action was made in error, explain your situation below.
            </p>

            @error('message')
                <div class="mb-3 rounded-xl border border-red-500/20 bg-red-500/10 px-3 py-2 text-xs text-red-400">
                    {{ $message }}
                </div>
            @enderror

            <form method="POST" action="{{ route('appeal.store.guest') }}" data-loading>
                @csrf
                <textarea
                    name="message"
                    rows="4"
                    maxlength="1000"
                    placeholder="Explain why this restriction should be lifted…"
                    class="mb-3 w-full resize-none rounded-xl border border-white/[0.06] bg-canvas/40 px-4 py-3 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                >{{ old('message') }}</textarea>
                <button type="submit" data-loading-text="Submitting…"
                        class="w-full rounded-xl bg-gradient-to-tr from-accent-from to-accent-to py-2.5 text-xs font-semibold uppercase tracking-widest text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                    Submit appeal
                </button>
            </form>
        </div>
    @endif

    <p class="mt-5 text-center text-[11px] text-muted">
        <a href="{{ route('login.clear-ban') }}" class="font-semibold text-accent hover:underline">
            Use a different account
        </a>
    </p>

</div>

@else

@php($activeMode = in_array(old('form_intent'), ['login', 'register']) ? old('form_intent') : ($mode ?? 'login'))

{{-- Pill toggle: a single highlight slides between the tabs (CSS-driven). --}}
<div class="portal-switcher mx-auto mb-8 flex w-full max-w-[360px] items-center rounded-full border border-line bg-surface-muted p-1"
     :class="{ 'is-register': mode === 'register' }">
    <span class="portal-switcher-indicator rounded-full bg-surface shadow-sm" aria-hidden="true"></span>
    <button type="button" @click="go('login')"
            class="relative z-10 flex-1 rounded-full py-2 text-xs font-semibold transition"
            :class="mode === 'login' ? 'text-content' : 'text-muted hover:text-content'">
        Log in
    </button>
    <button type="button" @click="go('register')"
            class="relative z-10 flex-1 rounded-full py-2 text-xs font-semibold transition"
            :class="mode === 'register' ? 'text-content' : 'text-muted hover:text-content'">
        Register
    </button>
</div>

{{-- Cross-slide stage: both forms are stacked; the active one slides + fades in
     while the outgoing one slides out and blurs. The container height animates to
     whichever form is active (JS-measured), and the card tilts + shines on switch. --}}
<div class="portal-form-container" x-ref="formContainer" :class="{ 'h-anim': mounted }"
     style="min-height: {{ $activeMode === 'register' ? '480px' : '360px' }};">

        {{-- ─────────────── LOGIN ─────────────── --}}
            <form method="POST" action="{{ route('login') }}" data-loading
                  class="portal-form {{ $activeMode === 'login' ? 'active' : 'enter' }} mx-auto w-full max-w-[360px]"
                  x-ref="loginForm" :inert="activeForm !== 'login'">
                @csrf
                <input type="hidden" name="form_intent" value="login">

                <h1 class="mb-1 bg-gradient-to-r from-mirai-lime to-mirai-dark bg-clip-text text-lg font-semibold tracking-tight text-transparent">Welcome back</h1>
                <p class="mb-6 text-xs text-muted">Growth through Serenity</p>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                {{-- Username or email --}}
                <label class="mb-1.5 block pl-0.5 text-[11px] font-semibold text-content/80">Username or email</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" name="login" value="{{ old('login') }}" required autocomplete="username"
                           placeholder="username or name@example.com"
                           class="w-full rounded-xl border border-line bg-surface py-2 pl-11 pr-4 text-sm font-medium text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                </div>
                <x-input-error :messages="$errors->get('login')" class="mt-1.5 px-0.5" />

                {{-- Password --}}
                <div class="mb-1.5 mt-4 flex items-center justify-between pl-0.5">
                    <label class="text-[11px] font-semibold text-content/80">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-[10px] font-semibold text-accent hover:underline">Forgot Password?</a>
                    @endif
                </div>
                <div class="relative" x-data="{ show: false }">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password" placeholder="••••••••"
                           class="w-full rounded-xl border border-line bg-surface py-2 pl-11 pr-10 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-3 flex items-center text-muted hover:text-content transition-colors">
                        <i x-show="!show" data-lucide="eye" class="w-4 h-4"></i>
                        <i x-show="show" data-lucide="eye-off" class="w-4 h-4"></i>
                    </button>
                </div>

                <label class="mb-5 mt-4 flex items-center gap-2 pl-0.5">
                    <input type="checkbox" name="remember" class="rounded border-line text-accent shadow-sm focus:ring-accent">
                    <span class="text-xs text-muted">Remember me</span>
                </label>

                <button type="submit" data-loading-text="Signing in…"
                        class="w-full rounded-xl bg-gradient-to-tr from-accent-from to-accent-to py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                    Sign in
                </button>

                {{-- Divider --}}
                <div class="my-5 flex items-center gap-3">
                    <span class="h-px flex-1 bg-line"></span>
                    <span class="text-[10px] font-medium uppercase tracking-widest text-muted">or</span>
                    <span class="h-px flex-1 bg-line"></span>
                </div>

                {{-- Continue with Google (Socialite) --}}
                <a href="{{ route('auth.google') }}"
                   class="flex w-full items-center justify-center gap-2.5 rounded-xl border border-line bg-surface py-3 text-xs font-semibold uppercase tracking-widest text-content transition hover:bg-surface-muted focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.9 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.3-.4-3.5z"/>
                        <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
                        <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.3 0-9.7-3.1-11.3-7.5l-6.5 5C9.6 39.6 16.2 44 24 44z"/>
                        <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.6l6.2 5.2C39.9 36.2 44 30.7 44 24c0-1.3-.1-2.3-.4-3.5z"/>
                    </svg>
                    <span>Sign in with Google</span>
                </a>

                <p class="mt-5 text-center text-[11px] font-medium text-muted">
                    New to MiraiStudy?
                    <button type="button" @click="go('register')" class="font-semibold text-accent hover:underline">Create an account</button>
                </p>
            </form>

        {{-- ─────────────── REGISTER ─────────────── --}}
            <form method="POST" action="{{ route('register') }}" data-loading
                  class="portal-form {{ $activeMode === 'register' ? 'active' : 'enter' }} mx-auto w-full max-w-[360px]"
                  x-ref="registerForm" :inert="activeForm !== 'register'">
                @csrf
                <input type="hidden" name="form_intent" value="register">

                <h1 class="mb-1 bg-gradient-to-r from-mirai-lime to-mirai-dark bg-clip-text text-lg font-semibold tracking-tight text-transparent">Create your account</h1>
                <p class="mb-4 text-xs text-muted">Nurturing Knowledge. Start your journey.</p>

                {{-- Display name (entered first; drives the username suggestion) --}}
                <label class="mb-1 block pl-0.5 text-[11px] font-semibold text-content/80">Display name</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" name="display_name" x-model="reg.displayName" @input="onDisplayName()" required autocomplete="name"
                           placeholder="Your Full Name"
                           class="w-full rounded-xl border border-line bg-surface py-2 pl-11 pr-10 text-sm font-medium text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <span x-show="steps[0]" x-transition.opacity class="absolute right-3.5 top-1/2 -translate-y-1/2 text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                </div>
                <x-input-error :messages="$errors->get('display_name')" class="mt-1 px-0.5" />

                {{-- Username (auto-suggested from the display name) --}}
                <label class="mb-1 mt-2.5 block pl-0.5 text-[11px] font-semibold text-content/80">Username</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 font-semibold text-muted">@</span>
                    <input type="text" name="username" x-model="reg.username" @input="onUsername()" required
                           autocomplete="off" autocapitalize="none" spellcheck="false"
                           placeholder="user123"
                           class="w-full rounded-xl border border-line bg-surface py-2 pl-9 pr-10 text-sm font-medium text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2" x-show="usernameStatus === 'checking'" x-cloak>
                        <span class="block h-3.5 w-3.5 animate-spin rounded-full border-2 border-line border-t-accent"></span>
                    </span>
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-accent" x-show="usernameStatus === 'available'" x-cloak>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-red-500" x-show="usernameStatus === 'taken' || usernameStatus === 'invalid'" x-cloak>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </span>
                </div>
                {{-- availability / hint --}}
                <div class="mt-1 px-0.5 text-[10px]">
                    <span x-show="usernameStatus === 'available'" class="font-medium text-accent" x-cloak>Nice — that username is available.</span>
                    <span x-show="usernameStatus === 'taken'" class="font-medium text-red-500" x-cloak>That username is taken.</span>
                    <span x-show="usernameStatus !== 'available' && usernameStatus !== 'taken'" class="text-muted">Letters and numbers only.</span>
                </div>
                {{-- suggestion chips --}}
                <div class="mt-1.5 flex flex-wrap items-center gap-1.5" x-show="suggestions.length" x-cloak>
                    <span class="text-[10px] text-muted">Try:</span>
                    <template x-for="s in suggestions" :key="s">
                        <button type="button" @click="applySuggestion(s)"
                                class="rounded-full border border-line px-2 py-0.5 text-[10px] font-medium text-accent transition hover:bg-surface-muted">@<span x-text="s"></span></button>
                    </template>
                </div>
                <x-input-error :messages="$errors->get('username')" class="mt-1 px-0.5" />

                {{-- Email --}}
                <label class="mb-1 mt-2.5 block pl-0.5 text-[11px] font-semibold text-content/80">Email address</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </span>
                    <input type="email" name="email" x-model="reg.email" @input="validate()" required autocomplete="email"
                           placeholder="name@example.com"
                           class="w-full rounded-xl border border-line bg-surface py-2 pl-11 pr-10 text-sm font-medium text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <span x-show="steps[2]" x-transition.opacity class="absolute right-3.5 top-1/2 -translate-y-1/2 text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1 px-0.5" />

                {{-- Password + Confirm: paired two-column row --}}
                <div class="mt-2.5 grid grid-cols-2 gap-3 items-start">

                {{-- Password --}}
                <div>
                <label class="mb-1 block pl-0.5 text-[11px] font-semibold text-content/80">Password</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input :type="showPw ? 'text' : 'password'" name="password" x-model="reg.password" @input="validate()" required autocomplete="new-password" placeholder="••••••••"
                           class="w-full rounded-xl border border-line bg-surface py-2 pl-11 pr-10 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <button type="button" @click="showPw = !showPw" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-content">
                        <svg x-show="!showPw" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="showPw" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    </button>
                </div>
                {{-- strength meter --}}
                <div class="mt-1.5 flex items-center gap-2 pl-0.5" x-show="reg.password.length > 0" x-transition.opacity>
                    <div class="h-1 flex-1 overflow-hidden rounded-full bg-surface-muted">
                        <div class="h-full rounded-full transition-all duration-300"
                             :class="{ 'bg-red-500': strength === 1, 'bg-amber-500': strength === 2, 'bg-accent': strength === 3 }"
                             :style="`width: ${strengthPct}%`"></div>
                    </div>
                    <span class="text-[9px] font-bold uppercase tracking-wide text-muted" x-text="strengthLabel"></span>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 px-0.5" />
                </div>

                {{-- Confirm password --}}
                <div>
                <label class="mb-1 block pl-0.5 text-[11px] font-semibold text-content/80">Confirm password</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </span>
                    <input :type="showPw ? 'text' : 'password'" name="password_confirmation" x-model="reg.confirmation" @input="validate()" required autocomplete="new-password" placeholder="••••••••"
                           class="w-full rounded-xl border border-line bg-surface py-2 pl-11 pr-10 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                    <span x-show="steps[4]" x-transition.opacity class="absolute right-3.5 top-1/2 -translate-y-1/2 text-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                </div>
                </div>
                </div>

                <button type="submit" data-loading-text="Creating account…"
                        class="mt-4 w-full rounded-xl bg-gradient-to-tr from-accent-from to-accent-to py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                    Create account
                </button>

                {{-- Divider --}}
                <div class="my-4 flex items-center gap-3">
                    <span class="h-px flex-1 bg-line"></span>
                    <span class="text-[10px] font-medium uppercase tracking-widest text-muted">or</span>
                    <span class="h-px flex-1 bg-line"></span>
                </div>

                {{-- Continue with Google (Socialite) --}}
                <a href="{{ route('auth.google') }}"
                   class="flex w-full items-center justify-center gap-2.5 rounded-xl border border-line bg-surface py-3 text-xs font-semibold uppercase tracking-widest text-content transition hover:bg-surface-muted focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                    <svg class="h-4 w-4" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.9 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.3-.4-3.5z"/>
                        <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6.1 29.5 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
                        <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.3 0-9.7-3.1-11.3-7.5l-6.5 5C9.6 39.6 16.2 44 24 44z"/>
                        <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.6l6.2 5.2C39.9 36.2 44 30.7 44 24c0-1.3-.1-2.3-.4-3.5z"/>
                    </svg>
                    <span>Sign up with Google</span>
                </a>

                <p class="mt-4 text-center text-[11px] font-medium text-muted">
                    Already have an account?
                    <button type="button" @click="go('login')" class="font-semibold text-accent hover:underline">Log in</button>
                </p>
            </form>
</div>

@endif
