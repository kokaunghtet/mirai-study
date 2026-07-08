<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
    /**
     * Spam-click guard: skip a new follow notification if one already fired
     * for this sender/recipient pair within this window.
     */
    private const FOLLOW_NOTIFICATION_COOLDOWN_SECONDS = 30;

    public function toggle(Request $request, User $user)
    {
        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            return response()->json(['error' => 'You cannot follow yourself.'], 422);
        }

        $following = DB::transaction(function () use ($authUser, $user) {
            $isFollowing = DB::table('follows')
                ->where('follower_id', $authUser->id)
                ->where('following_id', $user->id)
                ->lockForUpdate()
                ->exists();

            if ($isFollowing) {
                $authUser->following()->detach($user->id);

                return false;
            }

            try {
                DB::transaction(fn () => $authUser->following()->attach($user->id, ['status' => 'accepted']));
            } catch (QueryException $e) {
                // Lost the attach race to a concurrent request (pivot PK rejected
                // the duplicate row) — treat as already followed, no re-notify.
                return true;
            }

            $recentlyNotified = Notification::where('user_id', $user->id)
                ->where('sender_id', $authUser->id)
                ->where('type', 'follow_user')
                ->where('created_at', '>=', now()->subSeconds(self::FOLLOW_NOTIFICATION_COOLDOWN_SECONDS))
                ->exists();

            if (! $recentlyNotified) {
                NotificationService::send(
                    recipient: $user,
                    type: 'follow_user',
                    title: $authUser->display_name.' started following you',
                    content: '@'.$authUser->username.' is now following you.',
                    sender: $authUser,
                    url: route('profile.show', $authUser->username),
                );
            }

            return true;
        });

        return response()->json(['following' => $following]);
    }

    /**
     * Remove a user from the authenticated user's followers list.
     */
    public function removeFollower(Request $request, User $user)
    {
        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            return response()->json(['error' => 'You cannot remove yourself.'], 422);
        }

        // Detach: $user was following $authUser, so remove that relationship
        $authUser->followers()->detach($user->id);

        return response()->json(['removed' => true]);
    }
}
