<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Send the user off to Google's consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google: find or create the local user, then sign in.
     *
     * Google has already verified the email, so we skip the OTP challenge that the
     * normal email/password flow uses and log the user in directly.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->withErrors(['login' => 'Google sign-in failed. Please try again.']);
        }

        $user = $this->findOrCreateUser($googleUser);

        if ($user->isBanned()) {
            $ban = $user->activeBan();
            request()->session()->put('ban_appeal', [
                'user_id' => $user->id,
                'ban_reason' => $ban?->reason,
                'ban_type' => $ban?->type,
                'has_open_appeal' => $ban?->hasOpenAppeal() ?? false,
            ]);

            return redirect()->route('login');
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('feed.index'))->with('success', "Welcome, {$user->display_name}!");
    }

    /**
     * Match the Google account to a local user (by google_id, then email), linking or
     * creating one as needed.
     */
    protected function findOrCreateUser(SocialiteUser $googleUser): User
    {
        // 1. Returning Google user.
        $user = User::where('google_id', $googleUser->getId())->first();

        // 2. Existing email/password account → link it to this Google identity.
        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();
        }

        if ($user) {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                // Signing in with Google proves the email, so consider it verified.
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        }

        // 3. Brand-new user.
        $user = User::create([
            'username' => $this->generateUsername($googleUser),
            'display_name' => $googleUser->getName() ?: 'Mirai Student',
            'email' => $googleUser->getEmail(),
            'password' => null,
            'google_id' => $googleUser->getId(),
            'profile_image' => $googleUser->getAvatar(),
        ]);

        // email_verified_at isn't mass-assignable; set it via the model helper.
        $user->markEmailAsVerified();

        return $user;
    }

    /**
     * Build a unique username from the Google email/name. Must satisfy the app's rule:
     * lowercase, ^[a-z0-9]{3,30}$, and unique across soft-deleted rows too.
     */
    protected function generateUsername(SocialiteUser $googleUser): string
    {
        $base = preg_replace('/[^a-z0-9]/', '', Str::lower(Str::before($googleUser->getEmail(), '@')));

        if (strlen($base) < 3) {
            $base = 'user'.$base;
        }

        $base = substr($base, 0, 30);

        $username = $base;
        $i = 1;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $suffix = (string) $i++;
            $username = substr($base, 0, 30 - strlen($suffix)).$suffix;
        }

        return $username;
    }
}
