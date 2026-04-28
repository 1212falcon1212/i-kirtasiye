<?php

namespace App\Jobs;

use App\Mail\NewOrderForSellerMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function handle(): void
    {
        $this->order->load('user', 'items.product', 'subOrders.seller', 'subOrders.items');

        // Aliciya siparis onay e-postasi gonder
        if ($this->order->user?->email) {
            Mail::to($this->order->user->email)
                ->send(new OrderConfirmationMail($this->order));

            Log::info('Order confirmation email sent to buyer', [
                'order_id' => $this->order->id,
                'email' => $this->order->user->email,
            ]);
        }

        // Her satici icin yeni siparis e-postasi gonder
        foreach ($this->order->subOrders as $subOrder) {
            $seller = $subOrder->seller;

            if ($seller?->email) {
                Mail::to($seller->email)
                    ->send(new NewOrderForSellerMail($this->order, $subOrder));

                Log::info('New order email sent to seller', [
                    'order_id' => $this->order->id,
                    'seller_id' => $seller->id,
                    'email' => $seller->email,
                ]);
            }
        }
    }
}
