<?php


namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    use AuthorizesRequests;

    // Anyone can view the feed
    public function index(Request $request)
    {
        $with = ['user', 'tags', 'media'];

        if (auth()->check()) {
            $userId = auth()->id();
            $with['bookmarks'] = fn($q) => $q->where('user_id', $userId);
            $with['likes']     = fn($q) => $q->where('user_id', $userId);
        }

        $posts = Post::with($with)
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'html'          => view('feed._posts', compact('posts'))->render(),
                'next_page_url' => $posts->nextPageUrl(),
            ]);
        }

        return view('feed.index', compact('posts'));
    }

    public function create()
    {
        $tags = Tag::all();
        return view('feed.create', compact('tags'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'     => 'nullable|string|max:255',
            'content'   => 'required|string|max:5000',
            'tags'      => 'nullable|array',
            'tags.*'    => 'exists:tags,id',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'files.*'   => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
        ]);
        
        $post = $request->user()->posts()->create([
            'title'   => $validated['title'] ?? null,
            'content' => $validated['content'],
        ]);
        
        if (!empty($validated['tags'])) {
            $post->tags()->attach($validated['tags']);
        }
        
        // Handle media uploads (images/videos)
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store('posts/media', 'public');
                
                $post->media()->create([
                    'url'  => Storage::url($path),
                    'type' => 'image',
                ]);
            }
        }
        
        // Handle document/file uploads
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('posts/files', 'public');
                
                $post->media()->create([
                    'url'      => Storage::url($path),
                    'type'     => 'document',
                    'filename' => $file->getClientOriginalName(),
                    'filesize' => $file->getSize(),
                ]);
            }
        }
        
        return redirect()->route('posts.show', $post)
        ->with('success', 'Post created successfully.');
    }
    
    // Anyone can view a single post
    public function show(Post $post)
    {
        $post->load([
            'user',
            'tags',
            'media',
            'likes',
            'comments' => fn($q) => $q->whereNull('parent_id')
                ->with(['user', 'replies.user', 'likes'])
                ->latest(),
        ]);

        $post->loadCount(['likes', 'comments']);

        return view('feed.show', compact('post'));
    }

    public function edit(Post $post)
    {
        // Only the post owner can edit
        $this->authorize('update', $post);

        $tags = Tag::all();
        $post->load('tags');

        return view('feed.edit', compact('post', 'tags'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title'           => 'nullable|string|max:255',
            'content'         => 'required|string|max:5000',
            'tags'            => 'nullable|array',
            'tags.*'          => 'exists:tags,id',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'files.*'         => 'nullable|file|max:20480',
            'remove_media'    => 'nullable|array',
            'remove_media.*'  => 'exists:post_media,id',
        ]);

        $post->update([
            'title'   => $validated['title'] ?? null,
            'content' => $validated['content'],
        ]);

        $post->tags()->sync($validated['tags'] ?? []);

        // Remove media the user deleted
        if (!empty($validated['remove_media'])) {
            $post->media()->whereIn('id', $validated['remove_media'])->delete();
        }

        // Add new media
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store('posts/media', 'public');

                $post->media()->create([
                    'url'  => Storage::url($path),
                    'type' => 'image',
                ]);
            }
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('posts/files', 'public');

                $post->media()->create([
                    'url'      => Storage::url($path),
                    'type'     => 'document',
                    'filename' => $file->getClientOriginalName(),
                    'filesize' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete(); // soft delete

        return redirect()->route('feed.index')
            ->with('success', 'Post deleted.');
    }
}
