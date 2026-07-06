<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        // Normalise the username to lowercase before validating / storing.
        $request->merge([
            'username' => strtolower((string) $request->input('username')),
        ]);

        $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'lowercase', 'regex:/^[a-z0-9]+$/', 'unique:'.User::class],
            'display_name' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'username.regex' => 'Username may only contain letters and numbers.',
        ]);

        // Transaction ensures the user row is rolled back if OTP email delivery fails.
        try {
            $user = DB::transaction(function () use ($request, $otp) {
                $user = User::create([
                    'username' => $request->username,
                    'display_name' => $request->display_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                event(new Registered($user));

                // Don't sign the user in yet — email must be verified first. Issue a code and
                // hand off to the shared OTP challenge screen (see OtpChallengeController).
                $otp->issue($user, 'email_verification');

                return $user;
            });
        } catch (TransportExceptionInterface) {
            throw ValidationException::withMessages([
                'email' => 'Could not send verification email. Please check your email address or try again later.',
            ]);
        }

        $request->session()->put('otp_challenge', [
            'user_id' => $user->id,
            'purpose' => 'email_verification',
            'remember' => false,
        ]);

        return redirect()->route('otp.challenge');
    }
}
