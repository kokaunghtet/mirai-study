<x-app-layout>
    <x-slot name="title">Edit Profile — MiraiStudy</x-slot>

    <div class="max-w-[560px] mx-auto space-y-5">

        {{-- Profile Info --}}
        <div class="bg-surface rounded-2xl border border-line overflow-hidden">
            <div class="px-6 py-4 border-b border-line">
                <h2 class="text-[15px] font-bold text-content">Profile Information</h2>
                <p class="text-xs text-muted mt-0.5">Update your display name, username, bio, and photo.</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" data-loading
                  enctype="multipart/form-data"
                  class="px-6 py-5 space-y-5"
                  x-data="profileEditor()">
                @csrf
                @method('PATCH')

                {{-- Profile Image --}}
                <div class="flex items-center gap-4">
                    <div class="relative shrink-0">
                        <template x-if="preview">
                            <img :src="preview"
                                 class="w-16 h-16 rounded-full object-cover border-2 border-line">
                        </template>
                        <template x-if="!preview">
                            @if ($user->profile_image)
                                <img src="{{ $user->profile_image }}"
                                     class="w-16 h-16 rounded-full object-cover border-2 border-line"
                                     alt="">
                            @else
                                <div class="w-16 h-16 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold text-xl border-2 border-line">
                                    {{ strtoupper(substr($user->display_name, 0, 1)) }}
                                </div>
                            @endif
                        </template>
                    </div>

                    <div>
                        <label for="profile_image_input"
                               class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-line bg-surface px-3 py-1.5 text-[13px] font-semibold text-content hover:bg-surface-muted transition-colors">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            Upload Photo
                        </label>
                        <input id="profile_image_input"
                               type="file" name="profile_image"
                               accept="image/*" class="hidden"
                               @change="handlePreview($event)">
                        <p class="text-[11px] text-muted mt-1">JPG, PNG, GIF up to 5MB</p>
                    </div>
                </div>

                {{-- Display Name --}}
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase tracking-wide mb-1.5">
                        Display Name
                    </label>
                    <input type="text" name="display_name"
                           value="{{ old('display_name', $user->display_name) }}"
                           class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 text-sm text-content outline-none focus:border-accent focus:bg-surface transition-colors"
                           required>
                    @error('display_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Username --}}
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase tracking-wide mb-1.5">
                        Username
                    </label>
                    <div class="flex items-center rounded-xl border border-line bg-surface-muted px-4 py-2.5 focus-within:border-accent focus-within:bg-surface transition-colors">
                        <span class="text-sm text-muted mr-1">@</span>
                        <input type="text" name="username"
                               value="{{ old('username', $user->username) }}"
                               class="flex-1 bg-transparent text-sm text-content outline-none"
                               required>
                    </div>
                    @error('username')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bio --}}
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase tracking-wide mb-1.5">
                        Bio
                    </label>
                    <textarea name="bio" rows="3"
                              placeholder="Tell others about yourself..."
                              class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 text-sm text-content resize-none outline-none focus:border-accent focus:bg-surface transition-colors">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email (read-only) --}}
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase tracking-wide mb-1.5">
                        Email <span class="text-muted font-normal normal-case">(read-only)</span>
                    </label>
                    <input type="email" value="{{ $user->email }}"
                           class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 text-sm text-muted cursor-not-allowed"
                           disabled>
                </div>

                <div class="pt-1 flex justify-end">
                    <button type="submit" data-loading-text="Saving…"
                            class="rounded-lg bg-accent px-5 py-2.5 text-[13px] font-bold text-white hover:bg-accent-strong transition-all active:scale-95">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Privacy Settings --}}
        <div class="bg-surface rounded-2xl border border-line overflow-hidden">
            <div class="px-6 py-4 border-b border-line">
                <h2 class="text-[15px] font-bold text-content">Privacy</h2>
                <p class="text-xs text-muted mt-0.5">Control what others can see on your profile.</p>
            </div>

            <div class="px-6 py-5">
                <label class="flex items-center justify-between cursor-pointer"
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
                    <div class="relative ml-4 shrink-0">
                        <div :class="on ? 'bg-accent' : 'bg-surface-muted'"
                             class="w-11 h-6 rounded-full transition-colors cursor-pointer"
                             :style="loading ? 'opacity:0.6' : ''">
                            <div :class="on ? 'translate-x-5' : 'translate-x-0.5'"
                                 class="mt-0.5 ml-0.5 w-5 h-5 bg-surface rounded-full shadow transition-transform">
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Delete Account --}}
        <div class="bg-surface rounded-2xl border border-red-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-red-100">
                <h2 class="text-[15px] font-bold text-red-600">Delete Account</h2>
                <p class="text-xs text-muted mt-0.5">Permanently delete your account and all data.</p>
            </div>

            <div class="px-6 py-5" x-data="{ confirm: false }">
                <button type="button" @click="confirm = true"
                        x-show="!confirm"
                        class="rounded-lg border border-red-200 px-4 py-2 text-[13px] font-bold text-red-600 hover:bg-red-50 transition-colors">
                    Delete My Account
                </button>

                <form method="POST" action="{{ route('profile.destroy') }}" data-loading
                      x-show="confirm" class="space-y-3">
                    @csrf
                    @method('DELETE')

                    <p class="text-sm text-muted">
                        Enter your password to confirm deletion. This cannot be undone.
                    </p>

                    <input type="password" name="password"
                           placeholder="Your password"
                           class="w-full rounded-xl border border-line bg-surface-muted px-4 py-2.5 text-sm outline-none focus:border-red-400 transition-colors">

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

    </div>

    <script>
        function profileEditor() {
            return {
                preview: null,
                handlePreview(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    if (this.preview) URL.revokeObjectURL(this.preview);
                    this.preview = URL.createObjectURL(file);
                }
            }
        }
    </script>
</x-app-layout>