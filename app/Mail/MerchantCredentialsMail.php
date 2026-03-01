<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MerchantCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $storeName,
        public string $username,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Kutoot Store Login Credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.merchant-credentials',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
