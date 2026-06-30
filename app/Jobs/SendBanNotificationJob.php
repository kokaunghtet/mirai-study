<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserBan;
use App\Notifications\UserPermanentlyBannedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBanNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public UserBan $ban,
    ) {}

    public function handle(): void
    {
        $this->user->notify(new UserPermanentlyBannedNotification($this->ban));
    }
}
