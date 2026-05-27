<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggle(Request $request, User $user)
    {
        $authUser = $request->user();

        // Prevent self-following
        if ($authUser->id === $user->id) {
            return back()->with('error', 'You cannot follow yourself.');
        }

        $existing = $authUser->following()->where('following_id', $user->id)->first();

        if ($existing) {
            $authUser->following()->detach($user->id);
            $following = false;
        } else {
            $authUser->following()->attach($user->id, ['status' => 'accepted']);
            $following = true;
        }

        if ($request->expectsJson()) {
            return response()->json(['following' => $following]);
        }

        return back();
    }
}