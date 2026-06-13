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
        $settings       = null;
        $todaySessions  = 0;
        $todayFocusTime = 0;

        if ($request->user()) {
            $settings = $request->user()->pomodoroSettings
                ?? PomodoroSetting::create(['user_id' => $request->user()->id]);

            [$todaySessions, $todayFocusTime] = $this->todayStats($request->user());
        }

        return view('timer.index', compact('settings', 'todaySessions', 'todayFocusTime'));
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

        [$todaySessions, $todayFocusTime] = $this->todayStats($request->user());

        return response()->json([
            'success'          => true,
            'today_sessions'   => $todaySessions,
            'today_focus_time' => $todayFocusTime,
        ]);
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

        return response()->json(['success' => true]);
    }

    /**
     * Today's completed-session count and total focus minutes for a user.
     *
     * @return array{0:int,1:int}
     */
    private function todayStats($user): array
    {
        $base = $user->timerSessions()
            ->where('completed', true)
            ->whereDate('created_at', today());

        return [
            (int) (clone $base)->count(),
            (int) (clone $base)->sum('actual_duration'),
        ];
    }
}