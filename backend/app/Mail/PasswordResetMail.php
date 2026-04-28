<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Şifre sıfırlama e-postası
 */
class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Şifre sıfırlama URL'i
     */
    public string $resetUrl;

    public function __construct(
        public User $user,
        public string $token
    ) {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $this->resetUrl = $frontendUrl.'/reset-password?token='.$token.'&email='.urlencode($user->email);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Şifre Sıfırlama Talebi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
