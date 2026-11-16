<?php

namespace App\Notifications;

use App\Support\Portal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyDecisionNotification extends Notification implements ShouldQueue
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
        $mail = (new MailMessage)->subject($this->approved ? 'Your ShopLeap logistics company is approved' : 'Your ShopLeap logistics company application was rejected')
            ->line($this->approved ? 'Your company has been approved. You can now create and manage hubs.' : 'Your company application was rejected.')
            ->action('View your company account', Portal::route('logistics', 'company.dashboard'));
        if (! $this->approved) {
            $mail->line('Reason: '.$this->reason);
        }

        return $mail;
    }
}
