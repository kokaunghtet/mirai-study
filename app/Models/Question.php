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
        'text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'answer',
        'explanation',
    ];

    public function category()
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    public function level()
    {
        return $this->belongsTo(ExamLevel::class, 'level_id');
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }
}
