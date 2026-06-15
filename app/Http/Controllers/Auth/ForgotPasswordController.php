<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\MasksEmail;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * OTP-based "Forgot password" flow (three session-gated steps):
 *   1. email  → issue a 6-digit code        (create / store)
 *   2. code   → verify it                    (showCode / verifyCode / resend)
 *   3. reset  → choose a new password        (edit / update)
 *
 * The pending reset lives in the session under `password_reset`; the user is
 * never logged in here — on success they're sent to the login screen.
 */
class ForgotPasswordController extends Controller
{
    use MasksEmail;

    /** Session key holding the pending reset payload. */
    protected const SESSION_KEY = 'password_reset';

    public function __construct(protected OtpService $otp) {}

    /**
     * Step 1 — show the email-entry screen.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Step 1 — email a 6-digit reset code and hand off to the code screen.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "We can't find an account with that email."]);
        }

        $this->otp->issue($user, 'password_reset');

        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'email' => $user->email,
            'verified' => false,
        ]);

        return redirect()->route('password.code')
            ->with('status', 'We sent a 6-digit code to '.$this->maskEmail($user->email).'.');
    }

    /**
     * Step 2 — show the 6-digit code entry screen.
     */
    public function showCode(Request $request): View|RedirectResponse
    {
        if (! ($user = $this->pendingUser($request))) {
            return redirect()->route('password.request');
        }

        return view('auth.password-otp', [
            'maskedEmail' => $this->maskEmail($user->email),
            'secondsRemaining' => $this->otp->secondsUntilExpiry($user, 'password_reset'),
        ]);
    }

    /**
     * Step 2 — verify the submitted code and unlock the reset form.
     */
    public function verifyCode(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        if (! ($user = $this->pendingUser($request))) {
            return redirect()->route('password.request');
        }

        if (! $this->otp->verify($user, 'password_reset', (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'That code is invalid or has expired.']);
        }

        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'email' => $user->email,
            'verified' => true,
            'verified_at' => now()->timestamp,
        ]);

        return redirect()->route('password.reset');
    }

    /**
     * Step 2 — re-issue a fresh code for the pending reset.
     */
    public function resend(Request $request): RedirectResponse
    {
        if (! ($user = $this->pendingUser($request))) {
            return redirect()->route('password.request');
        }

        $this->otp->issue($user, 'password_reset');

        return back()->with('status', 'A new code has been sent to your email.');
    }

    /**
     * Step 3 — show the new-password form (only after the code is verified).
     */
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $this->verifiedUser($request)) {
            return $this->pendingUser($request)
                ? redirect()->route('password.code')
                : redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    /**
     * Step 3 — persist the new password and send the user to login.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (! ($user = $this->verifiedUser($request))) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Your reset session has expired. Please start again.']);
        }

        // Don't let them "reset" to the password they already have.
        if (Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors([
                'password' => 'Your new password must be different from your current password.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('login')
            ->with('status', 'Your password has been reset. You can now sign in.');
    }

    /**
     * The user behind a pending (not necessarily verified) reset, or null.
     */
    protected function pendingUser(Request $request): ?User
    {
        $state = $request->session()->get(self::SESSION_KEY);

        if (! $state || empty($state['user_id'])) {
            return null;
        }

        return User::find($state['user_id']);
    }

    /**
     * The user behind a verified, still-fresh reset (code verified within the
     * OTP TTL), or null.
     */
    protected function verifiedUser(Request $request): ?User
    {
        $state = $request->session()->get(self::SESSION_KEY);

        if (! $state || empty($state['verified']) || empty($state['verified_at'])) {
            return null;
        }

        if ($state['verified_at'] < now()->subMinutes(OtpService::TTL_MINUTES)->timestamp) {
            return null;
        }

        return $this->pendingUser($request);
    }
}
