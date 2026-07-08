<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\MasksEmail;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LinkedAccountService;
use App\Services\OtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtpChallengeController extends Controller
{
    use MasksEmail;

    public function __construct(protected OtpService $otp, protected LinkedAccountService $linkedAccounts) {}

    /**
     * Show the 6-digit code entry screen for the pending challenge.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $challenge = $request->session()->get('otp_challenge');

        if (! $challenge || ! ($user = User::find($challenge['user_id']))) {
            return redirect()->route('login');
        }

        return view('auth.otp-challenge', [
            'purpose' => $challenge['purpose'],
            'maskedEmail' => $this->maskEmail($user->email),
            'secondsRemaining' => $this->otp->secondsUntilExpiry($user, $challenge['purpose']),
        ]);
    }

    /**
     * Verify a submitted code; on success complete the login (and mark the email
     * verified for the registration / unverified-login path).
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $challenge = $request->session()->get('otp_challenge');

        if (! $challenge || ! ($user = User::find($challenge['user_id']))) {
            return redirect()->route('login');
        }

        $purpose = $challenge['purpose'];

        if (! $this->otp->verify($user, $purpose, (string) $request->input('code'))) {
            return back()->withErrors([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        // Email is marked verified before the ban check intentionally: verifying the address
        // is a low-privilege action and must complete even if login is then denied.
        if ($purpose === 'email_verification' && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // Permanently banned: don't authenticate — show appeal on the login page instead
        if ($user->isBanned()) {
            $ban = $user->activeBan();
            $request->session()->forget('otp_challenge');
            $request->session()->put('ban_appeal', [
                'user_id' => $user->id,
                'ban_reason' => $ban?->reason,
                'ban_type' => $ban?->type,
                'has_open_appeal' => $ban?->hasOpenAppeal() ?? false,
            ]);

            return redirect()->route('login');
        }

        Auth::login($user, (bool) ($challenge['remember'] ?? false));

        $this->linkedAccounts->remember($request, $user);

        $request->session()->forget('otp_challenge');
        $request->session()->regenerate();

        $default = match (true) {
            $user->isAdmin() => route('admin.dashboard', absolute: false),
            $user->isModerator() => route('admin.reports', absolute: false),
            default => route('feed.index', absolute: false),
        };

        return redirect()->intended($default)->with('success', "Welcome, {$user->display_name}!");
    }

    /**
     * Re-issue a fresh code for the pending challenge.
     */
    public function resend(Request $request): RedirectResponse
    {
        $challenge = $request->session()->get('otp_challenge');

        if (! $challenge || ! ($user = User::find($challenge['user_id']))) {
            return redirect()->route('login');
        }

        $this->otp->issue($user, $challenge['purpose']);

        return back()->with('status', 'A new code has been sent to your email.');
    }
}
