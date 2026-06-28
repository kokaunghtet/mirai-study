<?php

namespace App\Http\Controllers;

use App\Models\Appeal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AppealController extends Controller
{
    /**
     * Show the appeal submission form.
     * Only accessible to authenticated users who are currently banned/suspended.
     */
    public function create()
    {
        $user = auth()->user();

        if (! $user || ! $user->isBannedNow()) {
            return redirect()->route('feed.index');
        }

        $ban = $user->activeBan();

        if (! $ban) {
            return redirect()->route('feed.index');
        }

        $openAppeal = $ban->appeals()->where('status', 'pending')->first();

        return view('appeals.create', compact('user', 'ban', 'openAppeal'));
    }

    /**
     * Submit an appeal for the user's active ban.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user || ! $user->isBannedNow()) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Not restricted.'], 422)
                : redirect()->route('feed.index');
        }

        $ban = $user->activeBan();

        if (! $ban) {
            return $request->expectsJson()
                ? response()->json(['error' => 'No active ban found.'], 422)
                : redirect()->route('feed.index');
        }

        // Only one open appeal per ban
        if ($ban->hasOpenAppeal()) {
            return $request->expectsJson()
                ? response()->json(['error' => 'You already have a pending appeal.'], 409)
                : redirect()->back()->with('show_appeal_modal', true);
        }

        $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        Appeal::create([
            'user_ban_id' => $ban->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        Cache::forget('admin_stats');

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : redirect()->back()->with('show_appeal_modal', true);
    }

    /**
     * Submit an appeal from the login page (user is not authenticated).
     * Reads the banned user identity from the ban_appeal session key.
     */
    public function storeGuest(Request $request)
    {
        $banAppeal = session('ban_appeal');

        if (! $banAppeal || ! isset($banAppeal['user_id'])) {
            return redirect()->route('login');
        }

        $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $user = User::find($banAppeal['user_id']);

        if (! $user || ! $user->isBanned()) {
            session()->forget('ban_appeal');

            return redirect()->route('login');
        }

        $ban = $user->activeBan();

        if (! $ban) {
            session()->forget('ban_appeal');

            return redirect()->route('login');
        }

        if ($ban->hasOpenAppeal()) {
            session(['ban_appeal' => array_merge($banAppeal, ['has_open_appeal' => true])]);

            return redirect()->route('login');
        }

        Appeal::create([
            'user_ban_id' => $ban->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        Cache::forget('admin_stats');

        session(['ban_appeal' => array_merge($banAppeal, ['has_open_appeal' => true])]);

        return redirect()->route('login');
    }
}
