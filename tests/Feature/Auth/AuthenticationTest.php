<?php

namespace Tests\Feature\Auth;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_their_email(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('feed.index', absolute: false));
    }

    public function test_users_can_authenticate_using_their_username(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('feed.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_with_unverified_email_is_sent_to_the_challenge(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('otp.challenge'));
        Notification::assertSentTo($user, OtpNotification::class);
        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'purpose' => 'email_verification',
        ]);
    }

    public function test_two_factor_login_requires_a_code_then_succeeds(): void
    {
        Notification::fake();

        $user = User::factory()->twoFactor()->create();

        $response = $this->post('/login', [
            'login' => $user->username,
            'password' => 'password',
        ]);

        // Password alone is not enough — 2FA gate kicks in.
        $this->assertGuest();
        $response->assertRedirect(route('otp.challenge'));
        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'purpose' => 'login_verification',
        ]);

        $code = Otp::where('user_id', $user->id)
            ->where('purpose', 'login_verification')
            ->latest('id')->first()->otp_code;

        $verify = $this->post('/otp', ['code' => $code]);

        $verify->assertRedirect(route('feed.index', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
