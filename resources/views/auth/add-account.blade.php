<x-app-layout>
    <x-slot name="title">Add account — MiraiStudy</x-slot>

    <div class="mx-auto w-full max-w-sm">
        <a href="{{ route('feed.index') }}" class="mb-6 inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-content">
            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
            Back to feed
        </a>

        <div class="rounded-2xl border border-line bg-surface p-6 shadow-sm">
            <h1 class="mb-1 text-lg font-semibold text-content">Add another account</h1>
            <p class="mb-6 text-xs text-muted">Sign in to switch between accounts from the sidebar without logging out.</p>

            <form method="POST" action="{{ route('accounts.add.store') }}" data-loading>
                @csrf

                <label class="mb-1.5 block pl-0.5 text-[11px] font-semibold text-content/80">Username or email</label>
                <input type="text" name="login" value="{{ old('login') }}" required autocomplete="username"
                       placeholder="username or name@example.com"
                       class="w-full rounded-xl border border-line bg-surface py-2 px-4 text-sm font-medium text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                <x-input-error :messages="$errors->get('login')" class="mt-1.5 px-0.5" />

                <label class="mb-1.5 mt-4 block pl-0.5 text-[11px] font-semibold text-content/80">Password</label>
                <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                       class="w-full rounded-xl border border-line bg-surface py-2 px-4 text-sm text-content placeholder:text-muted focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                <x-input-error :messages="$errors->get('login_password')" class="mt-1.5 px-0.5" />

                <button type="submit" data-loading-text="Signing in…"
                        class="mt-6 w-full rounded-xl bg-gradient-to-tr from-accent-from to-accent-to py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                    Sign in
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
