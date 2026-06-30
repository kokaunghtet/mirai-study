<?php

namespace App\Notifications;

use App\Models\UserBan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserPermanentlyBannedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public UserBan $ban) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your MiraiStudy account has been permanently banned')
            ->greeting('Hi '.($notifiable->display_name ?? 'there').',')
            ->line('Your MiraiStudy account has been permanently banned.')
            ->line('Reason: '.($this->ban->reason ?: 'No reason provided.'));

        if ($notifiable->appeals()->whereHas('ban', fn ($q) => $q->where('id', $this->ban->id))->where('status', 'pending')->exists()) {
            $mail->line('You have a pending appeal that will be reviewed by our team.');
        } else {
            $mail->line('If you believe this was a mistake, you may submit an appeal through the login page.');
        }

        return $mail
            ->action('Go to MiraiStudy', url('/'))
            ->line('If you have any questions, please contact our support team.');
    }
}
