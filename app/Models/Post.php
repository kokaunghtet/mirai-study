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

    const RECENCY_DECAY = 259200;          // seconds per freshness point (3 days)

    const RECENCY_EPOCH = 1735689600;      // 2025-01-01 00:00:00 UTC — subtract before dividing so recency values stay small

    const FOLLOW_BOOST = 1.5;            // ≈ 4.5 days fresher if you follow the author

    const JITTER_SCALE = 0.25;           // max ≈ 6h-equivalent reshuffle of near-ties

    const JITTER_MULT = 2654435761;     // Knuth multiplicative hash constant

    const FRESH_PIN_MINUTES = 15;       // a viewer's own post pins to the top this long after creation

    protected $fillable = [
        'user_id',
        'title',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
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

    public function revisions()
    {
        return $this->hasMany(PostRevision::class);
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
     * @param  int|null  $viewerId  the logged-in viewer's id (null for guests); their own
     *                              freshly-created posts pin to the top for FRESH_PIN_MINUTES
     */
    public function scopeForYouRanked(Builder $query, array $followedIds = [], int $seed = 0, ?int $viewerId = null): Builder
    {
        $sqlite = $query->getConnection()->getDriverName() === 'sqlite';

        // Recency ≈ the post's age expressed in days (a fixed value per row).
        $recency = $sqlite
            ? "((CAST(strftime('%s', posts.created_at) AS REAL) - ".self::RECENCY_EPOCH.') / '.self::RECENCY_DECAY.'.0)'
            : '((UNIX_TIMESTAMP(posts.created_at) - '.self::RECENCY_EPOCH.') / '.self::RECENCY_DECAY.')';

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

        // Tier 1: the viewer's own freshly-created posts pin to the very top for a short
        // window, then fall back into normal ranking. created_at is a fixed per-row value,
        // so this boolean only shifts at the FRESH_PIN_MINUTES boundary. Applied before the
        // score so it takes ordering priority; its bindings are appended ahead of the score's.
        if ($viewerId) {
            $cutoff = now()->subMinutes(self::FRESH_PIN_MINUTES)->toDateTimeString();
            $query->orderByRaw(
                '(CASE WHEN posts.user_id = ? AND posts.created_at > ? THEN 1 ELSE 0 END) DESC',
                [$viewerId, $cutoff]
            );
        }

        return $query->orderByRaw("{$score} DESC", $bindings)
            ->orderByDesc('posts.id');
    }

    /**
     * Whether this post is still inside its "freshly created" pin window for the
     * given viewer (defaults to the authenticated user) — i.e. the viewer is the
     * author and the post is younger than FRESH_PIN_MINUTES. Drives the "New" badge.
     */
    public function isFreshForViewer(?User $viewer = null): bool
    {
        $viewer ??= auth()->user();

        return $viewer
            && $this->user_id === $viewer->id
            && $this->created_at->gt(now()->subMinutes(self::FRESH_PIN_MINUTES));
    }
}
