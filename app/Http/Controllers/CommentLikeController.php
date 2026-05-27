<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentLikeController extends Controller
{
    public function toggle(Request $request, Comment $comment)
    {
        $user = $request->user();

        $existing = $comment->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $comment->likes()->create(['user_id' => $user->id]);
            $liked = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'liked'       => $liked,
                'likes_count' => $comment->likes()->count(),
            ]);
        }

        return back();
    }
}