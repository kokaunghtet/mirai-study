<x-app-layout>
    <x-slot name="title">Upload Paper — MiraiStudy</x-slot>

    <div class="px-4">
        <div class="mx-auto max-w-2xl">

            {{-- Header --}}
            <header class="mb-6 flex items-center gap-3">
                <a href="{{ route('admin.papers') }}" title="Back"
                   class="inline-flex items-center justify-center rounded-xl border border-line bg-surface p-2 text-muted transition-colors hover:text-content">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Upload Paper</h1>
                    <p class="mt-1 text-sm text-muted">Drop a PDF — we read the year, session, level &amp; type from its name.</p>
                </div>
            </header>

            <form method="POST" action="{{ route('admin.papers.store') }}" enctype="multipart/form-data"
                  x-data="paperUploader(@js($categories))"
                  data-old-category="{{ old('category_id') }}"
                  data-old-level="{{ old('level_id') }}"
                  data-old-year="{{ old('year') }}"
                  data-old-session="{{ old('session') }}"
                  data-old-part="{{ old('part') }}"
                  data-old-doctype="{{ old('doc_type') }}"
                  data-old-title="{{ old('title') }}"
                  class="space-y-5">
                @csrf

                {{-- ── Dropzone (drives every field) ─────────────────────────── --}}
                <div>
                    <label for="file"
                           @dragover.prevent="isDragging = true"
                           @dragleave.prevent="isDragging = false"
                           @drop.prevent="dropFile($event)"
                           :class="isDragging ? 'border-accent bg-accent/10' : 'border-line hover:bg-surface-muted'"
                           class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed bg-surface px-4 py-10 text-center transition-colors">

                        {{-- Empty state --}}
                        <template x-if="!fileName">
                            <div class="flex flex-col items-center gap-2">
                                <span class="grid h-12 w-12 place-items-center rounded-full bg-accent/10 text-accent">
                                    <i data-lucide="upload" class="h-6 w-6"></i>
                                </span>
                                <p class="text-sm font-semibold text-content">Drop a PDF here, or click to browse</p>
                                <p class="text-xs text-muted">e.g. <span class="font-mono">2026S_FE_AM_Question.pdf</span> · max 20&nbsp;MB</p>
                            </div>
                        </template>

                        {{-- Chosen file --}}
                        <template x-if="fileName">
                            <div class="flex w-full items-center gap-3 text-left">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-accent/10 text-accent">
                                    <i data-lucide="file-text" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-content" x-text="fileName"></div>
                                    <div class="text-xs text-muted" x-text="formatSize(fileSize)"></div>
                                </div>
                                <button type="button" @click.prevent="clearFile()" title="Remove"
                                        class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-line text-muted transition-colors hover:text-content">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </template>
                    </label>

                    <input id="file" name="file" type="file" accept="application/pdf" required
                           class="hidden" @change="pickFile($event)" />
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                {{-- ── Parsed preview ────────────────────────────────────────── --}}
                <div x-show="parsed" x-cloak>
                    {{-- Matched --}}
                    <div x-show="parseOk"
                         class="rounded-2xl border border-accent/40 bg-accent/5 p-4">
                        <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-content">
                            <i data-lucide="sparkles" class="h-4 w-4 text-accent"></i>
                            Auto-filled from filename
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="rounded-lg border border-line bg-surface px-2.5 py-1 text-content">
                                <span class="text-muted">Year</span> <span class="font-semibold" x-text="year"></span>
                            </span>
                            <span class="rounded-lg border border-line bg-surface px-2.5 py-1 text-content">
                                <span class="text-muted">Session</span> <span class="font-semibold" x-text="session"></span>
                            </span>
                            <span class="rounded-lg border border-line bg-surface px-2.5 py-1 text-content">
                                <span class="text-muted">Level</span> <span class="font-semibold" x-text="levelLabel"></span>
                            </span>
                            <span class="rounded-lg border border-line bg-surface px-2.5 py-1 text-content">
                                <span class="text-muted">Sitting</span> <span class="font-semibold" x-text="part"></span>
                            </span>
                            <span class="rounded-lg border border-line bg-surface px-2.5 py-1 text-content">
                                <span class="text-muted">Type</span> <span class="font-semibold" x-text="docLabel"></span>
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-muted">Everything below is editable — fix anything that looks off.</p>
                    </div>

                    {{-- No match --}}
                    <div x-show="!parseOk"
                         class="flex items-start gap-2.5 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-amber-700 dark:text-amber-300">
                        <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <p class="text-xs">Couldn't read this filename. Expected
                            <span class="font-mono">20NNS_FE_AM_Question.pdf</span>. Fill the fields below manually.</p>
                    </div>
                </div>

                {{-- ── Editable fields ───────────────────────────────────────── --}}
                <div class="space-y-5 rounded-2xl border border-line bg-surface p-5">

                    {{-- Category + Level --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="category_id" value="Category" />
                            <select name="category_id" id="category_id" x-model="categoryId"
                                    class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent">
                                <option value="">Select a category…</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="level_id" value="Level" />
                            <select name="level_id" id="level_id" x-model="levelId" :disabled="!categoryId"
                                    class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent disabled:opacity-50">
                                <option value="">Select a level…</option>
                                <template x-for="lvl in levels" :key="lvl.id">
                                    <option :value="lvl.id" x-text="lvl.name"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('level_id')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Year + Session --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="year" value="Year" />
                            <x-text-input id="year" name="year" type="number" min="1990" max="{{ now()->year + 1 }}"
                                          class="mt-1 block w-full" x-model="year" required />
                            <x-input-error :messages="$errors->get('year')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="session" value="Session (optional)" />
                            <select name="session" id="session" x-model="session"
                                    class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent">
                                <option value="">—</option>
                                <option value="April">April</option>
                                <option value="October">October</option>
                                <option value="December">December</option>
                                <option value="July">July</option>
                            </select>
                            <x-input-error :messages="$errors->get('session')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Part + Doc type --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="part" value="Sitting (optional)" />
                            <select name="part" id="part" x-model="part"
                                    class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent">
                                <option value="">—</option>
                                <option value="AM">AM (morning)</option>
                                <option value="PM">PM (afternoon)</option>
                            </select>
                            <x-input-error :messages="$errors->get('part')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="doc_type" value="Type (optional)" />
                            <select name="doc_type" id="doc_type" x-model="docType"
                                    class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent">
                                <option value="">—</option>
                                <option value="question">Question paper</option>
                                <option value="answer">Answer key</option>
                                <option value="combined">Questions + answer key</option>
                            </select>
                            <x-input-error :messages="$errors->get('doc_type')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Title --}}
                    <div>
                        <x-input-label for="title" value="Title (optional)" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                      x-model="title" placeholder="e.g. FE — April 2020 · AM · Questions" />
                        <p class="mt-1 text-xs text-muted">Auto-built from the filename; edit if needed. Blank falls back to the file name.</p>
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-input-label for="description" value="Description (optional)" />
                        <textarea id="description" name="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent"
                                  placeholder="Notes about this paper…">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.papers') }}"
                       class="inline-flex items-center rounded-xl border border-line bg-surface px-4 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-accent px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-accent-strong">
                        <i data-lucide="upload" class="h-4 w-4"></i>
                        Upload
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
