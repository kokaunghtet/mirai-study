<div x-data="{ open: false }"
     x-on:open-auth-modal.window="open = true">

    <div x-show="open"
         x-transition
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
         @click.self="open = false">

        <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-md mx-4">
            <h2 class="text-xl font-bold text-gray-900 mb-2">Join MiraiStudy</h2>
            <p class="text-gray-500 text-sm mb-6">
                Create a free account to like, comment, bookmark posts and more.
            </p>

            <div class="flex flex-col gap-3">
                <a href="{{ route('register') }}"
                   class="w-full text-center bg-green-600 text-white font-medium py-2.5 rounded-lg hover:bg-green-700">
                    Create Account
                </a>
                <a href="{{ route('login') }}"
                   class="w-full text-center border border-gray-300 text-gray-700 font-medium py-2.5 rounded-lg hover:bg-gray-50">
                    Log In
                </a>
            </div>

            <button @click="open = false"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                ✕
            </button>
        </div>
    </div>
</div>