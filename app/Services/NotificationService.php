<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public static function send(
        User $recipient,
        string $type,
        string $title,
        string $content,
        ?User $sender = null,
        ?string $url = null,
    ): void {
        // Don't notify yourself
        if ($sender && $sender->id === $recipient->id) {
            return;
        }

        $notification = Notification::create([
            'user_id' => $recipient->id,
            'sender_id' => $sender?->id,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'url' => $url,
        ]);

        $notification->load('sender');

        broadcast(new NotificationCreated($notification))->toOthers();
    }
}
