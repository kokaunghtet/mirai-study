<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\LinkedAccountService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected LinkedAccountService $linkedAccounts) {}

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

        // Permanently banned: don't authenticate — show appeal on the login page instead
        if ($user->isBanned()) {
            $ban = $user->activeBan();
            $request->session()->put('ban_appeal', [
                'user_id' => $user->id,
                'ban_reason' => $ban?->reason,
                'ban_type' => $ban?->type,
                'has_open_appeal' => $ban?->hasOpenAppeal() ?? false,
            ]);

            return redirect()->route('login');
        }

        // Soft-deleted user with scheduled deletion: fully restore account and cancel deletion.
        if ($user->trashed() && $user->isDeletionScheduled()) {
            $user->restoreFromDeletion();

            $login = $request->input('login');
            $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            Auth::attempt([$field => $login, 'password' => $request->input('password')], $request->boolean('remember'));
            $this->linkedAccounts->remember($request, $user);
            $request->session()->regenerate();

            return redirect()->route('feed.index')->with('success', "Welcome back, {$user->display_name}! Your account has been fully restored.");
        }

        // Email not verified → make them verify before they get a session.
        if (! $user->hasVerifiedEmail()) {
            return $this->startChallenge($request, $otp, $user, 'email_verification');
        }

        // 2FA enabled → email a one-time login code before granting access.
        if ($user->two_factor_enabled) {
            return $this->startChallenge($request, $otp, $user, 'login_verification');
        }

        Auth::login($user, $request->boolean('remember'));

        $this->linkedAccounts->remember($request, $user);

        $request->session()->regenerate();

        $default = match (true) {
            $user->isAdmin() => route('admin.dashboard', absolute: false),
            $user->isModerator() => route('admin.reports', absolute: false),
            default => route('feed.index', absolute: false),
        };

        return redirect()->intended($default)->with('success', "Welcome, {$user->display_name}!");
    }

    /**
     * Issue an OTP and hand off to the shared challenge screen, stashing the pending
     * login in the session (the user is not authenticated until the code is verified).
     */
    protected function startChallenge(Request $request, OtpService $otp, User $user, string $purpose): RedirectResponse
    {
        $otp->issue($user, $purpose);

        $request->session()->put('otp_challenge', [
            'user_id' => $user->id,
            'purpose' => $purpose,
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
