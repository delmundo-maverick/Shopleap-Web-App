<?php

namespace App\Notifications;

use App\Support\Portal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public string $role, public bool $approved, public ?string $reason = null) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->approved ? 'Your ShopLeap registration is approved' : 'Update on your ShopLeap registration')
            ->view('emails.registration-decision', [
                'name' => $notifiable->name,
                'role' => $this->role,
                'approved' => $this->approved,
                'reason' => $this->reason,
                'loginUrl' => Portal::url('main', $this->approved ? 'login' : ''),
            ]);
    }
}
