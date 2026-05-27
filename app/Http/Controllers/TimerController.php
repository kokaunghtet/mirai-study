<?php

namespace App\Http\Controllers;

use App\Models\PomodoroSetting;
use App\Models\TimerSession;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    // Both guests and auth users can view
    public function index(Request $request)
    {
        $settings = null;

        if ($request->user()) {
            $settings = $request->user()->pomodoroSettings
                ?? PomodoroSetting::create(['user_id' => $request->user()->id]);
        }

        return view('timer.index', compact('settings'));
    }

    // Only auth users can store sessions
    public function store(Request $request)
    {
        $validated = $request->validate([
            'planned_duration' => 'required|integer|min:1',
            'actual_duration'  => 'nullable|integer|min:0',
            'completed'        => 'required|boolean',
            'started_at'       => 'required|date',
            'ended_at'         => 'nullable|date',
        ]);

        $request->user()->timerSessions()->create($validated);

        return response()->json(['success' => true]);
    }

    // Only auth users can update settings
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'focus_minutes'              => 'required|integer|min:1|max:120',
            'short_break_minutes'        => 'required|integer|min:1|max:60',
            'long_break_minutes'         => 'required|integer|min:1|max:120',
            'sessions_before_long_break' => 'required|integer|min:1|max:10',
            'daily_goal_sessions'        => 'required|integer|min:1|max:24',
        ]);

        $request->user()->pomodoroSettings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return back()->with('success', 'Timer settings updated.');
    }
}