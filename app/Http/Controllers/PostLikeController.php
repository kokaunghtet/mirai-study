<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Post;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostLikeController extends Controller
{
    /**
     * Spam-click guard: skip a new like notification if one already fired
     * for this sender/recipient/post within this window.
     */
    private const LIKE_NOTIFICATION_COOLDOWN_SECONDS = 30;

    public function toggle(Request $request, Post $post)
    {
        $user = $request->user();

        $liked = DB::transaction(function () use ($user, $post) {
            $existing = DB::table('post_likes')
                ->where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                $post->likes()->where('user_id', $user->id)->delete();

                return false;
            }

            try {
                DB::transaction(fn () => $post->likes()->create(['user_id' => $user->id]));
            } catch (QueryException $e) {
                // Lost the like race to a concurrent request (pivot PK rejected
                // the duplicate row) — treat as already liked, no re-notify.
                return true;
            }

            if ($post->user_id !== $user->id) {
                $title = $user->display_name.' liked your post';

                // 'like_post'+url alone would also match a like on this post's
                // *comments* (same url) — title pins the dedupe to this exact
                // post-like event, not the comment-like one.
                $recentlyNotified = Notification::where('user_id', $post->user_id)
                    ->where('sender_id', $user->id)
                    ->where('type', 'like_post')
                    ->where('title', $title)
                    ->where('url', route('posts.show', $post))
                    ->where('created_at', '>=', now()->subSeconds(self::LIKE_NOTIFICATION_COOLDOWN_SECONDS))
                    ->exists();

                if (! $recentlyNotified) {
                    NotificationService::send(
                        recipient: $post->user,
                        type: 'like_post',
                        title: $title,
                        content: '"'.str($post->content)->limit(80).'"',
                        sender: $user,
                        url: route('posts.show', $post),
                    );
                }
            }

            return true;
        });

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
