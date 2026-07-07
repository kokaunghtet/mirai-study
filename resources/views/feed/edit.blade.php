<x-app-layout>
    <x-slot name="title">Edit Post — MiraiStudy</x-slot>

    <div class="mx-auto max-w-[600px] px-4">
        <div class="rounded-2xl bg-surface border border-line shadow-sm overflow-hidden">

                {{-- Header --}}
                <header class="flex items-center justify-between px-5 py-4 border-b border-line">
                    <h2 class="text-[15px] font-bold text-content">Edit post</h2>
                    <a href="{{ route('posts.show', $post) }}"
                       class="grid h-8 w-8 place-items-center rounded-full text-muted hover:bg-surface-muted transition-colors">
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

                <form method="POST" action="{{ route('posts.update', $post) }}" data-loading
                      enctype="multipart/form-data"
                      x-data="editComposer()">
                    @csrf
                    @method('PATCH')

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

                    <section class="px-5 pb-3">

                        {{-- Text --}}
                        <textarea name="content" rows="4"
                                  placeholder="What's on your mind?"
                                  class="w-full resize-none rounded-xl bg-surface-muted px-3.5 py-3 text-sm leading-6 text-content border border-line outline-none placeholder:text-muted focus:border-accent transition-colors"
                                  style="min-height: 90px; overflow-y: hidden"
                                  x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                  x-init="$el.style.height = $el.scrollHeight + 'px'"
                                  :required="tab === 'text'">{{ old('content', $post->content) }}</textarea>
                        @error('content')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        {{-- Persistent inputs --}}
                        <input type="file" id="editMediaInput" name="media[]"
                               accept="image/*" multiple class="hidden"
                               @change="handleNewMedia($event)">

                        <input type="file" id="editFileInput" name="files[]"
                               accept="image/*,.pdf,.txt" multiple class="hidden"
                               @change="handleNewFiles($event)">

                        {{-- Media panel --}}
                        <div x-show="tab === 'media'" class="mt-2 space-y-3">

                            {{-- Existing media --}}
                            @php $existingMedia = $post->media->where('type', 'image'); @endphp
                            @if ($existingMedia->isNotEmpty())
                                <div>
                                    <p class="text-[11px] font-semibold text-muted mb-2 uppercase tracking-wide">
                                        Existing images — click to remove
                                    </p>
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach ($existingMedia as $item)
                                            <label class="relative cursor-pointer group">
                                                <input type="checkbox"
                                                       name="remove_media[]"
                                                       value="{{ $item->id }}"
                                                       class="peer hidden">
                                                <img src="{{ $item->url }}"
                                                     class="w-full aspect-square object-cover rounded-lg border-2 border-transparent peer-checked:border-red-500 peer-checked:opacity-50 transition-all">
                                                {{-- Remove overlay --}}
                                                <div class="absolute inset-0 rounded-lg peer-checked:bg-red-500/10 transition-all pointer-events-none"></div>
                                                <div class="absolute top-1 right-1 hidden peer-checked:flex items-center justify-center h-5 w-5 rounded-full bg-red-500">
                                                    <i data-lucide="x" class="h-3 w-3 text-white"></i>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- New media upload --}}
                            <div>
                                <div x-show="newMediaPreviews.length === 0">
                                    <label for="editMediaInput"
                                           class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-line px-4 py-4 text-center hover:bg-surface-muted transition-all">
                                        <i data-lucide="image" class="h-7 w-7 text-muted"></i>
                                        <p class="text-[13px] font-semibold text-content">Add more photos</p>
                                    </label>
                                </div>

                                <div x-show="newMediaPreviews.length > 0">
                                    <p class="text-[11px] font-semibold text-muted mb-2 uppercase tracking-wide">
                                        New images to add
                                    </p>
                                    <div class="relative aspect-video overflow-hidden rounded-xl bg-black">
                                        <template x-for="(src, i) in newMediaPreviews" :key="i">
                                            <img x-show="newMediaIdx === i"
                                                 :src="src"
                                                 class="h-full w-full object-cover absolute inset-0">
                                        </template>

                                        <div x-show="newMediaPreviews.length > 1"
                                             class="absolute right-2.5 top-2.5 rounded-md bg-black/70 px-2 py-1 text-[11px] font-semibold text-white"
                                             x-text="`${newMediaIdx + 1}/${newMediaPreviews.length}`">
                                        </div>

                                        <label for="editMediaInput"
                                               class="absolute bottom-2 right-2 cursor-pointer rounded-md bg-black/70 px-2.5 py-1 text-[11px] font-semibold text-white">
                                            Change
                                        </label>

                                        <button type="button"
                                                x-show="newMediaPreviews.length > 1"
                                                @click="newMediaIdx = Math.max(0, newMediaIdx - 1)"
                                                class="absolute left-2 top-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white text-xl">
                                            ‹
                                        </button>
                                        <button type="button"
                                                x-show="newMediaPreviews.length > 1"
                                                @click="newMediaIdx = Math.min(newMediaPreviews.length - 1, newMediaIdx + 1)"
                                                class="absolute right-2 top-1/2 -translate-y-1/2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white text-xl">
                                            ›
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- File panel --}}
                        <div x-show="tab === 'file'" class="mt-2 space-y-3">

                            {{-- Existing files --}}
                            @php $existingFiles = $post->media->where('type', 'document'); @endphp
                            @if ($existingFiles->isNotEmpty())
                                <div>
                                    <p class="text-[11px] font-semibold text-muted mb-2 uppercase tracking-wide">
                                        Existing files — click to remove
                                    </p>
                                    <div class="flex flex-col gap-1.5">
                                        @foreach ($existingFiles as $file)
                                            <label class="flex items-center gap-2.5 rounded-lg border border-line px-3 py-2.5 cursor-pointer hover:bg-surface-muted transition-colors peer-checked:border-red-300">
                                                <input type="checkbox"
                                                       name="remove_media[]"
                                                       value="{{ $file->id }}"
                                                       class="peer rounded text-red-500 border-line">
                                                <div class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-lg bg-accent/15 text-accent">
                                                    <i data-lucide="file" class="h-4 w-4"></i>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate text-xs font-semibold text-content peer-checked:line-through">
                                                        {{ $file->filename ?? basename($file->url) }}
                                                    </div>
                                                    @if ($file->filesize)
                                                        <div class="text-[11px] text-muted">
                                                            {{ max(1, round($file->filesize / 1024)) }} KB
                                                        </div>
                                                    @endif
                                                </div>
                                                <span class="text-[11px] text-red-400 font-semibold peer-checked:block hidden">Remove</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- New file upload --}}
                            <div>
                                <label for="editFileInput"
                                       class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border-2 border-dashed border-line px-4 py-4 text-center hover:bg-surface-muted transition-all">
                                    <i data-lucide="upload" class="h-7 w-7 text-muted"></i>
                                    <p class="text-[13px] font-semibold text-content">Attach new files</p>
                                </label>

                                <div class="mt-2.5 flex flex-col gap-1.5">
                                    <template x-for="(file, i) in newFiles" :key="i">
                                        <div class="flex items-center gap-2.5 rounded-lg border border-line bg-surface px-3 py-2.5">
                                            <div class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-lg bg-accent/15 text-accent">
                                                <i data-lucide="file" class="h-4 w-4"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-xs font-semibold text-content" x-text="file.name"></div>
                                                <div class="text-[11px] text-muted" x-text="formatSize(file.size)"></div>
                                            </div>
                                            <button type="button" @click="removeNewFile(i)"
                                                    class="text-muted hover:text-red-500 transition-colors">
                                                <i data-lucide="x" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Title --}}
                    <div class="px-5 pb-3">
                        <input type="text" name="title"
                               value="{{ old('title', $post->title) }}"
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
                                           {{ $post->tags->contains($tag->id) ? 'checked' : '' }}
                                           class="rounded text-accent border-line">
                                    <span class="text-xs text-muted">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Submit --}}
                    <footer class="flex items-center justify-end px-5 py-4 border-t border-line">
                        <button type="submit" data-loading-text="Saving…"
                                class="flex items-center gap-1.5 rounded-lg bg-gradient-to-tr from-accent-from to-accent-to px-5 py-2.5 text-[13px] font-bold text-white transition-all hover:opacity-90 active:scale-95 shadow-sm">
                            <i data-lucide="check" class="h-4 w-4"></i>
                            Save Changes
                        </button>
                    </footer>
                </form>
        </div>
    </div>

    <script>
        function editComposer() {
            return {
                tab: 'text',
                newMediaPreviews: [],
                newMediaIdx: 0,
                newFiles: [],

                setTab(t) { this.tab = t; },

                handleNewMedia(event) {
                    const files = Array.from(event.target.files).slice(0, 10);
                    this.newMediaPreviews.forEach(url => URL.revokeObjectURL(url));
                    this.newMediaPreviews = files.map(f => URL.createObjectURL(f));
                    this.newMediaIdx = 0;
                },

                handleNewFiles(event) {
                    this.newFiles = Array.from(event.target.files);
                },

                removeNewFile(index) {
                    this.newFiles.splice(index, 1);
                    const input = document.getElementById('editFileInput');
                    if (input) {
                        const dt = new DataTransfer();
                        this.newFiles.forEach(f => dt.items.add(f));
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