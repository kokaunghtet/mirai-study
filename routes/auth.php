<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\OtpChallengeController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SwitchAccountController;
use App\Http\Controllers\Auth\UsernameController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('register/username-suggestions', [UsernameController::class, 'suggestions'])
        ->name('username.suggestions');

    Route::get('register/username-available', [UsernameController::class, 'available'])
        ->name('username.available');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Google OAuth (Socialite). redirect() sends the user to Google; callback() finds or
    // creates the local user, then logs them in — Google has already verified the email,
    // so this skips the OTP challenge entirely. See GoogleController.
    Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])
        ->name('auth.google');
    Route::get('auth/google/callback', [GoogleController::class, 'callback']);

    // Password reset — OTP based (email → 6-digit code → new password).
    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.email');

    Route::get('forgot-password/code', [ForgotPasswordController::class, 'showCode'])
        ->name('password.code');

    Route::post('forgot-password/code', [ForgotPasswordController::class, 'verifyCode'])
        ->middleware('throttle:6,1')->name('password.code.verify');

    Route::post('forgot-password/resend', [ForgotPasswordController::class, 'resend'])
        ->middleware('throttle:6,1')->name('password.code.resend');

    Route::get('reset-password', [ForgotPasswordController::class, 'edit'])
        ->name('password.reset');

    Route::post('reset-password', [ForgotPasswordController::class, 'update'])
        ->name('password.store');
});

// OTP challenge (email verification at sign-up / unverified login, 2FA on login, and
// adding a second account from the sidebar switcher). Deliberately outside the `guest`
// group: an already-authenticated browser must be able to complete a pending challenge
// too (see SwitchAccountController), and the pending user lives in the session either
// way — not the auth guard — until the code is verified.
Route::get('otp', [OtpChallengeController::class, 'show'])->name('otp.challenge');
Route::post('otp', [OtpChallengeController::class, 'verify'])
    ->middleware('throttle:6,1')->name('otp.verify');
Route::post('otp/resend', [OtpChallengeController::class, 'resend'])
    ->middleware('throttle:6,1')->name('otp.resend');

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Sidebar account switcher — add another account and hop between them without
    // logging out. Guest-only /login can't be reused here since it 302s an
    // authenticated visitor away before the form is ever seen.
    Route::get('accounts/add', [SwitchAccountController::class, 'create'])
        ->name('accounts.add');
    Route::post('accounts/add', [SwitchAccountController::class, 'store'])
        ->name('accounts.add.store');
    Route::post('accounts/{user}/switch', [SwitchAccountController::class, 'switch'])
        ->name('accounts.switch');
});
