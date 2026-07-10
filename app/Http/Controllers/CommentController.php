<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Post;
use App\Rules\NoProfanity;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

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
            'content' => ['required', 'string', 'max:2000', new NoProfanity],
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $commenter = $request->user();

        $comment = $post->comments()->create([
            'user_id' => $commenter->id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Notify post owner
        if ($post->user_id !== $commenter->id) {
            NotificationService::send(
                recipient: $post->user,
                type: 'comment_post',
                title: $commenter->display_name.' commented on your post',
                content: str($validated['content'])->limit(100),
                sender: $commenter,
                url: route('posts.show', $post),
            );
        }

        // Notify parent comment owner on reply (if different from post owner)
        if ($comment->parent_id) {
            $parent = Comment::find($comment->parent_id);
            if ($parent && $parent->user_id !== $commenter->id && $parent->user_id !== $post->user_id) {
                NotificationService::send(
                    recipient: $parent->user,
                    type: 'comment_post',
                    title: $commenter->display_name.' replied to your comment',
                    content: str($validated['content'])->limit(100),
                    sender: $commenter,
                    url: route('posts.show', $post),
                );
            }
        }

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
            'content' => ['required', 'string', 'max:2000', new NoProfanity],
        ]);

        $comment->update($validated);

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        $this->authorize('delete', $comment);

        $actorId = auth()->id();
        $isStaff = $actorId !== $comment->user_id;
        $post = $comment->post;

        $comment->delete();

        if ($isStaff) {
            ActivityLog::create([
                'user_id' => $actorId,
                'action' => 'content_removed',
                'subject_type' => 'Comment',
                'subject_id' => $comment->id,
                'properties' => [
                    'username' => $comment->user?->username,
                    'reason' => 'Direct removal by staff',
                ],
                'created_at' => now(),
            ]);
        }

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
            'comments' => fn ($q) => $q->whereNull('parent_id')
                ->with(['user', 'replies' => fn ($r) => $r->with(['user', 'likes']), 'likes'])
                ->latest(),
        ]);

        $post->loadCount('comments');
    }
}
