<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CommentLikeController extends Controller
{
    public function toggle(Request $request, Comment $comment)
    {
        $user = $request->user();

        $existing = $comment->likes()->where('user_id', $user->id)->exists();

        if ($existing) {
            $comment->likes()->where('user_id', $user->id)->delete();
            $liked = false;
        } else {
            $comment->likes()->create(['user_id' => $user->id]);
            $liked = true;

            if ($comment->user_id !== $user->id) {
                NotificationService::send(
                    recipient: $comment->user,
                    type: 'like_post',
                    title: $user->display_name.' liked your comment',
                    content: str($comment->content)->limit(80),
                    sender: $user,
                    url: route('posts.show', $comment->post_id),
                );
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'likes_count' => $comment->likes()->count(),
            ]);
        }

        return back();
    }
}
