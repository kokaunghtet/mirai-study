<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentLikeController extends Controller
{
    /**
     * Spam-click guard: skip a new like notification if one already fired
     * for this sender/recipient/comment within this window.
     */
    private const LIKE_NOTIFICATION_COOLDOWN_SECONDS = 30;

    public function toggle(Request $request, Comment $comment)
    {
        $user = $request->user();

        $liked = DB::transaction(function () use ($user, $comment) {
            $existing = DB::table('comment_likes')
                ->where('user_id', $user->id)
                ->where('comment_id', $comment->id)
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                $comment->likes()->where('user_id', $user->id)->delete();

                return false;
            }

            try {
                DB::transaction(fn () => $comment->likes()->create(['user_id' => $user->id]));
            } catch (QueryException $e) {
                // Lost the like race to a concurrent request (pivot PK rejected
                // the duplicate row) — treat as already liked, no re-notify.
                return true;
            }

            if ($comment->user_id !== $user->id) {
                $url = route('posts.show', $comment->post_id);
                $title = $user->display_name.' liked your comment';
                $content = str($comment->content)->limit(80);

                // 'like_post'+url alone also matches a like on the *post* itself
                // (same url), or on a *different comment* by the same author on
                // this post (same title+url) — title + content pins the dedupe
                // to this exact comment.
                $recentlyNotified = Notification::where('user_id', $comment->user_id)
                    ->where('sender_id', $user->id)
                    ->where('type', 'like_post')
                    ->where('title', $title)
                    ->where('content', $content)
                    ->where('url', $url)
                    ->where('created_at', '>=', now()->subSeconds(self::LIKE_NOTIFICATION_COOLDOWN_SECONDS))
                    ->exists();

                if (! $recentlyNotified) {
                    NotificationService::send(
                        recipient: $comment->user,
                        type: 'like_post',
                        title: $title,
                        content: $content,
                        sender: $user,
                        url: $url,
                    );
                }
            }

            return true;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'likes_count' => $comment->likes()->count(),
            ]);
        }

        return back();
    }
}
