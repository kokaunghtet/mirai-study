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

/**
 * Lets an already-authenticated browser add and hop between multiple accounts
 * without a full logout, layered on the same credential/OTP checks as normal login.
 */
class SwitchAccountController extends Controller
{
    public function __construct(protected LinkedAccountService $accounts) {}

    /**
     * Show the "sign in to another account" form (reachable while already logged in).
     */
    public function create(): View
    {
        return view('auth.add-account');
    }

    /**
     * Authenticate a second set of credentials and add that account to the switcher.
     */
    public function store(LoginRequest $request, OtpService $otp): RedirectResponse
    {
        $user = $request->authenticateCredentials();

        if ($user->isBanned()) {
            return back()->withErrors(['login' => 'This account can\'t be added right now.']);
        }

        if ($user->trashed() && $user->isDeletionScheduled()) {
            $user->restoreFromDeletion();
        }

        if (! $user->hasVerifiedEmail()) {
            return $this->startChallenge($request, $otp, $user, 'email_verification');
        }

        if ($user->two_factor_enabled) {
            return $this->startChallenge($request, $otp, $user, 'login_verification');
        }

        Auth::login($user);

        $this->accounts->remember($request, $user);

        $request->session()->regenerate();

        return redirect()->route('feed.index')->with('success', "Signed in as {$user->display_name}.");
    }

    /**
     * Switch the active session to a previously authenticated account. The target must
     * already be a member of this session's linked accounts — this never accepts a bare
     * user id from the client as sufficient authorization.
     */
    public function switch(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->accounts->contains($request, $user->id), 403);

        if ($user->isBanned()) {
            return back()->withErrors(['login' => 'This account can\'t be switched to right now.']);
        }

        Auth::login($user);

        $this->accounts->remember($request, $user);

        $request->session()->regenerate();

        return redirect()->route('feed.index')->with('success', "Switched to {$user->display_name}.");
    }

    /**
     * Issue an OTP and hand off to the shared challenge screen, same as a normal login.
     */
    protected function startChallenge(Request $request, OtpService $otp, User $user, string $purpose): RedirectResponse
    {
        $otp->issue($user, $purpose);

        $request->session()->put('otp_challenge', [
            'user_id' => $user->id,
            'purpose' => $purpose,
            'remember' => false,
        ]);

        return redirect()->route('otp.challenge');
    }
}
