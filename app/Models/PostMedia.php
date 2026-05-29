<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostMedia extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'url', 'type', 'filename', 'filesize'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
