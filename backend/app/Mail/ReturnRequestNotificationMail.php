<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Saticiya yeni iade talebi bildirim e-postasi
 */
class ReturnRequestNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ReturnRequest $returnRequest
    ) {}

    public function envelope(): Envelope
    {
        $orderNumber = $this->returnRequest->order?->order_number ?? '';

        return new Envelope(
            subject: "Yeni Iade Talebi - #{$orderNumber}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.return-request-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
