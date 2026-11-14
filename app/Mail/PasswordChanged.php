<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordChanged extends Mailable
{
    public function __construct(public string $role = 'buyer') {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Shopleap '.($this->role === 'super_admin' ? 'Admin' : ucfirst($this->role)).' account password was changed');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-changed');
    }
}
