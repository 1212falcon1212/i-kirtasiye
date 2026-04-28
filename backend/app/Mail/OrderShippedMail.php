<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\SubOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public SubOrder $subOrder,
        public string $sellerName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Siparisiniz Kargoya Verildi - #{$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-shipped',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
