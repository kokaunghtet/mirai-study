<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    public function toggle(Request $request, Post $post)
    {
        $user = $request->user();

        $existing = $post->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $post->likes()->where('user_id', $user->id)->delete();
            $liked = false;
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $liked = true;

            if ($post->user_id !== $user->id) {
                NotificationService::send(
                    recipient: $post->user,
                    type: 'like_post',
                    title: $user->display_name.' liked your post',
                    content: '"'.str($post->content)->limit(80).'"',
                    sender: $user,
                    url: route('posts.show', $post),
                );
            }
        }

        // Works for both regular and AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'likes_count' => $post->likes()->count(),
            ]);
        }

        return back();
    }
}
