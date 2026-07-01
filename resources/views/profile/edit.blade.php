<x-app-layout>
    <x-slot name="title">Edit Profile — MiraiStudy</x-slot>

    <div class="max-w-[600px] mx-auto space-y-5 py-2">

        {{-- Page header --}}
        <header class="px-1">
            <h1 class="text-2xl font-bold tracking-tight text-content">Edit Profile</h1>
            <p class="mt-1 text-sm text-muted">Manage your public information, security, and privacy.</p>
        </header>

        {{-- ───────────────────── Profile Information ───────────────────── --}}
        <form method="POST" action="{{ route('profile.update') }}" data-loading
              enctype="multipart/form-data"
              class="rounded-2xl border border-line bg-surface shadow-sm overflow-hidden"
              x-data="profileForm({
                  displayName: @js(old('display_name', $user->display_name)),
                  username: @js($user->username),
                  bio: @js($user->bio ?? ''),
                  hasImage: {{ $user->profile_image ? 'true' : 'false' }},
                  existingUrl: @js($user->profile_image),
                  checkUrl: '{{ route('profile.username-available') }}',
              })">
            @csrf
            @method('PATCH')
            <input type="hidden" name="remove_profile_image" :value="removed ? 1 : 0">

            {{-- Identity header band: avatar + live name/handle preview --}}
            <div class="relative flex items-center gap-4 px-6 py-5 bg-accent/10 border-b border-line">
                {{-- Avatar with camera overlay --}}
                <div class="relative shrink-0">
                    <template x-if="showImage">
                        <img :src="preview || existingUrl"
                             class="w-20 h-20 rounded-full object-cover ring-4 ring-surface shadow-sm"
                             alt="Profile photo">
                    </template>
                    <template x-if="!showImage">
                        <div class="w-20 h-20 rounded-full bg-accent/20 flex items-center justify-center text-accent font-bold text-2xl ring-4 ring-surface shadow-sm">
                            <span x-text="(displayName.trim()[0] || '{{ strtoupper(substr($user->display_name, 0, 1)) }}').toUpperCase()"></span>
                        </div>
                    </template>

                    <label for="profile_image_input" title="Change photo" aria-label="Change photo"
                           class="absolute -bottom-1 -right-1 grid place-items-center w-7 h-7 rounded-full bg-accent text-white ring-2 ring-surface cursor-pointer hover:bg-accent-strong transition-colors">
                        <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                    </label>
                    <input id="profile_image_input" x-ref="fileInput"
                           type="file" name="profile_image"
                           accept="image/*" class="hidden"
                           @change="handlePreview($event)">
                </div>

                {{-- Live preview of how the profile reads --}}
                <div class="min-w-0 flex-1">
                    <div class="text-base font-bold text-content truncate" x-text="displayName.trim() || 'Your name'"></div>
                    <div class="text-sm text-muted truncate">@<span x-text="username || 'username'"></span></div>
                    <div class="mt-1.5 flex items-center gap-3 text-[11px]">
                        <label for="profile_image_input" class="cursor-pointer font-semibold text-accent hover:text-accent-strong transition-colors">Change photo</label>
                        <button type="button" x-show="showImage" @click="removePhoto()"
                                class="font-semibold text-muted hover:text-red-600 transition-colors">Remove</button>
                    </div>
                </div>
            </div>

            {{-- Fields --}}
            <div class="p-6 space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Display Name --}}
                    <div>
                        <label class="block text-[13px] font-semibold text-content mb-1.5">Display Name</label>
                        <input type="text" name="display_name"
                               x-model="displayName"
                               class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 text-sm text-content outline-none focus:border-accent focus:bg-surface transition-colors"
                               required>
                        @error('display_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Username --}}
                    <div>
                        <label class="block text-[13px] font-semibold text-content mb-1.5">Username</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-muted">@</span>
                            <input type="text" name="username"
                                   x-model="username"
                                   @input="onUsernameInput()"
                                   autocomplete="off" autocapitalize="none" spellcheck="false"
                                   class="w-full rounded-xl border border-line bg-surface-muted pl-8 pr-10 py-2.5 text-sm text-content outline-none focus:border-accent focus:bg-surface transition-colors"
                                   :class="{
                                      'border-red-300 focus:border-red-300': usernameStatus === 'taken' || usernameStatus === 'invalid',
                                      'border-green-300 focus:border-green-300': usernameStatus === 'available' && username !== originalUsername,
                                   }"
                                   required>
                            <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2">
                                <i x-show="usernameStatus === 'checking'" data-lucide="loader-2" class="w-4 h-4 text-muted animate-spin"></i>
                                <i x-show="usernameStatus === 'available' && username !== originalUsername" x-cloak data-lucide="check" class="w-4 h-4 text-green-600"></i>
                                <i x-show="usernameStatus === 'taken'" x-cloak data-lucide="x" class="w-4 h-4 text-red-500"></i>
                            </span>
                        </div>
                        <p x-show="usernameStatus === 'taken'" x-cloak class="text-red-500 text-xs mt-1">That username is already taken.</p>
                        <p x-show="usernameStatus === 'invalid'" x-cloak class="text-muted text-xs mt-1">3–30 lowercase letters and numbers.</p>
                        @error('username')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Bio --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-[13px] font-semibold text-content">Bio</label>
                        <span class="text-[11px] tabular-nums"
                              :class="bioNearLimit ? 'text-amber-500 font-semibold' : 'text-muted'"
                              x-text="`${bio.length}/${bioMax}`"></span>
                    </div>
                    <textarea name="bio" rows="3" maxlength="500"
                              x-model="bio"
                              placeholder="Tell others about yourself..."
                              class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 text-sm text-content resize-none outline-none focus:border-accent focus:bg-surface transition-colors"></textarea>
                    @error('bio')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email (read-only) --}}
                <div>
                    <label class="block text-[13px] font-semibold text-content mb-1.5">
                        Email <span class="text-muted font-normal">(read-only)</span>
                    </label>
                    <div class="flex items-center gap-2 rounded-xl border border-line bg-surface-muted px-4 py-2.5">
                        <i data-lucide="mail" class="w-4 h-4 text-muted shrink-0"></i>
                        <span class="text-sm text-muted truncate">{{ $user->email }}</span>
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-muted ml-auto shrink-0"></i>
                    </div>
                </div>

                <div class="pt-1 flex items-center justify-end gap-3">
                    <button type="submit" data-loading-text="Saving…"
                            :disabled="!canSave"
                            class="rounded-lg bg-gradient-to-tr from-accent-from to-accent-to px-5 py-2.5 text-[13px] font-bold text-white hover:opacity-90 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>

        {{-- ───────────────────── Security / Password ───────────────────── --}}
        <div class="rounded-2xl border border-line bg-surface p-6 shadow-sm" x-data="{ open: {{ $errors->updatePassword->isNotEmpty() ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between text-left">
                <h2 class="text-base font-semibold text-content">Password</h2>
                <i data-lucide="chevron-down" class="w-4 h-4 text-muted transition-transform"
                   :class="open ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="open" x-collapse.duration.700ms x-cloak class="mt-4">
                <p class="text-xs text-muted mb-4">Use a long, random password to keep your account secure.</p>

            @if ($user->password)
                <form method="POST" action="{{ route('password.update') }}" data-loading class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div x-data="{ show: false }">
                        <label class="block text-[13px] font-semibold text-content mb-1.5">Current Password</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" name="current_password" autocomplete="current-password"
                                   class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 pr-10 text-sm text-content outline-none focus:border-accent focus:bg-surface transition-colors">
                            <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-3 flex items-center text-muted hover:text-content transition-colors">
                                <i x-show="!show" data-lucide="eye" class="w-4 h-4"></i>
                                <i x-show="show" data-lucide="eye-off" class="w-4 h-4"></i>
                            </button>
                        </div>
                        @error('current_password', 'updatePassword')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ show: false }" class="space-y-4">
                        <div>
                            <label class="block text-[13px] font-semibold text-content mb-1.5">New Password</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password" autocomplete="new-password"
                                       class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 pr-10 text-sm text-content outline-none focus:border-accent focus:bg-surface transition-colors">
                                <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 right-3 flex items-center text-muted hover:text-content transition-colors">
                                    <i x-show="!show" data-lucide="eye" class="w-4 h-4"></i>
                                    <i x-show="show" data-lucide="eye-off" class="w-4 h-4"></i>
                                </button>
                            </div>
                            @error('password', 'updatePassword')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[13px] font-semibold text-content mb-1.5">Confirm New Password</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password_confirmation" autocomplete="new-password"
                                       class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 pr-10 text-sm text-content outline-none focus:border-accent focus:bg-surface transition-colors">
                                <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 right-3 flex items-center text-muted hover:text-content transition-colors">
                                    <i x-show="!show" data-lucide="eye" class="w-4 h-4"></i>
                                    <i x-show="show" data-lucide="eye-off" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-1 flex items-center justify-end gap-3">
                        @if (session('status') === 'password-updated')
                            @push('scripts')
                            <script>(function(d) { window._snackbarComponent ? window._snackbarComponent.show(d) : window._snackbarQueue.push(d); })({ message: 'Password updated.', type: 'success' });</script>
                            @endpush
                        @endif
                        <button type="submit" data-loading-text="Updating…"
                                class="rounded-lg bg-gradient-to-tr from-accent-from to-accent-to px-5 py-2.5 text-[13px] font-bold text-white hover:opacity-90 transition-all active:scale-95">
                            Update Password
                        </button>
                    </div>
                </form>
            @else
                <div class="flex items-start gap-3 rounded-xl bg-surface-muted px-4 py-3.5">
                    <i data-lucide="info" class="w-4 h-4 text-muted mt-0.5 shrink-0"></i>
                    <p class="text-xs text-muted leading-relaxed">
                        Your account signs in with Google, so it has no password to change. Manage sign-in security from your Google account.
                    </p>
                </div>
            @endif
            </div>
        </div>

        {{-- ───────────────────── Privacy ───────────────────── --}}
        <div class="rounded-2xl border border-line bg-surface p-6 shadow-sm">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-content">Privacy</h2>
                <p class="mt-0.5 text-xs text-muted">Control what others can see on your profile.</p>
            </div>

            <label class="flex items-center justify-between gap-4 rounded-xl bg-surface-muted p-4 cursor-pointer"
                   x-data="{
                        on: {{ $preferences->show_liked_posts ? 'true' : 'false' }},
                        loading: false,
                        async toggle() {
                            if (this.loading) return;
                            this.loading = true;
                            const next = !this.on;
                            this.on = next;
                            try {
                                const res = await fetch('{{ route('profile.preferences') }}', {
                                    method: 'PATCH',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({ show_liked_posts: next })
                                });
                                if (!res.ok) throw new Error('Request failed');
                                const data = await res.json();
                                this.on = data.show_liked_posts;
                            } catch (e) {
                                this.on = !next; // revert on failure
                            } finally {
                                this.loading = false;
                            }
                        }
                   }"
                   @click.prevent="toggle()">
                <div>
                    <div class="text-sm font-semibold text-content">Show liked posts</div>
                    <div class="text-xs text-muted mt-0.5">
                        Others can see posts you've liked on your profile.
                    </div>
                </div>
                <button type="button" role="switch"
                        :aria-checked="on ? 'true' : 'false'"
                        :class="on ? 'bg-accent' : 'bg-line'"
                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-surface cursor-pointer"
                        :style="loading ? 'opacity:0.6' : ''"
                        @click.prevent="toggle()">
                    <span :class="on ? 'translate-x-5' : 'translate-x-1'"
                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
            </label>
        </div>

        {{-- ───────────────────── Delete Account ───────────────────── --}}
        <div class="rounded-2xl border border-red-200 bg-surface p-6 shadow-sm" x-data="{ confirm: false }">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-red-600">Delete Account</h2>
                <p class="mt-0.5 text-xs text-muted">Permanently delete your account and all of your data. This cannot be undone.</p>
            </div>

            <button type="button" @click="confirm = true" x-show="!confirm"
                    class="rounded-lg border border-red-200 px-4 py-2 text-[13px] font-bold text-red-600 hover:bg-red-50 transition-colors">
                Delete My Account
            </button>

            <form method="POST" action="{{ route('profile.destroy') }}" data-loading
                  x-show="confirm" x-cloak class="space-y-3">
                @csrf
                @method('DELETE')

                <p class="text-sm text-muted">Enter your password to confirm deletion.</p>

                <input type="password" name="password" placeholder="Your password"
                       class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 text-sm text-content outline-none focus:border-red-400 transition-colors">

                @if ($errors->userDeletion->has('password'))
                    <p class="text-red-500 text-xs">{{ $errors->userDeletion->first('password') }}</p>
                @endif

                <div class="flex items-center gap-2">
                    <button type="submit" data-loading-text="Deleting…"
                            class="rounded-lg bg-red-600 px-4 py-2 text-[13px] font-bold text-white hover:bg-red-700 transition-colors">
                        Confirm Delete
                    </button>
                    <button type="button" @click="confirm = false"
                            class="rounded-lg border border-line px-4 py-2 text-[13px] font-semibold text-muted hover:bg-surface-muted transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

    </div>

    <script>
        function profileForm(opts) {
            return {
                // ── avatar ──
                preview: null,
                removed: false,
                hasImage: opts.hasImage,
                existingUrl: opts.existingUrl,

                // ── identity ──
                displayName: opts.displayName,

                // ── username ──
                checkUrl: opts.checkUrl,
                originalUsername: opts.username,
                username: opts.username,
                usernameStatus: 'available',   // '' | 'checking' | 'available' | 'taken' | 'invalid'
                _unTimer: null,

                // ── bio ──
                bio: opts.bio,
                bioMax: 500,

                get showImage() {
                    return !!this.preview || (this.hasImage && !this.removed);
                },

                get bioNearLimit() {
                    return this.bio.length > this.bioMax * 0.9;
                },

                get canSave() {
                    return this.usernameStatus !== 'taken'
                        && this.usernameStatus !== 'invalid'
                        && this.usernameStatus !== 'checking';
                },

                handlePreview(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    if (this.preview) URL.revokeObjectURL(this.preview);
                    this.preview = URL.createObjectURL(file);
                    this.removed = false;
                },

                removePhoto() {
                    if (this.preview) {
                        URL.revokeObjectURL(this.preview);
                        this.preview = null;
                    }
                    this.$refs.fileInput.value = '';
                    this.removed = true;
                },

                onUsernameInput() {
                    this.username = this.username.toLowerCase();
                    clearTimeout(this._unTimer);

                    if (this.username === this.originalUsername) {
                        this.usernameStatus = 'available';
                        return;
                    }
                    if (!/^[a-z0-9]{3,30}$/.test(this.username)) {
                        this.usernameStatus = 'invalid';
                        return;
                    }
                    this.usernameStatus = 'checking';
                    this._unTimer = setTimeout(() => this.checkUsername(), 400);
                },

                async checkUsername() {
                    const u = this.username.trim();
                    if (u === this.originalUsername) { this.usernameStatus = 'available'; return; }
                    if (!/^[a-z0-9]{3,30}$/.test(u)) { this.usernameStatus = 'invalid'; return; }
                    try {
                        const res = await fetch(`${this.checkUrl}?username=${encodeURIComponent(u)}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        this.usernameStatus = data.available ? 'available' : 'taken';
                    } catch (e) {
                        this.usernameStatus = '';   // unknown — let the server validate on submit
                    }
                },
            }
        }
    </script>
</x-app-layout>
