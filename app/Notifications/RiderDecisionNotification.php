<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RiderDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected bool $approved,
        protected string $name,
        protected string $hubName,
        protected ?string $reason = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->approved ? 'Your rider application was approved' : 'Update on your rider application')
            ->view('emails.rider-decision', [
                'approved' => $this->approved,
                'name' => $this->name,
                'hub' => $this->hubName,
                'reason' => $this->reason,
            ]);
    }
}
