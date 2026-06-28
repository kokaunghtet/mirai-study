<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_RESOLVED = 'resolved';

    const STATUS_REJECTED = 'rejected';

    // Action constants
    const ACTION_REMOVED_CONTENT = 'removed_content';

    const ACTION_TEMP_BANNED = 'temp_banned';

    const ACTION_PERM_BANNED = 'perm_banned';

    const ACTION_NONE = 'none';

    protected $fillable = [
        'reporter_id',
        'target_type',
        'target_id',
        'category',
        'reason',
        'status',
        'reviewed_by',
        'action_taken',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Dynamically resolve target (post, user, or comment)
    public function target()
    {
        return match ($this->target_type) {
            'post' => $this->belongsTo(Post::class, 'target_id'),
            'comment' => $this->belongsTo(Comment::class, 'target_id'),
            'user' => $this->belongsTo(User::class, 'target_id'),
            default => null,
        };
    }
}
