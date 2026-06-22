<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'level_id',
        'section',
        'text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'answer',
        'explanation',
    ];

    /**
     * Scope to a question pool: a category + level, optionally narrowed to a
     * section (kanji/technology/…). A null/empty $section means "level only".
     */
    public function scopePool($query, int $categoryId, int $levelId, ?string $section)
    {
        return $query->where('category_id', $categoryId)
            ->where('level_id', $levelId)
            ->when($section, fn ($q) => $q->where('section', $section));
    }

    public function category()
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    public function level()
    {
        return $this->belongsTo(ExamLevel::class, 'level_id');
    }

    public function revisions()
    {
        return $this->hasMany(QuestionRevision::class);
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }
}
