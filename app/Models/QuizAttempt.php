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
        'total_questions',
        'score',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
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
}
