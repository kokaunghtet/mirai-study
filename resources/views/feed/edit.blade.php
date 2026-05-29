<x-app-layout>
    <x-slot name="title">Edit Post — MiraiStudy</x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h1 class="text-xl font-bold text-gray-900 mb-6">Edit Post</h1>

            <form method="POST" action="{{ route('posts.update', $post) }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Title <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input type="text" name="title"
                           value="{{ old('title', $post->title) }}"
                           placeholder="Give your post a title..."
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-300">
                    @error('title')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Content <span class="text-red-500">*</span>
                    </label>
                    <textarea name="content" rows="6"
                              class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-green-300"
                              required>{{ old('content', $post->content) }}</textarea>
                    @error('content')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tags as $tag)
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                       {{ $post->tags->contains($tag->id) ? 'checked' : '' }}
                                       class="rounded text-green-600">
                                <span class="text-sm text-gray-700">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="bg-green-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-green-700 transition">
                        Save Changes
                    </button>
                    <a href="{{ route('posts.show', $post) }}"
                       class="text-sm text-gray-500 hover:text-gray-700">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>