<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManagerPasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $resetUrl, public string $hubName) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Reset your ShopLeap hub manager password')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your company requested a password reset for your manager account at '.$this->hubName.'.')
            ->action('Reset password', $this->resetUrl)
            ->line('This single-use link expires in one hour. If you do not need a new password, you can ignore this email.');
    }
}
