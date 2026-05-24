<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamCategory extends Model
{
    protected $fillable = ['name'];

    public function levels()
    {
        return $this->hasMany(ExamLevel::class, 'category_id');
    }

    public function papers()
    {
        return $this->hasMany(ExamPaper::class, 'category_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'category_id');
    }
}
