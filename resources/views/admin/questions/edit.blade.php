<x-app-layout>
    <x-slot name="title">Edit Question — MiraiStudy</x-slot>

    <div class="px-4">
        <div class="mx-auto max-w-2xl">

            {{-- Header --}}
            <header class="mb-6 flex items-center gap-3">
                <a href="{{ route('admin.questions') }}" title="Back"
                   class="inline-flex items-center justify-center rounded-xl border border-line bg-surface p-2 text-muted transition-colors hover:text-content">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-content">Edit Question</h1>
                    <p class="mt-1 text-sm text-muted">Update the question details.</p>
                </div>
            </header>

            @include('admin.questions._form', [
                'action' => route('admin.questions.update', $question),
                'method' => 'PUT',
                'question' => $question,
                'submitLabel' => 'Save changes',
            ])

        </div>
    </div>
</x-app-layout>
