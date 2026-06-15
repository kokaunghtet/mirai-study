<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * "For You" ranking weights — see scopeForYouRanked(). All tunable.
     */
    const ENGAGE_COMMENT_WEIGHT = 2;     // a comment is worth 2 likes

    const ENGAGE_BOOKMARK_WEIGHT = 1.5;   // a bookmark is worth 1.5 likes

    const RECENCY_DECAY = 86400;          // seconds per freshness point (1 day)

    const FOLLOW_BOOST = 0.7;            // ≈ 0.7 days fresher if you follow the author

    const JITTER_SCALE = 0.25;           // max ≈ 6h-equivalent reshuffle of near-ties

    const JITTER_MULT = 2654435761;     // Knuth multiplicative hash constant

    protected $fillable = [
        'user_id',
        'title',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'post_likes')->withTimestamps();
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Order the query by a Reddit-style "hot" score blending engagement,
     * recency and a boost for authors the viewer follows, plus a subtle
     * per-session jitter so the feed reshuffles slightly between visits.
     *
     *   score = ENGAGEMENT + RECENCY + FOLLOW_BOOST + JITTER
     *
     * The score is built only from each post's *own* columns (created_at
     * epoch, withCount aliases) and a fixed per-session $seed — never NOW() —
     * so the order is byte-stable across the separate AJAX requests that
     * infinite scroll fires. A final `posts.id DESC` guarantees a total order.
     *
     * Requires `withCount(['likes', 'comments', 'bookmarks'])` on the query.
     *
     * @param  int[]  $followedIds  user ids the viewer follows (accepted)
     * @param  int  $seed  per-session jitter seed
     */
    public function scopeForYouRanked(Builder $query, array $followedIds = [], int $seed = 0): Builder
    {
        $sqlite = $query->getConnection()->getDriverName() === 'sqlite';

        // Recency ≈ the post's age expressed in days (a fixed value per row).
        $recency = $sqlite
            ? "(CAST(strftime('%s', posts.created_at) AS REAL) / ".self::RECENCY_DECAY.'.0)'
            : '(UNIX_TIMESTAMP(posts.created_at) / '.self::RECENCY_DECAY.')';

        // Weighted engagement (withCount aliases are resolvable in ORDER BY on
        // both MySQL and SQLite). LOG10 dampens viral runaway on MySQL; the
        // SQLite test build may lack math functions, so fall back to linear —
        // feed order isn't asserted in tests, this path only needs to not crash.
        $weighted = '(likes_count + '.self::ENGAGE_COMMENT_WEIGHT.' * comments_count + '
            .self::ENGAGE_BOOKMARK_WEIGHT.' * bookmarks_count)';
        $engagement = $sqlite ? "(1 + {$weighted})" : "LOG10(1 + {$weighted})";

        $bindings = [];

        // Boost for posts whose author the viewer follows.
        $boost = '0';
        if (! empty($followedIds)) {
            $placeholders = implode(',', array_fill(0, count($followedIds), '?'));
            $boost = "(CASE WHEN posts.user_id IN ({$placeholders}) THEN ? ELSE 0 END)";
            $bindings = array_merge($bindings, array_values($followedIds), [self::FOLLOW_BOOST]);
        }

        // Deterministic per-(post, seed) jitter in [0, JITTER_SCALE); only
        // reshuffles posts with near-identical scores.
        $jitter = '((((posts.id * ?) + ?) % 100000) / 100000.0 * ?)';
        $bindings = array_merge($bindings, [self::JITTER_MULT, $seed, self::JITTER_SCALE]);

        $score = "{$engagement} + {$recency} + {$boost} + {$jitter}";

        return $query->orderByRaw("{$score} DESC", $bindings)
            ->orderByDesc('posts.id');
    }
}
