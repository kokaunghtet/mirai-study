<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public string $purpose,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isLogin = $this->purpose === 'login_verification';

        $subject = $isLogin
            ? 'Your MiraiStudy login code'
            : 'Verify your MiraiStudy email';

        $intro = $isLogin
            ? 'Use this code to finish signing in to your MiraiStudy account.'
            : 'Welcome to MiraiStudy! Use this code to verify your email address.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hi '.($notifiable->display_name ?? 'there').',')
            ->line($intro)
            ->line('Your 6-digit code is:')
            ->line('**'.$this->code.'**')
            ->line('This code expires in '.\App\Services\OtpService::TTL_MINUTES.' minutes.')
            ->line('If you didn\'t request this, you can safely ignore this email.');
    }
}
