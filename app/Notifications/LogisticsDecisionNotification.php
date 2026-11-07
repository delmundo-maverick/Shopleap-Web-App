<?php

namespace App\Notifications;

use App\Support\Portal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LogisticsDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public bool $approved, public ?string $reason = null) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->approved ? 'Your ShopLeap Logistics application is approved' : 'Update your ShopLeap Logistics application')
            ->view('emails.logistics-decision', [
                'approved' => $this->approved,
                'reason' => $this->reason,
                'loginUrl' => Portal::url('logistics', 'login'),
                'applicationUrl' => Portal::route('logistics', 'logistics.application'),
            ]);
    }
}
