<?php

namespace App\Notifications;

use App\Support\Portal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $otp,
        protected string $purpose = 'registration',
        protected string $role = 'buyer',
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $reset = $this->purpose === 'reset';

        return (new MailMessage)
            ->subject(
                $reset
                    ? 'Reset your password'
                    : 'Your Verification Code'
            )
            ->view('emails.otp', [
                'loginUrl' => Portal::url(in_array($this->role, ['logistics', 'courier', 'courier_reset']) ? 'logistics' : 'main', in_array($this->role, ['courier', 'courier_reset']) ? '' : 'login'),
                'loginLabel' => in_array($this->role, ['courier', 'courier_reset']) ? 'About ShopLeap Logistics' : 'Go to Login',
                'loginHint' => in_array($this->role, ['courier', 'courier_reset']) ? 'Continue verification and sign in through the ShopLeap Rider app.' : 'Already verified, or coming back later?',
                'otp' => $this->otp,
                'purpose' => $this->purpose,
                'heading' => $reset
                    ? 'Reset your password'
                    : 'Verify your e-mail',
                'action' => $reset
                    ? 'to reset your Shopleap rider password'
                    : 'to finish your registration on Shopleap',
                'footer' => $reset
                    ? "If you didn't request this, ignore this email. Your password will not change without the code."
                    : "If you didn't request this code, you can safely ignore this email — no account will be created without it.",
            ]);
    }
}
