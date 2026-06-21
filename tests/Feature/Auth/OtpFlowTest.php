<?php

namespace Tests\Feature\Auth;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OtpFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_can_be_toggled_from_settings(): void
    {
        $user = User::factory()->create(); // 2FA off by default

        $this->actingAs($user)
            ->patchJson(route('settings.two-factor'), ['two_factor_enabled' => true])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($user->fresh()->two_factor_enabled);

        $this->actingAs($user)
            ->patchJson(route('settings.two-factor'), ['two_factor_enabled' => false])
            ->assertOk();

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_resend_issues_a_fresh_code_and_invalidates_the_old_one(): void
    {
        Notification::fake();

        $this->post('/register', [
            'username' => 'resender',
            'display_name' => 'Re Sender',
            'email' => 'resend@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'resend@example.com')->first();
        $first = Otp::where('user_id', $user->id)->latest('id')->first();

        $this->post(route('otp.resend'))->assertRedirect();

        // A new code exists and the previous one is now consumed (used_at set).
        $this->assertDatabaseCount('otps', 2);
        $this->assertNotNull($first->fresh()->used_at);

        // Old code no longer works; only the newest valid code does.
        $newest = Otp::where('user_id', $user->id)->latest('id')->first();
        $this->post('/otp', ['code' => $first->otp_code])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/otp', ['code' => $newest->otp_code])
            ->assertRedirect(route('feed.index', absolute: false));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_challenge_redirects_to_login_without_a_pending_session(): void
    {
        $this->get(route('otp.challenge'))->assertRedirect(route('login'));
    }
}
