<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get unique (order_id, seller_id) combinations from order_items
        $sellerOrders = DB::table('order_items')
            ->select('order_id', 'seller_id')
            ->selectRaw('SUM(total_price) as subtotal')
            ->selectRaw('SUM(commission_amount) as total_commission')
            ->selectRaw('SUM(seller_payout_amount) as total_payout')
            ->groupBy('order_id', 'seller_id')
            ->get();

        if ($sellerOrders->isEmpty()) {
            return;
        }

        // Get order data for status/shipping info
        $orderIds = $sellerOrders->pluck('order_id')->unique()->toArray();
        $orders = DB::table('orders')
            ->whereIn('id', $orderIds)
            ->get()
            ->keyBy('id');

        $now = now();

        foreach ($sellerOrders as $so) {
            $order = $orders->get($so->order_id);
            if (!$order) {
                continue;
            }

            // Create sub_order inheriting parent order's status/shipping data
            $subOrderId = DB::table('sub_orders')->insertGetId([
                'order_id' => $so->order_id,
                'seller_id' => $so->seller_id,
                'status' => $order->status,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at,
                'buyer_confirmed_at' => $order->buyer_confirmed_at ?? null,
                'subtotal' => $so->subtotal,
                'total_commission' => $so->total_commission,
                'total_payout' => $so->total_payout,
                'tracking_number' => $order->tracking_number,
                'shipping_provider' => $order->shipping_provider,
                'shipping_status' => $order->shipping_status ?? 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Link order_items to their sub_order
            DB::table('order_items')
                ->where('order_id', $so->order_id)
                ->where('seller_id', $so->seller_id)
                ->update(['sub_order_id' => $subOrderId]);
        }
    }

    public function down(): void
    {
        // Reset sub_order_id on all order_items
        DB::table('order_items')->update(['sub_order_id' => null]);
        // Delete all sub_orders
        DB::table('sub_orders')->truncate();
    }
};
