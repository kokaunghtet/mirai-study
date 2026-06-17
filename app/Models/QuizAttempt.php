<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'category_id',
        'level_id',
        'section',
        'total_questions',
        'score',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    public function level()
    {
        return $this->belongsTo(ExamLevel::class, 'level_id');
    }

    public function answers()
    {
        return $this->hasMany(UserAnswer::class, 'attempt_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Score as a whole-number percentage of total questions (0 when empty).
     */
    public function percentage(): int
    {
        return $this->total_questions
            ? (int) round($this->score / $this->total_questions * 100)
            : 0;
    }

    public function passed(): bool
    {
        return $this->percentage() >= (int) config('quiz.pass_mark', 60);
    }
}
