<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PostController extends Controller
{
    use AuthorizesRequests;

    // Anyone can view the feed
    public function index()
    {
        $posts = Post::with([
            'user',
            'tags',
            'media',
            'likes',
        ])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(10);

        return view('feed.index', compact('posts'));
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

    public function create()
    {
        $tags = Tag::all();
        return view('feed.create', compact('tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'   => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'tags'    => 'nullable|array',
            'tags.*'  => 'exists:tags,id',
        ]);

        $post = $request->user()->posts()->create([
            'title'   => $validated['title'] ?? null,
            'content' => $validated['content'],
        ]);

        if (!empty($validated['tags'])) {
            $post->tags()->attach($validated['tags']);
        }

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post created successfully.');
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
            'title'   => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'tags'    => 'nullable|array',
            'tags.*'  => 'exists:tags,id',
        ]);

        $post->update([
            'title'   => $validated['title'] ?? null,
            'content' => $validated['content'],
        ]);

        $post->tags()->sync($validated['tags'] ?? []);

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
