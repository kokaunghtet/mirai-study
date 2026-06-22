<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaperRevision extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['paper_id', 'editor_id', 'action'];

    protected $casts = ['created_at' => 'datetime'];

    public function paper()
    {
        return $this->belongsTo(ExamPaper::class, 'paper_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
