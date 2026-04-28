<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SellerRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hesap Başvurunuz Hakkında - i-kirtasiye',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.seller-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
