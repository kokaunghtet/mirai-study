<?php

namespace Tests\Feature\Auth;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_a_reset_code_is_emailed_for_a_known_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.code'));

        Notification::assertSentTo($user, OtpNotification::class,
            fn (OtpNotification $n) => $n->purpose === 'password_reset');
    }

    public function test_unknown_email_is_rejected_and_sends_nothing(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_the_reset_form_is_gated_until_the_code_is_verified(): void
    {
        // No pending session at all → back to the email step.
        $this->get('/reset-password')->assertRedirect(route('password.request'));

        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        // Code requested but not yet verified → back to the code step.
        $this->get('/reset-password')->assertRedirect(route('password.code'));
    }

    public function test_a_wrong_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        $this->post('/forgot-password/code', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->get('/reset-password')->assertRedirect(route('password.code'));
    }

    public function test_password_can_be_reset_through_the_full_otp_flow(): void
    {
        Notification::fake(); // the code still lands in the DB; just no real mail

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.code'));

        $code = Otp::where('user_id', $user->id)
            ->where('purpose', 'password_reset')
            ->latest('id')->first()->otp_code;

        $this->post('/forgot-password/code', ['code' => $code])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.reset'));

        $this->post('/reset-password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertGuest(); // reset does not log the user in
    }

    public function test_the_new_password_must_differ_from_the_current_one(): void
    {
        Notification::fake();

        $user = User::factory()->create(); // factory password is 'password'

        $this->post('/forgot-password', ['email' => $user->email]);

        $code = Otp::where('user_id', $user->id)
            ->where('purpose', 'password_reset')
            ->latest('id')->first()->otp_code;

        $this->post('/forgot-password/code', ['code' => $code]);

        $this->post('/reset-password', [
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');

        // Password is unchanged and the session is still usable for a real change.
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
