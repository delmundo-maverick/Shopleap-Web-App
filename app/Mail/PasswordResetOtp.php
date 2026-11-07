<?php

namespace App\Mail;

use App\Support\Portal;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordResetOtp extends Mailable
{
    public function __construct(public string $otp, public string $role = 'buyer') {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your Shopleap password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp', with: [
            'otp' => $this->otp,
            'loginUrl' => Portal::url($this->role === 'logistics' ? 'logistics' : ($this->role === 'super_admin' ? 'admin' : 'main'), 'login'),
            'purpose' => 'password_reset',
            'heading' => 'Reset your password',
            'action' => 'to reset your Shopleap password',
            'footer' => 'If you did not request this code, ignore this email. Your password will not change without verification.',
        ]);
    }
}
