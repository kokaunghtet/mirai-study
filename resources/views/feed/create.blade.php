<x-app-layout>
    <x-slot name="title">Create Post — MiraiStudy</x-slot>

    <div class="mx-auto max-w-[600px] px-4">
        <div class="rounded-2xl bg-surface border border-line shadow-sm overflow-hidden">

                {{-- Header --}}
                <header class="flex items-center justify-between px-5 py-4 border-b border-line">
                    <h2 class="text-[15px] font-bold text-content">Create post</h2>
                    <a href="{{ route('feed.index') }}"
                       class="grid h-8 w-8 place-items-center rounded-full text-muted hover:bg-surface-muted hover:text-red-500 transition-colors">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </a>
                </header>

                {{-- Author --}}
                <section class="flex items-center gap-2.5 px-5 py-3">
                    @if (auth()->user()->profile_image)
                        <img src="{{ auth()->user()->profile_image }}"
                            alt="{{ auth()->user()->display_name }}"
                            loading="lazy"
                            class="h-10 w-10 rounded-full object-cover">
                    @else
                        <div class="grid h-10 w-10 place-items-center rounded-full bg-accent/15 text-[15px] font-bold text-accent">
                            {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-content truncate">{{ auth()->user()->display_name }}</div>
                        <p class="text-[11px] text-muted">Public post</p>
                    </div>
                </section>

                <form method="POST" action="{{ route('posts.store') }}" data-loading
                      enctype="multipart/form-data"
                      x-data="postComposer()">
                    @csrf

                    {{-- Type Tabs --}}
                    <nav class="flex gap-1 px-5 pb-3">
                        <button type="button" @click="setTab('text')"
                                :class="tab === 'text'
                                    ? 'bg-surface text-content font-bold border-line shadow-sm'
                                    : 'bg-transparent text-muted border-transparent hover:bg-surface-muted'"
                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border px-1 py-2 text-xs transition-colors">
                            <i data-lucide="align-left" class="h-3.5 w-3.5"></i>
                            Text
                        </button>
                        <button type="button" @click="setTab('media')"
                                :class="tab === 'media'
                                    ? 'bg-surface text-content font-bold border-line shadow-sm'
                                    : 'bg-transparent text-muted border-transparent hover:bg-surface-muted'"
                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border px-1 py-2 text-xs transition-colors">
                            <i data-lucide="image" class="h-3.5 w-3.5"></i>
                            Media
                        </button>
                        <button type="button" @click="setTab('file')"
                                :class="tab === 'file'
                                    ? 'bg-surface text-content font-bold border-line shadow-sm'
                                    : 'bg-transparent text-muted border-transparent hover:bg-surface-muted'"
                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border px-1 py-2 text-xs transition-colors">
                            <i data-lucide="file" class="h-3.5 w-3.5"></i>
                            File
                        </button>
                    </nav>

                    {{-- Content panels --}}
                    <section class="px-5 pb-3">

                        {{-- Text — always visible --}}
                        <textarea name="content" rows="1"
                                  placeholder="What's on your mind?"
                                  class="min-h-[88px] w-full resize-none rounded-xl bg-surface-muted px-3.5 py-3 text-sm leading-6 text-content border border-line outline-none placeholder:text-muted focus:border-accent transition-colors"
                                  :required="tab === 'text'">{{ old('content') }}</textarea>
                        @error('content')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        {{-- IMPORTANT: Persistent media input — always in DOM, never removed --}}
                        <input type="file" id="mediaFileInput" name="media[]"
                               accept="image/*" multiple class="hidden"
                               @change="handleMedia($event)">
                        @error('media.*')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        {{-- Media panel --}}
                        <div x-show="tab === 'media'" class="mt-2">

                            {{-- Drop zone — shown when no previews --}}
                            <div x-show="mediaPreviews.length === 0">
                                <label for="mediaFileInput"
                                       class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-line px-4 py-5 text-center transition-all hover:bg-surface-muted">
                                    <i data-lucide="image" class="h-8 w-8 text-muted"></i>
                                    <p class="text-[13px] font-semibold text-content">Add photos</p>
                                    <span class="text-[11px] text-muted">Up to 10 images</span>
                                </label>
                            </div>

                            {{-- Preview carousel — shown when previews exist --}}
                            <div x-show="mediaPreviews.length > 0">
                                <div class="relative aspect-video overflow-hidden rounded-xl bg-black">

                                    {{-- Slides --}}
                                    <template x-for="(src, i) in mediaPreviews" :key="i">
                                        <img x-show="mediaIdx === i"
                                             :src="src"
                                             class="h-full w-full object-cover absolute inset-0">
                                    </template>

                                    {{-- Counter --}}
                                    <div x-show="mediaPreviews.length > 1"
                                         class="absolute right-2.5 top-2.5 rounded-md bg-black/70 px-2 py-1 text-[11px] font-semibold text-white"
                                         x-text="`${mediaIdx + 1}/${mediaPreviews.length}`">
                                    </div>

                                    {{-- Change button — triggers same persistent input --}}
                                    <label for="mediaFileInput"
                                           class="absolute bottom-2 right-2 cursor-pointer rounded-md bg-black/70 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-black/90 transition-colors">
                                        Change
                                    </label>

                                    {{-- Prev/Next --}}
                                    <button type="button"
                                            x-show="mediaPreviews.length > 1"
                                            @click="mediaIdx = Math.max(0, mediaIdx - 1)"
                                            class="absolute left-2 top-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white text-xl hover:bg-black/70">
                                        ‹
                                    </button>
                                    <button type="button"
                                            x-show="mediaPreviews.length > 1"
                                            @click="mediaIdx = Math.min(mediaPreviews.length - 1, mediaIdx + 1)"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white text-xl hover:bg-black/70">
                                        ›
                                    </button>
                                </div>

                                {{-- Dots --}}
                                <div x-show="mediaPreviews.length > 1" class="flex justify-center gap-1.5 py-2">
                                    <template x-for="(_, i) in mediaPreviews" :key="i">
                                        <button type="button" @click="mediaIdx = i"
                                                :class="mediaIdx === i ? 'bg-accent' : 'bg-gray-300'"
                                                class="h-1.5 w-1.5 rounded-full transition-colors">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- IMPORTANT: Persistent file input — always in DOM --}}
                        <input type="file" id="fileAttachInput" name="files[]"
                               accept="image/*,.pdf,.txt" multiple class="hidden"
                               @change="handleFiles($event)">
                        @error('files.*')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        {{-- File panel --}}
                        <div x-show="tab === 'file'" class="mt-2">
                            <label for="fileAttachInput"
                                   class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border-2 border-dashed border-line px-4 py-4 text-center transition-all hover:bg-surface-muted">
                                <i data-lucide="upload" class="h-7 w-7 text-muted"></i>
                                <p class="text-[13px] font-semibold text-content">Attach files</p>
                                <span class="text-[11px] text-muted">Images, PDF or TXT</span>
                            </label>

                            {{-- File chips --}}
                            <div class="mt-2.5 flex flex-col gap-1.5">
                                <template x-for="(file, i) in attachedFiles" :key="i">
                                    <div class="flex items-center gap-2.5 rounded-lg border border-line bg-surface px-3 py-2.5">
                                        <div class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-lg bg-accent/15 text-accent">
                                            <i data-lucide="file" class="h-4 w-4"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-xs font-semibold text-content" x-text="file.name"></div>
                                            <div class="text-[11px] text-muted" x-text="formatSize(file.size)"></div>
                                        </div>
                                        <button type="button" @click="removeFile(i)"
                                                class="text-muted hover:text-red-500 transition-colors">
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </section>

                    {{-- Optional title --}}
                    <div class="px-5 pb-3">
                        <input type="text" name="title" value="{{ old('title') }}"
                               placeholder="Add a title (optional)"
                               class="w-full bg-transparent text-sm text-muted outline-none placeholder:text-muted rounded-xl border-b border-line p-2 focus:border-accent transition-colors">
                    </div>

                    {{-- Tags --}}
                    <div class="px-5 pb-3">
                        <p class="text-[11px] font-semibold text-muted uppercase tracking-wide mb-2">Tags</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                           {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }}
                                           class="rounded text-accent border-line">
                                    <span class="text-xs text-muted">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Submit --}}
                    <footer class="flex items-center justify-end px-5 py-4 border-t border-line">
                        <button type="submit" data-loading-text="Publishing…"
                                class="flex items-center gap-1.5 rounded-lg bg-gradient-to-tr from-accent-from to-accent-to px-5 py-2.5 text-[13px] font-bold text-white transition-all hover:opacity-90 active:scale-95 shadow-sm">
                            <i data-lucide="send" class="h-4 w-4"></i>
                            Publish
                        </button>
                    </footer>
                </form>
        </div>
    </div>

    <script>
        function postComposer() {
            return {
                tab: 'text',
                mediaPreviews: [],
                mediaIdx: 0,
                attachedFiles: [],

                setTab(t) {
                    this.tab = t;
                },

                handleMedia(event) {
                    const files = Array.from(event.target.files).slice(0, 10);
                    // Revoke old object URLs to avoid memory leaks
                    this.mediaPreviews.forEach(url => URL.revokeObjectURL(url));
                    this.mediaPreviews = files.map(f => URL.createObjectURL(f));
                    this.mediaIdx = 0;
                },

                handleFiles(event) {
                    this.attachedFiles = Array.from(event.target.files);
                },

                removeFile(index) {
                    this.attachedFiles.splice(index, 1);
                    // Rebuild the FileList from the pruned array so the form
                    // submits only the remaining files (FileList is read-only;
                    // DataTransfer is the only spec-compliant way to write it).
                    const input = document.getElementById('fileAttachInput');
                    if (input) {
                        const dt = new DataTransfer();
                        this.attachedFiles.forEach(f => dt.items.add(f));
                        input.files = dt.files;
                    }
                },

                formatSize(bytes) {
                    return Math.max(1, Math.round(bytes / 1024)) + ' KB';
                }
            }
        }
    </script>
</x-app-layout>