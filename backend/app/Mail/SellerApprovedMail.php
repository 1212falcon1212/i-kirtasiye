<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SellerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hesabınız Onaylandı - i-kirtasiye B2B Platformuna Hoş Geldiniz!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.seller-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
