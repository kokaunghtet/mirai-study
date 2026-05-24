<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostMedium extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'url', 'type'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
