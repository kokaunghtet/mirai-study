<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaperDownload extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'paper_id'];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paper()
    {
        return $this->belongsTo(ExamPaper::class, 'paper_id');
    }
}
