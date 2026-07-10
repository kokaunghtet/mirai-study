<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\ProfanityFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostProfanityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Deterministic blacklist regardless of config/moderation.php edits.
        $this->app->instance(
            ProfanityFilter::class,
            new ProfanityFilter(['shit', 'cunt']),
        );
    }

    public function test_post_with_profane_content_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), ['content' => 'this is sh1t']);

        $response->assertSessionHasErrors('content');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_with_profane_title_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->post(route('posts.store'), [
            'title' => 's h i t happens',
            'content' => 'perfectly clean content',
        ]);

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_clean_post_is_created(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), ['content' => 'A clean study note about JLPT N3 grammar.']);

        $post = Post::first();
        $this->assertNotNull($post);
        $response->assertRedirect(route('posts.show', $post));
    }

    public function test_scunthorpe_style_content_is_not_flagged(): void
    {
        $this->actingAs($this->user)
            ->post(route('posts.store'), ['content' => 'Our assessment of the class trip to Scunthorpe'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_editing_a_post_cannot_bypass_the_filter(): void
    {
        $post = Post::factory()->for($this->user)->create(['content' => 'clean original']);

        $response = $this->actingAs($this->user)
            ->patch(route('posts.update', $post), ['content' => 'shiiit']);

        $response->assertSessionHasErrors('content');
        $this->assertSame('clean original', $post->fresh()->content);
    }

    public function test_comment_with_profanity_is_rejected(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('comments.store', $post), ['content' => 's.h.i.t']);

        $response->assertSessionHasErrors('content');
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_ajax_comment_with_profanity_gets_422_json(): void
    {
        // The comment drawer submits via fetch with Accept: application/json;
        // it must receive a 422 with field errors, never a redirect.
        $post = Post::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('comments.store', $post), ['content' => 'sh1t'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('content');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_clean_comment_is_created(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store', $post), ['content' => 'Great post, thanks for sharing!'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_clean_unicode_post_is_created(): void
    {
        $this->actingAs($this->user)
            ->post(route('posts.store'), ['content' => '日本語の勉強を頑張ります。မင်္ဂလာပါ။'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_shipped_config_blacklist_is_wired_up(): void
    {
        // No rebind here — exercises the real AppServiceProvider singleton
        // built from config/moderation.php.
        $this->app->forgetInstance(ProfanityFilter::class);

        $this->actingAs($this->user)
            ->post(route('posts.store'), ['content' => 'well f.u.c.k this exam'])
            ->assertSessionHasErrors('content');

        $this->assertDatabaseCount('posts', 0);
    }
}
