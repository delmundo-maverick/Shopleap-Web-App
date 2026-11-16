<?php

namespace App\Notifications;

use App\Models\LogisticsProfile;
use App\Support\Portal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class HubInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $inviteUrl;

    public string $hubName;

    public function __construct(LogisticsProfile $hub)
    {
        $this->hubName = $hub->business_name;
        $this->inviteUrl = Portal::url('logistics', URL::temporarySignedRoute('company.invitation.show', $hub->manager_invite_expires_at, ['hub' => $hub->public_code, 'token' => $hub->manager_invite_token], absolute: false));
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Set your ShopLeap hub manager password')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have been invited to manage '.$this->hubName.'.')
            ->action('Set your password', $this->inviteUrl)
            ->line('This single-use link expires in 48 hours. Your company can resend an invitation if it expires.');
    }
}
