<x-app-layout>
    <x-slot name="title">Edit Paper — MiraiStudy</x-slot>

    <div class="px-4">
        <div class="mx-auto max-w-2xl">

            {{-- Header --}}
            <header class="mb-6 flex items-center gap-3">
                <a href="{{ route('admin.papers') }}" title="Back"
                   class="inline-flex items-center justify-center rounded-xl border border-line bg-surface p-2 text-muted transition-colors hover:text-content">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Edit Paper</h1>
                    <p class="mt-1 text-sm text-muted">Update the paper's metadata. The PDF cannot be changed here.</p>
                </div>
            </header>

            {{-- Current file (read-only indicator) --}}
            <div class="mb-5 flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-accent/10 text-accent">
                    <i data-lucide="file-text" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-content">{{ $paper->title }}</div>
                    <div class="mt-0.5 text-xs text-muted">PDF · <a href="{{ route('exams.view', $paper) }}" target="_blank" class="text-accent hover:underline">View file</a></div>
                </div>
                <span class="shrink-0 rounded-full border border-line px-2.5 py-1 text-xs font-medium text-muted">PDF locked</span>
            </div>

            <form method="POST" action="{{ route('admin.papers.update', $paper) }}"
                  x-data="paperUploader(@js($categories))"
                  data-old-category="{{ old('category_id', $paper->category_id) }}"
                  data-old-level="{{ old('level_id', $paper->level_id) }}"
                  data-old-year="{{ old('year', $paper->year) }}"
                  data-old-session="{{ old('session', $paper->session) }}"
                  data-old-part="{{ old('part', $paper->part) }}"
                  data-old-doctype="{{ old('doc_type', $paper->doc_type) }}"
                  data-old-title="{{ old('title', $paper->title) }}"
                  class="space-y-5">
                @csrf
                @method('PUT')

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
                                <template x-for="opt in sessionOptions" :key="opt.v">
                                    <option :value="opt.v" x-text="opt.l"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('session')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Part + Doc type --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="part" value="Sitting (optional)" />
                            <select name="part" id="part" x-model="part"
                                    :disabled="isJlpt"
                                    :class="isJlpt ? 'opacity-50 cursor-not-allowed' : ''"
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
                        <x-input-label for="title" value="Title" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                      x-model="title" placeholder="e.g. FE — April 2020 · AM · Questions" />
                        <p class="mt-1 text-xs text-muted">Leave blank to keep the current title.</p>
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-input-label for="description" value="Description (optional)" />
                        <textarea id="description" name="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent"
                                  placeholder="Notes about this paper…">{{ old('description', $paper->description) }}</textarea>
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
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Save changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
