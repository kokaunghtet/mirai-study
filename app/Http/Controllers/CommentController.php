<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CommentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Render just the comments section for a post.
     * Used by the feed comment drawer (loaded via AJAX).
     */
    public function index(Request $request, Post $post)
    {
        $this->loadComments($post);

        return view('feed._comments', compact('post'));
    }

    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content'   => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $post->comments()->create([
            'user_id'   => $request->user()->id,
            'content'   => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // AJAX (feed drawer): return the refreshed comments partial.
        if ($request->expectsJson() || $request->ajax()) {
            $this->loadComments($post);
            return view('feed._comments', compact('post'));
        }

        return back()->with('success', 'Comment posted.');
    }

    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $comment->update($validated);

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        $this->authorize('delete', $comment);

        $post = $comment->post;
        $comment->delete();

        // AJAX (feed drawer): return the refreshed comments partial.
        if ($request->expectsJson() || $request->ajax()) {
            $this->loadComments($post);
            return view('feed._comments', compact('post'));
        }

        return back()->with('success', 'Comment deleted.');
    }

    /**
     * Eager-load the data the comments partial needs and the
     * comment count it displays. Mirrors PostController@show.
     */
    private function loadComments(Post $post): void
    {
        $post->load([
            'comments' => fn($q) => $q->whereNull('parent_id')
                ->with(['user', 'replies.user', 'likes'])
                ->latest(),
        ]);

        $post->loadCount('comments');
    }
}