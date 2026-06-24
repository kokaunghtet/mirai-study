<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_promote_user_to_moderator(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->admin)
            ->patch(route('admin.users.role', $user), ['role' => 'moderator'])
            ->assertRedirect();

        $this->assertEquals('moderator', $user->fresh()->role);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'role_changed',
            'subject_type' => 'User',
            'subject_id' => $user->id,
        ]);

        $log = ActivityLog::where('subject_id', $user->id)->first();
        $this->assertEquals('user', $log->properties['from_role']);
        $this->assertEquals('moderator', $log->properties['to_role']);
    }

    public function test_admin_can_promote_user_to_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->admin)
            ->patch(route('admin.users.role', $user), ['role' => 'admin'])
            ->assertRedirect();

        $this->assertEquals('admin', $user->fresh()->role);

        $log = ActivityLog::where('subject_id', $user->id)->first();
        $this->assertEquals('user', $log->properties['from_role']);
        $this->assertEquals('admin', $log->properties['to_role']);
    }

    public function test_admin_can_promote_moderator_to_admin(): void
    {
        $mod = User::factory()->moderator()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.users.role', $mod), ['role' => 'admin'])
            ->assertRedirect();

        $this->assertEquals('admin', $mod->fresh()->role);

        $log = ActivityLog::where('subject_id', $mod->id)->first();
        $this->assertEquals('moderator', $log->properties['from_role']);
        $this->assertEquals('admin', $log->properties['to_role']);
    }

    public function test_admin_can_demote_moderator_to_user(): void
    {
        $mod = User::factory()->moderator()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.users.role', $mod), ['role' => 'user'])
            ->assertRedirect();

        $this->assertEquals('user', $mod->fresh()->role);

        $log = ActivityLog::where('subject_id', $mod->id)->first();
        $this->assertEquals('moderator', $log->properties['from_role']);
        $this->assertEquals('user', $log->properties['to_role']);
    }

    public function test_admin_can_demote_admin_to_user(): void
    {
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.users.role', $otherAdmin), ['role' => 'user'])
            ->assertRedirect();

        $this->assertEquals('user', $otherAdmin->fresh()->role);

        $log = ActivityLog::where('subject_id', $otherAdmin->id)->first();
        $this->assertEquals('admin', $log->properties['from_role']);
        $this->assertEquals('user', $log->properties['to_role']);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.users.role', $this->admin), ['role' => 'user'])
            ->assertForbidden();

        $this->assertEquals('admin', $this->admin->fresh()->role);
        $this->assertDatabaseMissing('activity_logs', [
            'subject_id' => $this->admin->id,
            'action' => 'role_changed',
        ]);
    }

    public function test_non_admin_cannot_change_roles(): void
    {
        $regular = User::factory()->create(['role' => 'user']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($regular)
            ->patch(route('admin.users.role', $target), ['role' => 'admin'])
            ->assertForbidden();

        $this->assertEquals('user', $target->fresh()->role);
    }

    public function test_role_whitelist_rejects_invalid_values(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.users.role', $user), ['role' => 'superadmin'])
            ->assertUnprocessable();

        $this->assertEquals('user', $user->fresh()->role);
    }
}
