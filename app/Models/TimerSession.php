<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PomodoroSetting extends Model
{
    protected $fillable = [
        'user_id',
        'focus_minutes',
        'short_break_minutes',
        'long_break_minutes',
        'sessions_before_long_break',
        'daily_goal_sessions',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
