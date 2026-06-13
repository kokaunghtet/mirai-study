<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, OtpService $otp): RedirectResponse
    {
        $user = $request->authenticateCredentials();

        // Email not verified → make them verify before they get a session.
        if (! $user->hasVerifiedEmail()) {
            return $this->startChallenge($request, $otp, $user, 'email_verification');
        }

        // 2FA enabled → email a one-time login code before granting access.
        if ($user->two_factor_enabled) {
            return $this->startChallenge($request, $otp, $user, 'login_verification');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('feed.index', absolute: false));
    }

    /**
     * Issue an OTP and hand off to the shared challenge screen, stashing the pending
     * login in the session (the user is not authenticated until the code is verified).
     */
    protected function startChallenge(Request $request, OtpService $otp, User $user, string $purpose): RedirectResponse
    {
        $otp->issue($user, $purpose);

        $request->session()->put('otp_challenge', [
            'user_id'  => $user->id,
            'purpose'  => $purpose,
            'remember' => $request->boolean('remember'),
        ]);

        return redirect()->route('otp.challenge');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
