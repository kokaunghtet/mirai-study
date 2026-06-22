<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostRevision extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['post_id', 'editor_id', 'title', 'content'];

    protected $casts = ['created_at' => 'datetime'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
