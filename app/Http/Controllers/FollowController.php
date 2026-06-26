<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggle(Request $request, User $user)
    {
        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            return response()->json(['error' => 'You cannot follow yourself.'], 422);
        }

        $isFollowing = $authUser->following()
            ->where('following_id', $user->id)
            ->exists();

        if ($isFollowing) {
            $authUser->following()->detach($user->id);
            $following = false;
        } else {
            $authUser->following()->attach($user->id, ['status' => 'accepted']);
            $following = true;

            NotificationService::send(
                recipient: $user,
                type: 'follow_user',
                title: $authUser->display_name.' started following you',
                content: '@'.$authUser->username.' is now following you.',
                sender: $authUser,
                url: route('profile.show', $authUser->username),
            );
        }

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
