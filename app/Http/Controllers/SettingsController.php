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
                ['theme_mode' => 'light', 'accent_color' => 'venom', 'fill_style' => 'gradient', 'show_liked_posts' => true]
            );

        return view('settings.index', compact('preferences'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme_mode'   => 'required|in:light,dark,system',
            'accent_color' => 'required|in:venom,aurora,sangria,twilight,inferno',
            'fill_style'   => 'required|in:gradient,solid',
        ]);

        $request->user()->preferences()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json(['success' => true]);
    }

    /**
     * Persist just the light/dark mode (used by the quick sidebar toggle).
     */
    public function updateThemeMode(Request $request)
    {
        $validated = $request->validate([
            'theme_mode' => 'required|in:light,dark,system',
        ]);

        $request->user()->preferences()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json(['success' => true]);
    }

    /**
     * Toggle email-based two-factor authentication (a security attribute on the user).
     */
    public function updateTwoFactor(Request $request)
    {
        $validated = $request->validate([
            'two_factor_enabled' => 'required|boolean',
        ]);

        $request->user()->update($validated);

        return response()->json(['success' => true]);
    }
}