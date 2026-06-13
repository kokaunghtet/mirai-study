<?php

namespace Tests\Feature\Auth;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_register_unverified_and_receive_a_code(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'username' => 'testuser',
            'display_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Not signed in yet — they must verify the email first.
        $this->assertGuest();
        $response->assertRedirect(route('otp.challenge'));

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, OtpNotification::class);
        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'purpose' => 'email_verification',
        ]);
    }

    public function test_user_can_verify_email_with_code_and_is_logged_in(): void
    {
        Notification::fake();

        $this->post('/register', [
            'username' => 'testuser',
            'display_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $code = Otp::where('user_id', $user->id)
            ->where('purpose', 'email_verification')
            ->latest('id')->first()->otp_code;

        $response = $this->post('/otp', ['code' => $code]);

        $response->assertRedirect(route('feed.index', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_wrong_verification_code_is_rejected(): void
    {
        Notification::fake();

        $this->post('/register', [
            'username' => 'testuser',
            'display_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $real = Otp::where('user_id', $user->id)->latest('id')->first()->otp_code;
        $wrong = $real === '000000' ? '111111' : '000000';

        $response = $this->from(route('otp.challenge'))->post('/otp', ['code' => $wrong]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_username_is_forced_to_lowercase(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'username' => 'KaungHtet',
            'display_name' => 'Kaung Htet',
            'email' => 'kaung@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('otp.challenge'));
        $this->assertDatabaseHas('users', ['username' => 'kaunghtet']);
    }

    public function test_username_with_symbols_is_rejected(): void
    {
        $response = $this->from('/register')->post('/register', [
            'username' => 'kaung_htet.2004',
            'display_name' => 'Kaung Htet',
            'email' => 'kaung@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_username_suggestions_are_alphanumeric_and_available(): void
    {
        $response = $this->getJson(route('username.suggestions', ['name' => 'Kaung Htet']));

        $response->assertOk();
        $usernames = $response->json('usernames');

        $this->assertNotEmpty($usernames);
        foreach ($usernames as $username) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]{3,30}$/', $username);
            $this->assertStringStartsWith('kaunghtet', $username);
            $this->assertDatabaseMissing('users', ['username' => $username]);
        }
    }

    public function test_taken_username_is_reported_unavailable(): void
    {
        User::factory()->create(['username' => 'kaunghtet']);

        $this->getJson(route('username.available', ['username' => 'kaunghtet']))
            ->assertOk()
            ->assertJson(['valid' => true, 'available' => false]);

        $this->getJson(route('username.available', ['username' => 'kaunghtet2004']))
            ->assertOk()
            ->assertJson(['valid' => true, 'available' => true]);
    }
}
