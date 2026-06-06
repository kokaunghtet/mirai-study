@foreach ($posts as $post)
    <x-post-card :post="$post" />
@endforeach