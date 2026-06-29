<?php

namespace Tests\Feature\Admin;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $moderator;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->moderator = User::factory()->moderator()->create();
        $this->user = User::factory()->create(['role' => 'user']);
    }

    // ── Report submission (ReportController::store) ──

    public function test_authenticated_user_can_report_post(): void
    {
        $post = Post::factory()->create(['user_id' => User::factory()->create()]);

        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'post',
                'target_id' => $post->id,
                'category' => 'spam',
                'reason' => 'This is spam content',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $this->user->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);
    }

    public function test_authenticated_user_can_report_comment(): void
    {
        $comment = Comment::factory()->create(['user_id' => User::factory()->create()]);

        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'comment',
                'target_id' => $comment->id,
                'category' => 'harassment',
            ])
            ->assertOk();

        $this->assertDatabaseHas('reports', [
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'category' => 'harassment',
        ]);
    }

    public function test_authenticated_user_can_report_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'user',
                'target_id' => $target->id,
                'category' => 'inappropriate',
            ])
            ->assertOk();

        $this->assertDatabaseHas('reports', [
            'target_type' => 'user',
            'target_id' => $target->id,
        ]);
    }

    public function test_guest_cannot_report(): void
    {
        $post = Post::factory()->create();

        $this->postJson(route('reports.store'), [
            'target_type' => 'post',
            'target_id' => $post->id,
            'category' => 'spam',
        ])->assertUnauthorized();
    }

    public function test_self_report_is_blocked(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'user',
                'target_id' => $this->user->id,
                'category' => 'spam',
            ])
            ->assertUnprocessable()
            ->assertJson(['error' => 'self']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_reporting_admin_user_is_blocked(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'user',
                'target_id' => $this->admin->id,
                'category' => 'spam',
            ])
            ->assertUnprocessable()
            ->assertJson(['error' => 'admin']);
    }

    public function test_reporting_admin_post_is_blocked(): void
    {
        $post = Post::factory()->create(['user_id' => $this->admin->id]);

        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'post',
                'target_id' => $post->id,
                'category' => 'spam',
            ])
            ->assertUnprocessable()
            ->assertJson(['error' => 'admin']);
    }

    public function test_reporting_admin_comment_is_blocked(): void
    {
        $comment = Comment::factory()->create(['user_id' => $this->admin->id]);

        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'comment',
                'target_id' => $comment->id,
                'category' => 'spam',
            ])
            ->assertUnprocessable()
            ->assertJson(['error' => 'admin']);
    }

    public function test_duplicate_report_is_blocked(): void
    {
        $post = Post::factory()->create(['user_id' => User::factory()->create()]);

        Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'category' => 'spam',
        ]);

        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'post',
                'target_id' => $post->id,
                'category' => 'harassment',
            ])
            ->assertStatus(409)
            ->assertJson(['error' => 'duplicate']);
    }

    public function test_invalid_target_type_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'invalid',
                'target_id' => 1,
                'category' => 'spam',
            ])
            ->assertUnprocessable();
    }

    public function test_invalid_category_is_rejected(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'post',
                'target_id' => $post->id,
                'category' => 'invalid_category',
            ])
            ->assertUnprocessable();
    }

    public function test_nonexistent_target_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'post',
                'target_id' => 999999,
                'category' => 'spam',
            ])
            ->assertUnprocessable();
    }

    public function test_reason_is_optional(): void
    {
        $post = Post::factory()->create(['user_id' => User::factory()->create()]);

        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'post',
                'target_id' => $post->id,
                'category' => 'spam',
            ])
            ->assertOk();

        $this->assertDatabaseHas('reports', [
            'target_type' => 'post',
            'category' => 'spam',
            'reason' => null,
        ]);
    }

    // ── Reports page access (AdminController::reports) ──

    public function test_admin_can_view_reports_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports'))
            ->assertOk();
    }

    public function test_moderator_can_view_reports_page(): void
    {
        $this->actingAs($this->moderator)
            ->get(route('admin.reports'))
            ->assertOk();
    }

    public function test_regular_user_cannot_view_reports_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.reports'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_reports_page(): void
    {
        $this->get(route('admin.reports'))
            ->assertRedirect(route('login'));
    }

    public function test_reports_page_defaults_to_pending_filter(): void
    {
        Post::factory()->create(['user_id' => User::factory()->create()]);
        $pending = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => User::factory()->create()->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);
        $resolved = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => User::factory()->create()->id,
            'category' => 'spam',
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports'))
            ->assertOk();

        $response->assertSee('Pending');
    }

    public function test_reports_page_filters_by_status(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports', ['status' => 'resolved']))
            ->assertOk();
    }

    public function test_reports_page_filters_by_type(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports', ['type' => 'post']))
            ->assertOk();
    }

    public function test_reports_ajax_returns_html(): void
    {
        $this->actingAs($this->admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertJsonStructure(['html']);
    }

    // ── Report resolution (AdminController::updateReport) ──

    public function test_admin_can_reject_report(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'reject',
            ])
            ->assertOk();

        $report->refresh();
        $this->assertEquals('rejected', $report->status);
        $this->assertEquals('none', $report->action_taken);
        $this->assertEquals($this->admin->id, $report->reviewed_by);
    }

    public function test_admin_can_temp_ban_user_from_report(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'harassment',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'temp_ban',
                'duration' => 7,
                'reason' => 'Harassment',
            ])
            ->assertOk();

        $report->refresh();
        $this->assertEquals('resolved', $report->status);
        $this->assertEquals('temp_banned', $report->action_taken);

        $target->refresh();
        $this->assertEquals('suspended', $target->status);
        $this->assertTrue($target->isSuspended());

        $this->assertDatabaseHas('user_bans', [
            'user_id' => $target->id,
            'type' => 'temporary',
            'report_id' => $report->id,
            'banned_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_perm_ban_user_from_report(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'perm_ban',
                'reason' => 'Repeated spam',
            ])
            ->assertOk();

        $report->refresh();
        $this->assertEquals('resolved', $report->status);
        $this->assertEquals('perm_banned', $report->action_taken);

        $target->refresh();
        $this->assertEquals('banned', $target->status);
        $this->assertTrue($target->isBanned());

        $this->assertDatabaseHas('user_bans', [
            'user_id' => $target->id,
            'type' => 'permanent',
            'report_id' => $report->id,
        ]);
    }

    public function test_admin_can_remove_content_from_report(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'category' => 'inappropriate',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'remove_content',
            ])
            ->assertOk();

        $report->refresh();
        $this->assertEquals('resolved', $report->status);
        $this->assertEquals('removed_content', $report->action_taken);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_admin_can_temp_ban_and_remove_content(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'temp_ban_remove',
                'duration' => 3,
            ])
            ->assertOk();

        $report->refresh();
        $this->assertEquals('temp_banned_removed', $report->action_taken);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        $author->refresh();
        $this->assertEquals('suspended', $author->status);
    }

    public function test_admin_can_perm_ban_and_remove_content(): void
    {
        $author = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $author->id]);
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'category' => 'harassment',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'perm_ban_remove',
                'reason' => 'Severe harassment',
            ])
            ->assertOk();

        $report->refresh();
        $this->assertEquals('perm_banned_removed', $report->action_taken);
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);

        $author->refresh();
        $this->assertEquals('banned', $author->status);
    }

    // ── Permission guards on report resolution ──

    public function test_moderator_can_resolve_report(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->moderator)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'reject',
            ])
            ->assertOk();

        $this->assertEquals('rejected', $report->fresh()->status);
    }

    public function test_regular_user_cannot_resolve_report(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'reject',
            ])
            ->assertForbidden();
    }

    public function test_cannot_resolve_already_processed_report(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'resolved',
            'action_taken' => 'none',
            'reviewed_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'reject',
            ])
            ->assertUnprocessable();
    }

    public function test_cannot_ban_admin_from_report(): void
    {
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $this->admin->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->moderator)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'temp_ban',
                'duration' => 7,
            ])
            ->assertForbidden();
    }

    public function test_moderator_cannot_ban_other_moderator_from_report(): void
    {
        $otherMod = User::factory()->moderator()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $otherMod->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->moderator)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'temp_ban',
                'duration' => 7,
            ])
            ->assertForbidden();
    }

    public function test_cannot_ban_yourself_from_report(): void
    {
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $this->admin->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'perm_ban',
            ])
            ->assertForbidden();
    }

    public function test_cannot_remove_content_for_user_report(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'remove_content',
            ])
            ->assertUnprocessable();
    }

    public function test_temp_ban_requires_duration(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'temp_ban',
            ])
            ->assertUnprocessable();
    }

    public function test_invalid_action_is_rejected(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'invalid_action',
            ])
            ->assertUnprocessable();
    }

    // ── Activity logging ──

    public function test_temp_ban_logs_activity(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'temp_ban',
                'duration' => 7,
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'user_temp_banned',
            'subject_type' => 'User',
            'subject_id' => $target->id,
        ]);
    }

    public function test_perm_ban_logs_activity(): void
    {
        $target = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'user',
            'target_id' => $target->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'perm_ban',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'user_perm_banned',
            'subject_id' => $target->id,
        ]);
    }

    public function test_remove_content_logs_activity(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'remove_content',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'content_removed',
            'subject_type' => 'Post',
            'subject_id' => $post->id,
        ]);
    }

    // ── Edge cases ──

    public function test_reporting_soft_deleted_post_is_allowed(): void
    {
        $post = Post::factory()->create(['user_id' => User::factory()->create()]);
        $post->delete();

        $this->actingAs($this->user)
            ->postJson(route('reports.store'), [
                'target_type' => 'post',
                'target_id' => $post->id,
                'category' => 'spam',
            ])
            ->assertOk();

        $this->assertDatabaseHas('reports', [
            'target_type' => 'post',
            'target_id' => $post->id,
        ]);
    }

    public function test_admin_can_ban_author_of_reported_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'temp_ban',
                'duration' => 30,
            ])
            ->assertOk();

        $author->refresh();
        $this->assertEquals('suspended', $author->status);
    }

    public function test_moderator_cannot_ban_admin_post_author(): void
    {
        $post = Post::factory()->create(['user_id' => $this->admin->id]);
        $report = Report::create([
            'reporter_id' => $this->user->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($this->moderator)
            ->patchJson(route('admin.reports.update', $report), [
                'action' => 'temp_ban',
                'duration' => 7,
            ])
            ->assertForbidden();
    }
}
