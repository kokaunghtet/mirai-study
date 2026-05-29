<x-app-layout>
    <x-slot name="title">Create Post — MiraiStudy</x-slot>

    <div class="flex justify-center px-4 py-6">
        <div class="w-full max-w-[560px]">
            <div class="rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden">

                {{-- Header --}}
                <header class="flex items-center justify-between px-[18px] py-4 border-b border-gray-100">
                    <h2 class="text-[15px] font-bold text-gray-900">Create post</h2>
                    <a href="{{ route('feed.index') }}"
                       class="grid h-8 w-8 place-items-center rounded-full text-gray-400 hover:bg-gray-100 transition-colors">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </a>
                </header>

                {{-- Author --}}
                <section class="flex items-center gap-2.5 px-[18px] py-3.5">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-green-100 text-[15px] font-bold text-green-600">
                        {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-900">{{ auth()->user()->display_name }}</div>
                        <p class="text-[11px] text-gray-400">Public post</p>
                    </div>
                </section>

                <form method="POST" action="{{ route('posts.store') }}"
                      enctype="multipart/form-data"
                      x-data="postComposer()">
                    @csrf

                    {{-- Type Tabs --}}
                    <nav class="flex gap-1 px-[18px] pb-3">
                        <button type="button" @click="setTab('text')"
                                :class="tab === 'text'
                                    ? 'bg-white text-gray-900 font-bold border-gray-200 shadow-sm'
                                    : 'bg-transparent text-gray-400 border-transparent hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border px-1 py-2 text-xs transition-colors">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 6h16M4 12h10M4 18h14"/>
                            </svg>
                            Text
                        </button>
                        <button type="button" @click="setTab('media')"
                                :class="tab === 'media'
                                    ? 'bg-white text-gray-900 font-bold border-gray-200 shadow-sm'
                                    : 'bg-transparent text-gray-400 border-transparent hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border px-1 py-2 text-xs transition-colors">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="3"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <path d="M21 15l-5-5L5 21"/>
                            </svg>
                            Media
                        </button>
                        <button type="button" @click="setTab('file')"
                                :class="tab === 'file'
                                    ? 'bg-white text-gray-900 font-bold border-gray-200 shadow-sm'
                                    : 'bg-transparent text-gray-400 border-transparent hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border px-1 py-2 text-xs transition-colors">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <path d="M14 2v6h6"/>
                            </svg>
                            File
                        </button>
                    </nav>

                    {{-- Content panels --}}
                    <section class="px-[18px] pb-2.5">

                        {{-- Text — always visible --}}
                        <textarea name="content" rows="4"
                                  placeholder="What's on your mind?"
                                  class="min-h-[90px] w-full resize-none rounded-xl bg-gray-50 px-3.5 py-3 text-sm leading-6 text-gray-900 border border-gray-200 outline-none placeholder:text-gray-400 focus:border-green-400 transition-colors"
                                  required>{{ old('content') }}</textarea>
                        @error('content')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        {{-- IMPORTANT: Persistent media input — always in DOM, never removed --}}
                        <input type="file" id="mediaFileInput" name="media[]"
                               accept="image/*" multiple class="hidden"
                               @change="handleMedia($event)">

                        {{-- Media panel --}}
                        <div x-show="tab === 'media'" class="mt-2">

                            {{-- Drop zone — shown when no previews --}}
                            <div x-show="mediaPreviews.length === 0">
                                <label for="mediaFileInput"
                                       class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-gray-300 px-4 py-5 text-center transition-all hover:bg-gray-50">
                                    <svg class="h-8 w-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <rect x="3" y="3" width="18" height="18" rx="3"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <path d="M21 15l-5-5L5 21"/>
                                    </svg>
                                    <p class="text-[13px] font-semibold text-gray-700">Add photos</p>
                                    <span class="text-[11px] text-gray-400">Up to 10 images</span>
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
                                                :class="mediaIdx === i ? 'bg-green-500' : 'bg-gray-300'"
                                                class="h-1.5 w-1.5 rounded-full transition-colors">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- IMPORTANT: Persistent file input — always in DOM --}}
                        <input type="file" id="fileAttachInput" name="files[]"
                               multiple class="hidden"
                               @change="handleFiles($event)">

                        {{-- File panel --}}
                        <div x-show="tab === 'file'" class="mt-2">
                            <label for="fileAttachInput"
                                   class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border-2 border-dashed border-gray-300 px-4 py-4 text-center transition-all hover:bg-gray-50">
                                <svg class="h-7 w-7 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                <p class="text-[13px] font-semibold text-gray-700">Attach files</p>
                                <span class="text-[11px] text-gray-400">Any file type</span>
                            </label>

                            {{-- File chips --}}
                            <div class="mt-2.5 flex flex-col gap-1.5">
                                <template x-for="(file, i) in attachedFiles" :key="i">
                                    <div class="flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5">
                                        <div class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-lg bg-green-100 text-green-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <path d="M14 2v6h6"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-xs font-semibold text-gray-900" x-text="file.name"></div>
                                            <div class="text-[11px] text-gray-400" x-text="formatSize(file.size)"></div>
                                        </div>
                                        <button type="button" @click="removeFile(i)"
                                                class="text-gray-400 hover:text-red-500 transition-colors">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M18 6 6 18M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </section>

                    {{-- Optional title --}}
                    <div class="px-[18px] pb-3">
                        <input type="text" name="title" value="{{ old('title') }}"
                               placeholder="Add a title (optional)"
                               class="w-full bg-transparent text-sm text-gray-600 outline-none placeholder:text-gray-300 border-b border-gray-100 pb-1 focus:border-green-300 transition-colors">
                    </div>

                    {{-- Tags --}}
                    <div class="px-[18px] pb-3">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                           {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }}
                                           class="rounded text-green-600 border-gray-300">
                                    <span class="text-xs text-gray-600">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Submit --}}
                    <footer class="flex items-center justify-end px-[18px] py-4 border-t border-gray-100">
                        <button type="submit"
                                class="flex items-center gap-1.5 rounded-lg bg-green-600 px-5 py-2.5 text-[13px] font-bold text-white transition-all hover:bg-green-700 active:scale-95 shadow-sm">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path d="M22 2 11 13"/>
                                <path d="M22 2 15 22 11 13 2 9l20-7z"/>
                            </svg>
                            Publish
                        </button>
                    </footer>
                </form>
            </div>
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
                    // Reset the actual input so the same file can be re-added
                    const input = document.getElementById('fileAttachInput');
                    if (input) input.value = '';
                },

                formatSize(bytes) {
                    return Math.max(1, Math.round(bytes / 1024)) + ' KB';
                }
            }
        }
    </script>
</x-app-layout>