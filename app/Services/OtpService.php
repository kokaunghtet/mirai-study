<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Support\Carbon;

class OtpService
{
    /** How long an issued code stays valid. */
    public const TTL_MINUTES = 10;

    /**
     * Generate a fresh 6-digit code for the given purpose, invalidate any earlier
     * unused codes of that purpose, persist it, and email it to the user.
     */
    public function issue(User $user, string $purpose): Otp
    {
        // Burn any still-pending codes of the same purpose so only the newest works.
        $user->otps()
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = $user->otps()->create([
            'otp_code'   => $code,
            'purpose'    => $purpose,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        $user->notify(new OtpNotification($code, $purpose));

        return $otp;
    }

    /**
     * Check a submitted code against the user's latest valid code for the purpose.
     * On success the code is consumed (marked used) so it can't be replayed.
     */
    public function verify(User $user, string $purpose, string $code): bool
    {
        $otp = $user->otps()
            ->where('purpose', $purpose)
            ->where('otp_code', $code)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();

        if (! $otp) {
            return false;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }
}
