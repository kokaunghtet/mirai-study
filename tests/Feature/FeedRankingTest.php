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

    /**
     * A viewer's own freshly-created post pins to the very top of their feed for
     * a short window — even above someone else's older, highly-engaged post.
     */
    public function test_own_freshly_created_post_pins_to_top_of_authors_feed(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $hot = Post::factory()->for($other)->create([
            'title' => 'SOMEONE-ELSES-HOT-POST',
            'created_at' => now()->subDays(5),
        ]);
        PostLike::insert(
            User::factory()->count(30)->create()
                ->map(fn ($u) => ['user_id' => $u->id, 'post_id' => $hot->id, 'created_at' => now()])
                ->all(),
        );

        Post::factory()->for($viewer)->create([
            'title' => 'MY-BRAND-NEW-POST',
            'created_at' => now(),
        ]);

        $html = $this->actingAs($viewer)->get(route('feed.index'))->assertOk()->getContent();

        $this->assertStringContainsString('>New<', $html, 'The fresh own post should show a New badge.');
        $this->assertLessThan(
            strpos($html, 'SOMEONE-ELSES-HOT-POST'),
            strpos($html, 'MY-BRAND-NEW-POST'),
            "The viewer's own fresh post should pin above someone else's hot post.",
        );
    }

    /**
     * The pin is viewer-scoped: another user sees normal ranking, so the older
     * highly-engaged post still outranks the fresh post (and shows no New badge).
     */
    public function test_freshness_pin_does_not_apply_to_other_viewers(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();

        $hot = Post::factory()->for($other)->create([
            'title' => 'OTHERS-HOT-POST',
            'created_at' => now()->subDays(5),
        ]);
        PostLike::insert(
            User::factory()->count(30)->create()
                ->map(fn ($u) => ['user_id' => $u->id, 'post_id' => $hot->id, 'created_at' => now()])
                ->all(),
        );

        Post::factory()->for($author)->create([
            'title' => 'AUTHORS-FRESH-POST',
            'created_at' => now(),
        ]);

        // Viewed by $other — $author's fresh post is not theirs, so no pin.
        $html = $this->actingAs($other)->get(route('feed.index'))->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'AUTHORS-FRESH-POST'),
            strpos($html, 'OTHERS-HOT-POST'),
            "Another viewer should see normal ranking, not the author's fresh post pinned.",
        );
    }
}
