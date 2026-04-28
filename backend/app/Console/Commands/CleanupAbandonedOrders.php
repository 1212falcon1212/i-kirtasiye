<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupAbandonedOrders extends Command
{
    protected $signature = 'orders:cleanup-abandoned {--minutes=30 : Minutes after which to cancel unpaid orders}';
    protected $description = 'Cancel orders with pending payment older than specified minutes and restore stock';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        $orders = Order::where('payment_status', 'pending')
            ->where('status', 'pending')
            ->where('payment_method', 'credit_card')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No abandoned orders found.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($orders as $order) {
            DB::transaction(function () use ($order) {
                // Restore stock
                foreach ($order->items as $item) {
                    if ($item->offer) {
                        $item->offer->increment('stock', $item->quantity);
                        if ($item->offer->status === 'sold_out') {
                            $item->offer->update(['status' => 'active']);
                        }
                    }
                }

                // Cancel order and sub-orders
                $order->update([
                    'status' => 'cancelled',
                    'payment_status' => 'expired',
                ]);

                $order->subOrders()->update(['status' => 'cancelled']);
            });

            Log::info("Abandoned order cancelled: {$order->order_number}");
            $count++;
        }

        $this->info("Cancelled {$count} abandoned orders.");
        return self::SUCCESS;
    }
}
