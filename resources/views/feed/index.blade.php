<x-app-layout>
    <x-slot name="title">Feed — MiraiStudy</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Feed --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Create Post Button --}}
            @auth
                <a href="{{ route('posts.create') }}"
                   class="block w-full text-center bg-green-600 text-white font-medium py-3 rounded-xl hover:bg-green-700 transition">
                    + Create Post
                </a>
            @endauth

            {{-- Posts --}}
            @forelse ($posts as $post)
                <x-post-card :post="$post" />
            @empty
                <div class="text-center py-16 text-gray-400">
                    <p class="text-lg">No posts yet.</p>
                    <p class="text-sm mt-1">Be the first to share something!</p>
                </div>
            @endforelse

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $posts->links() }}
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4">
            @guest
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-900 mb-2">Join MiraiStudy</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Connect with learners, share knowledge, and track your study progress.
                    </p>
                    <a href="{{ route('register') }}"
                       class="block w-full text-center bg-green-600 text-white text-sm font-medium py-2.5 rounded-lg hover:bg-green-700">
                        Create Account
                    </a>
                </div>
            @endguest

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Quick Links</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="{{ route('exams.index') }}" class="hover:text-green-600">📄 Exam Papers</a></li>
                    <li><a href="{{ route('quiz.index') }}" class="hover:text-green-600">📝 Take a Quiz</a></li>
                    <li><a href="{{ route('timer.index') }}" class="hover:text-green-600">⏱ Focus Timer</a></li>
                </ul>
            </div>
        </aside>

    </div>
</x-app-layout>