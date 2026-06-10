<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $preferences = $request->user()->preferences
            ?? UserPreference::firstOrCreate(
                ['user_id' => $request->user()->id],
                ['theme_mode' => 'light', 'accent_color' => 'venom', 'show_liked_posts' => true]
            );

        return view('settings.index', compact('preferences'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme_mode'   => 'required|in:light,dark,system',
            'accent_color' => 'required|in:venom,aurora,sangria,twilight,inferno',
        ]);

        $request->user()->preferences()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json(['success' => true]);
    }
}