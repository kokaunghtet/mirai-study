<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_loads(): void
    {
        Post::factory()->count(3)->create();

        $this->get(route('feed.index'))->assertOk();
    }

    /**
     * The "For You" score must let engagement outrank pure recency: an older
     * post with many likes should appear above a brand-new post with none.
     */
    public function test_engaging_older_post_ranks_above_fresh_dead_post(): void
    {
        $author = User::factory()->create();

        $hot = Post::factory()->for($author)->create([
            'title' => 'OLD-BUT-LOVED',
            'created_at' => now()->subDays(5),
        ]);
        // Give it real engagement from 30 distinct users. Insert directly: the
        // post_likes table has only created_at (no updated_at), so attach() via
        // the withTimestamps() relation can't be used here.
        PostLike::insert(
            User::factory()->count(30)->create()
                ->map(fn ($u) => ['user_id' => $u->id, 'post_id' => $hot->id, 'created_at' => now()])
                ->all(),
        );

        Post::factory()->for($author)->create([
            'title' => 'FRESH-AND-EMPTY',
            'created_at' => now(),
        ]);

        $html = $this->get(route('feed.index'))->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'FRESH-AND-EMPTY'),
            strpos($html, 'OLD-BUT-LOVED'),
            'The highly-liked older post should rank above the fresh empty one.',
        );
    }
}
