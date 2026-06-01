<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    public function toggle(Request $request, Post $post)
    {
        $user = $request->user();

        $existing = $post->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            // $post->delete();
            $post->likes()->where('user_id', $user->id)->delete();
            $liked = false;
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $liked = true;
        }

        // Works for both regular and AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'liked'      => $liked,
                'likes_count' => $post->likes()->count(),
            ]);
        }

        return back();
    }
}
