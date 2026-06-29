<?php

namespace App\Notifications;

use App\Models\Appeal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppealApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appeal $appeal) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your MiraiStudy account has been reinstated')
            ->greeting('Hi '.($notifiable->display_name ?? 'there').',')
            ->line('Good news — your appeal has been reviewed and approved.')
            ->line('Your account is now active again and you can log in normally.')
            ->action('Go to MiraiStudy', url('/'))
            ->line('If you have any questions, please contact our support team.');
    }
}
