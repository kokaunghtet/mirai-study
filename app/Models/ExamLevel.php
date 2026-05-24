<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamLevel extends Model
{
    protected $fillable = ['category_id', 'code', 'name'];

    public function category()
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    public function papers()
    {
        return $this->hasMany(ExamPaper::class, 'level_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'level_id');
    }
}
