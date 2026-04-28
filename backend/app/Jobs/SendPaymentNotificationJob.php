<?php

namespace App\Jobs;

use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSuccessMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public bool $success,
        public string $reason = ''
    ) {}

    public function handle(): void
    {
        $this->order->load('user');

        if (! $this->order->user?->email) {
            Log::warning('Payment notification skipped: no buyer email', [
                'order_id' => $this->order->id,
            ]);

            return;
        }

        if ($this->success) {
            Mail::to($this->order->user->email)
                ->send(new PaymentSuccessMail($this->order));

            Log::info('Payment success email sent', [
                'order_id' => $this->order->id,
                'email' => $this->order->user->email,
            ]);
        } else {
            Mail::to($this->order->user->email)
                ->send(new PaymentFailedMail($this->order, $this->reason));

            Log::info('Payment failed email sent', [
                'order_id' => $this->order->id,
                'email' => $this->order->user->email,
                'reason' => $this->reason,
            ]);
        }
    }
}
