<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamPaper extends Model
{
    protected $fillable = [
        'category_id',
        'level_id',
        'uploaded_by',
        'title',
        'year',
        'session',
        'part',
        'doc_type',
        'description',
        'file_url',
        'file_type',
    ];

    public function category()
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    public function level()
    {
        return $this->belongsTo(ExamLevel::class, 'level_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function downloads()
    {
        return $this->hasMany(PaperDownload::class, 'paper_id');
    }

    public function revisions()
    {
        return $this->hasMany(PaperRevision::class, 'paper_id');
    }
}
