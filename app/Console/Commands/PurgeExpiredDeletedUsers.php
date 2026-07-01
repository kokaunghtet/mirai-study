<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredDeletedUsers extends Command
{
    protected $signature = 'users:purge-deleted';

    protected $description = 'Permanently delete users whose 1-month grace period has expired';

    public function handle(): int
    {
        $expiredUsers = User::onlyTrashed()
            ->whereNotNull('deletion_scheduled_at')
            ->where('deletion_scheduled_at', '<', now())
            ->get();

        if ($expiredUsers->isEmpty()) {
            $this->info('No expired deleted users found.');

            return Command::SUCCESS;
        }

        $count = 0;

        foreach ($expiredUsers as $user) {
            // Clean up profile image from storage
            if ($user->profile_image) {
                $path = str_replace('/storage/', '', parse_url($user->profile_image, PHP_URL_PATH));
                Storage::disk('public')->delete($path);
            }

            // Permanently delete the user
            $user->forceDelete();
            $count++;

            $this->info("Purged user: {$user->username} (ID: {$user->id})");
        }

        $this->info("Successfully purged {$count} expired deleted users.");

        return Command::SUCCESS;
    }
}
