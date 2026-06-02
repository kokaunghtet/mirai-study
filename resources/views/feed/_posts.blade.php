{{-- _posts.blade.php — only post cards, no empty state, no self-include --}}
@foreach ($posts as $post)
    <x-post-card :post="$post" />
@endforeach
