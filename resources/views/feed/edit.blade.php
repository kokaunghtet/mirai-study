<x-app-layout>
    <x-slot name="title">Edit Post — MiraiStudy</x-slot>

    <div class="flex justify-center px-4 py-6">
        <div class="w-full max-w-[560px]">
            <div class="rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden">

                {{-- Header --}}
                <header class="flex items-center justify-between px-[18px] py-4 border-b border-gray-100">
                    <h2 class="text-[15px] font-bold text-gray-900">Edit post</h2>
                    <a href="{{ route('posts.show', $post) }}"
                       class="grid h-8 w-8 place-items-center rounded-full text-gray-400 hover:bg-gray-100 transition-colors">
                        <i data-lucide="x" class="h-4 w-4"></i>
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

                <form method="POST" action="{{ route('posts.update', $post) }}"
                      enctype="multipart/form-data"
                      x-data="editComposer()">
                    @csrf
                    @method('PATCH')

                    {{-- Type Tabs --}}
                    <nav class="flex gap-1 px-[18px] pb-3">
                        <button type="button" @click="setTab('text')"
                                :class="tab === 'text'
                                    ? 'bg-white text-gray-900 font-bold border-gray-200 shadow-sm'
                                    : 'bg-transparent text-gray-400 border-transparent hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border px-1 py-2 text-xs transition-colors">
                            <i data-lucide="align-left" class="h-3.5 w-3.5"></i>
                            Text
                        </button>
                        <button type="button" @click="setTab('media')"
                                :class="tab === 'media'
                                    ? 'bg-white text-gray-900 font-bold border-gray-200 shadow-sm'
                                    : 'bg-transparent text-gray-400 border-transparent hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border px-1 py-2 text-xs transition-colors">
                            <i data-lucide="image" class="h-3.5 w-3.5"></i>
                            Media
                        </button>
                        <button type="button" @click="setTab('file')"
                                :class="tab === 'file'
                                    ? 'bg-white text-gray-900 font-bold border-gray-200 shadow-sm'
                                    : 'bg-transparent text-gray-400 border-transparent hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border px-1 py-2 text-xs transition-colors">
                            <i data-lucide="file" class="h-3.5 w-3.5"></i>
                            File
                        </button>
                    </nav>

                    <section class="px-[18px] pb-2.5">

                        {{-- Text --}}
                        <textarea name="content" rows="4"
                                  placeholder="What's on your mind?"
                                  class="min-h-[90px] w-full resize-none rounded-xl bg-gray-50 px-3.5 py-3 text-sm leading-6 text-gray-900 border border-gray-200 outline-none placeholder:text-gray-400 focus:border-green-400 transition-colors"
                                  required>{{ old('content', $post->content) }}</textarea>
                        @error('content')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        {{-- Persistent inputs --}}
                        <input type="file" id="editMediaInput" name="media[]"
                               accept="image/*" multiple class="hidden"
                               @change="handleNewMedia($event)">

                        <input type="file" id="editFileInput" name="files[]"
                               multiple class="hidden"
                               @change="handleNewFiles($event)">

                        {{-- Media panel --}}
                        <div x-show="tab === 'media'" class="mt-2 space-y-3">

                            {{-- Existing media --}}
                            @php $existingMedia = $post->media->where('type', 'image'); @endphp
                            @if ($existingMedia->isNotEmpty())
                                <div>
                                    <p class="text-[11px] font-semibold text-gray-400 mb-2 uppercase tracking-wide">
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
                                           class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-gray-300 px-4 py-4 text-center hover:bg-gray-50 transition-all">
                                        <i data-lucide="image" class="h-7 w-7 text-gray-400"></i>
                                        <p class="text-[13px] font-semibold text-gray-700">Add more photos</p>
                                    </label>
                                </div>

                                <div x-show="newMediaPreviews.length > 0">
                                    <p class="text-[11px] font-semibold text-gray-400 mb-2 uppercase tracking-wide">
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
                                    <p class="text-[11px] font-semibold text-gray-400 mb-2 uppercase tracking-wide">
                                        Existing files — click to remove
                                    </p>
                                    <div class="flex flex-col gap-1.5">
                                        @foreach ($existingFiles as $file)
                                            <label class="flex items-center gap-2.5 rounded-lg border border-gray-200 px-3 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors peer-checked:border-red-300">
                                                <input type="checkbox"
                                                       name="remove_media[]"
                                                       value="{{ $file->id }}"
                                                       class="peer rounded text-red-500 border-gray-300">
                                                <div class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-lg bg-green-100 text-green-600">
                                                    <i data-lucide="file" class="h-4 w-4"></i>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate text-xs font-semibold text-gray-900 peer-checked:line-through">
                                                        {{ $file->filename ?? basename($file->url) }}
                                                    </div>
                                                    @if ($file->filesize)
                                                        <div class="text-[11px] text-gray-400">
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
                                       class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border-2 border-dashed border-gray-300 px-4 py-4 text-center hover:bg-gray-50 transition-all">
                                    <i data-lucide="upload" class="h-7 w-7 text-gray-400"></i>
                                    <p class="text-[13px] font-semibold text-gray-700">Attach new files</p>
                                </label>

                                <div class="mt-2.5 flex flex-col gap-1.5">
                                    <template x-for="(file, i) in newFiles" :key="i">
                                        <div class="flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5">
                                            <div class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-lg bg-green-100 text-green-600">
                                                <i data-lucide="file" class="h-4 w-4"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-xs font-semibold text-gray-900" x-text="file.name"></div>
                                                <div class="text-[11px] text-gray-400" x-text="formatSize(file.size)"></div>
                                            </div>
                                            <button type="button" @click="newFiles.splice(i, 1)"
                                                    class="text-gray-400 hover:text-red-500 transition-colors">
                                                <i data-lucide="x" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Title --}}
                    <div class="px-[18px] pb-3">
                        <input type="text" name="title"
                               value="{{ old('title', $post->title) }}"
                               placeholder="Add a title (optional)"
                               class="w-full bg-transparent text-sm text-gray-600 outline-none placeholder:text-gray-300 border-b border-gray-100 pb-1 focus:border-green-300 transition-colors">
                    </div>

                    {{-- Tags --}}
                    <div class="px-[18px] pb-3">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                           {{ $post->tags->contains($tag->id) ? 'checked' : '' }}
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
                            <i data-lucide="check" class="h-4 w-4"></i>
                            Save Changes
                        </button>
                    </footer>
                </form>
            </div>
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

                formatSize(bytes) {
                    return Math.max(1, Math.round(bytes / 1024)) + ' KB';
                }
            }
        }
    </script>
</x-app-layout>