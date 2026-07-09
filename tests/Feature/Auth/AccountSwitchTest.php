<?php

namespace Tests\Feature\Auth;

use App\Models\Otp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_a_second_account(): void
    {
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id]])
            ->post(route('accounts.add.store'), ['login' => $b->username, 'password' => 'password'])
            ->assertRedirect(route('feed.index'));

        $this->assertAuthenticatedAs($b);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], session('linked_accounts'));
    }

    public function test_switching_requires_the_account_to_be_linked_in_this_session(): void
    {
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id]])
            ->post(route('accounts.switch', $b))
            ->assertForbidden();

        $this->assertAuthenticatedAs($a);
    }

    public function test_user_can_switch_to_a_linked_account(): void
    {
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id, $b->id]])
            ->post(route('accounts.switch', $b))
            ->assertRedirect(route('feed.index'));

        $this->assertAuthenticatedAs($b);
    }

    public function test_current_account_cannot_be_removed(): void
    {
        $a = User::factory()->create();

        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id]])
            ->delete(route('accounts.remove', $a))
            ->assertSessionHasErrors('login');

        $this->assertContains($a->id, session('linked_accounts'));
    }

    public function test_linked_account_can_be_removed_and_can_no_longer_be_switched_to(): void
    {
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id, $b->id]])
            ->delete(route('accounts.remove', $b))
            ->assertRedirect();

        $this->assertEquals([$a->id], session('linked_accounts'));

        $this->post(route('accounts.switch', $b))->assertForbidden();
    }

    public function test_cap_blocks_adding_an_account_beyond_max(): void
    {
        [$a, $b, $c] = User::factory()->count(3)->create();

        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id, $b->id]])
            ->post(route('accounts.add.store'), ['login' => $c->username, 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertAuthenticatedAs($a);
    }

    public function test_readding_an_already_linked_account_is_allowed_at_the_cap(): void
    {
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id, $b->id]])
            ->post(route('accounts.add.store'), ['login' => $b->username, 'password' => 'password'])
            ->assertRedirect(route('feed.index'));

        $this->assertAuthenticatedAs($b);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], session('linked_accounts'));
    }

    public function test_adding_a_two_factor_account_goes_through_the_otp_challenge(): void
    {
        Notification::fake();

        [$a] = User::factory()->count(1)->create();
        $b = User::factory()->twoFactor()->create();

        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id]])
            ->post(route('accounts.add.store'), ['login' => $b->username, 'password' => 'password'])
            ->assertRedirect(route('otp.challenge'));

        // Not switched yet — credentials alone don't grant the second session.
        $this->assertAuthenticatedAs($a);

        $code = Otp::where('user_id', $b->id)->latest('id')->first();

        $this->post(route('otp.verify'), ['code' => $code->otp_code])
            ->assertRedirect(route('feed.index', absolute: false));

        $this->assertAuthenticatedAs($b);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], session('linked_accounts'));
    }

    public function test_cap_is_reenforced_when_an_otp_challenge_completes(): void
    {
        Notification::fake();

        [$a, $b] = User::factory()->count(2)->create();
        $c = User::factory()->twoFactor()->create();

        // Challenge for C issued earlier; meanwhile the session filled up to the cap.
        app(OtpService::class)->issue($c, 'login_verification');
        $code = Otp::where('user_id', $c->id)->latest('id')->first();

        $this->actingAs($a)
            ->withSession([
                'linked_accounts' => [$a->id, $b->id],
                'otp_challenge' => [
                    'user_id' => $c->id,
                    'purpose' => 'login_verification',
                    'remember' => false,
                    'add_account' => true,
                ],
            ])
            ->post(route('otp.verify'), ['code' => $code->otp_code])
            ->assertRedirect(route('accounts.add'))
            ->assertSessionHasErrors('login');

        $this->assertAuthenticatedAs($a);
    }

    public function test_banned_linked_account_is_pruned_and_frees_its_slot(): void
    {
        [$a, $b] = User::factory()->count(2)->create();
        $b->update(['status' => 'banned']);

        // Rendering any page runs the switcher, which prunes ids that no longer resolve.
        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id, $b->id]])
            ->get(route('feed.index'))
            ->assertOk();

        $this->assertEquals([$a->id], session('linked_accounts'));
    }

    public function test_switching_to_a_banned_linked_account_is_rejected(): void
    {
        [$a, $b] = User::factory()->count(2)->create();
        $b->update(['status' => 'banned']);

        $this->actingAs($a)
            ->withSession(['linked_accounts' => [$a->id, $b->id]])
            ->post(route('accounts.switch', $b))
            ->assertSessionHasErrors('login');

        $this->assertAuthenticatedAs($a);
    }

    /**
     * Regression: the soft-delete global scope hides deletion-scheduled users, so without
     * the withTrashed fallback in LoginRequest a wrong password reported "account not found".
     */
    public function test_deletion_scheduled_user_with_wrong_password_gets_a_password_error(): void
    {
        $user = User::factory()->create();
        $user->update(['deletion_scheduled_at' => now()->addMonth()]);
        $user->delete();

        $this->post('/login', ['login' => $user->username, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('login_password')
            ->assertSessionDoesntHaveErrors('login');
    }
}
