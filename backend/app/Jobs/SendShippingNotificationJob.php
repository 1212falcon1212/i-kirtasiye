<?php

namespace App\Jobs;

use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\SubOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendShippingNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public SubOrder $subOrder,
        public string $sellerName
    ) {}

    public function handle(): void
    {
        $this->order->load('user');

        if (! $this->order->user?->email) {
            Log::warning('Shipping notification skipped: no buyer email', [
                'order_id' => $this->order->id,
            ]);

            return;
        }

        Mail::to($this->order->user->email)
            ->send(new OrderShippedMail($this->order, $this->subOrder, $this->sellerName));

        Log::info('Shipping notification email sent', [
            'order_id' => $this->order->id,
            'sub_order_id' => $this->subOrder->id,
            'email' => $this->order->user->email,
        ]);
    }
}
