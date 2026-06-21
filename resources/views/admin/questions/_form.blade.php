<form method="POST" action="{{ $action }}"
      x-data="questionForm(@js($categories))"
      data-old-category="{{ old('category_id', $question?->category_id) }}"
      data-old-level="{{ old('level_id', $question?->level_id) }}"
      data-old-section="{{ old('section', $question?->section) }}"
      data-old-answer="{{ old('answer', $question?->answer) }}"
      class="space-y-5">
    @csrf
    @if (($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    {{-- ── Taxonomy cascade ─────────────────────────────────────── --}}
    <div class="space-y-5 rounded-2xl border border-line bg-surface p-5">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- Category --}}
            <div>
                <x-input-label for="category_id" value="Category" />
                <select name="category_id" id="category_id" x-model="categoryId" @change="onCategoryChange"
                        class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent">
                    <option value="">Select a category…</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
            </div>

            {{-- Level --}}
            <div>
                <x-input-label for="level_id" value="Level" />
                <select name="level_id" id="level_id" x-model="levelId" :disabled="!categoryId" @change="onLevelChange"
                        class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent disabled:opacity-50">
                    <option value="">Select a level…</option>
                    <template x-for="lvl in levels" :key="lvl.id">
                        <option :value="lvl.id" x-text="lvl.name"></option>
                    </template>
                </select>
                <x-input-error :messages="$errors->get('level_id')" class="mt-2" />
            </div>
        </div>

        {{-- Section (hidden for level-only pools like IP) --}}
        <div x-show="needsSection" x-cloak>
            <x-input-label for="section" value="Section" />
            <select name="section" id="section" x-model="section"
                    class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent">
                <option value="">Select a section…</option>
                <template x-for="[code, label] in Object.entries(sections)" :key="code">
                    <option :value="code" x-text="label"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('section')" class="mt-2" />
        </div>
    </div>

    {{-- ── Question body ─────────────────────────────────────────── --}}
    <div class="space-y-5 rounded-2xl border border-line bg-surface p-5">
        <div>
            <x-input-label for="text" value="Question" />
            <textarea name="text" id="text" rows="4"
                      class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent"
                      placeholder="Enter the question text…">{{ old('text', $question?->text) }}</textarea>
            <x-input-error :messages="$errors->get('text')" class="mt-2" />
        </div>

        {{-- Options A–D with inline answer radio --}}
        <div class="space-y-4">
            <x-input-label value="Options" />
            @foreach (['A', 'B', 'C', 'D'] as $letter)
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-sm font-medium text-content shrink-0 w-8">
                        <input type="radio" name="answer" value="{{ $letter }}"
                               x-model="answer"
                               @checked(old('answer', $question?->answer) === $letter)
                               class="text-accent focus:ring-accent" />
                        <span>{{ $letter }}</span>
                    </label>
                    <input type="text" name="option_{{ strtolower($letter) }}"
                           value="{{ old('option_' . strtolower($letter), $question?->{'option_' . strtolower($letter)}) }}"
                           placeholder="Option {{ $letter }}"
                           class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent"
                           required />
                </div>
                <x-input-error :messages="$errors->get('option_' . strtolower($letter))" class="mt-2" />
            @endforeach
            <x-input-error :messages="$errors->get('answer')" class="mt-2" />
        </div>

        {{-- Explanation --}}
        <div>
            <x-input-label for="explanation" value="Explanation (optional)" />
            <textarea name="explanation" id="explanation" rows="3"
                      class="mt-1 block w-full rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent"
                      placeholder="Explain why the correct answer is right…">{{ old('explanation', $question?->explanation) }}</textarea>
            <x-input-error :messages="$errors->get('explanation')" class="mt-2" />
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.questions') }}"
           class="inline-flex items-center rounded-xl border border-line bg-surface px-4 py-2 text-sm font-semibold text-content transition-colors hover:bg-surface-muted">
            Cancel
        </a>
        <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-xl bg-accent px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-accent-strong">
            <i data-lucide="plus" class="h-4 w-4"></i>
            {{ $submitLabel ?? 'Add Question' }}
        </button>
    </div>
</form>
