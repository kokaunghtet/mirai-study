<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionRevision extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['question_id', 'editor_id', 'action'];

    protected $casts = ['created_at' => 'datetime'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
