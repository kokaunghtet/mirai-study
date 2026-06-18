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

    /**
     * A user's completed attempts, newest first.
     */
    public function scopeCompletedFor($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->latest('completed_at');
    }

    /**
     * A user's unfinished attempts, newest first.
     */
    public function scopeInProgressFor($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->whereNull('completed_at')
            ->latest('id');
    }

    /**
     * Human label like "JLPT N3 · Kanji" or "ITPEC IP" (no section).
     */
    public function heading(): string
    {
        $this->loadMissing(['category', 'level']);

        $label = implode(' ', array_filter([
            $this->category?->name,
            $this->level?->code,
        ]));

        if ($this->section) {
            $sectionLabel = config(
                "quiz.catalog.{$this->category?->name}.levels.{$this->level?->code}.sections.{$this->section}",
                ucfirst($this->section)
            );
            $label .= ' · '.$sectionLabel;
        }

        return $label;
    }
}
