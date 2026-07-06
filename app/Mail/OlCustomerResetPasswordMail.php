<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OlCustomerResetPasswordMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $name,
        public string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Setel Ulang Password | Bakpia 3 Generasi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
        );
    }
}
