<?php

namespace App\Notifications;

use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public string $purpose,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $intro] = match ($this->purpose) {
            'login_verification' => [
                'Your MiraiStudy login code',
                'Use this code to finish signing in to your MiraiStudy account.',
            ],
            'password_reset' => [
                'Your MiraiStudy password reset code',
                'Use this code to reset your MiraiStudy password.',
            ],
            default => [
                'Verify your MiraiStudy email',
                'Welcome to MiraiStudy! Use this code to verify your email address.',
            ],
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hi '.($notifiable->display_name ?? 'there').',')
            ->line($intro)
            ->line('Your 6-digit code is:')
            ->line('**'.$this->code.'**')
            ->line('This code expires in '.OtpService::TTL_MINUTES.' minutes.')
            ->line('If you didn\'t request this, you can safely ignore this email.');
    }
}
