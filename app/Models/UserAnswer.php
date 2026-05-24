<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_answer',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
