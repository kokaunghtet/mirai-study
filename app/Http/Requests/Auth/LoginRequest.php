<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Verify the request's credentials WITHOUT logging the user in, returning the
     * matched user. The caller decides whether to grant a session immediately or
     * route through an OTP challenge (email verification / 2FA) first.
     *
     * @throws ValidationException
     */
    public function authenticateCredentials(): User
    {
        $this->ensureIsNotRateLimited();

        // Allow signing in with either a username or an email address.
        $login = (string) $this->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $password = $this->input('password');

        // First, try normal authentication (works for active users).
        if (Auth::validate([$field => $login, 'password' => $password])) {
            RateLimiter::clear($this->throttleKey());

            return Auth::getLastAttempted();
        }

        // Normal auth failed — check for soft-deleted users with scheduled deletion.
        $deletedUser = User::withTrashed()
            ->where($field, $login)
            ->whereNotNull('deleted_at')
            ->whereNotNull('deletion_scheduled_at')
            ->first();

        if ($deletedUser && Hash::check($password, $deletedUser->password)) {
            RateLimiter::clear($this->throttleKey());

            return $deletedUser;
        }

        // No valid credentials — rate limit, then report which field was wrong.
        RateLimiter::hit($this->throttleKey());

        $user = User::where($field, $login)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'login' => trans('auth.account_not_found'),
            ]);
        }

        throw ValidationException::withMessages([
            'login_password' => trans('auth.password'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
