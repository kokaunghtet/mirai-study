<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
    ];

    // added this function to get the comment counting right
    protected static function booted(): void
    {
        static::deleting(function (Comment $comment) {
            // Soft-delete all replies when the parent comment is soft-deleted
            $comment->replies()->each(function (Comment $reply) {
                $reply->delete();
            });
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // Parent comment (null if top-level)
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    // Direct replies to this comment
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function likes()
    {
        return $this->hasMany(CommentLike::class);
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'comment_likes')->withTimestamps();
    }
}
