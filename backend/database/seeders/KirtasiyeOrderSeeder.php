<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 5 demo siparişi farklı durumlarda yaratır.
 */
class KirtasiyeOrderSeeder extends Seeder
{
    public function run(): void
    {
        $retailers = User::where('role', User::ROLE_RETAILER)->get();
        if ($retailers->isEmpty()) {
            $this->command->warn('Kırtasiyeci bulunamadı, sipariş seed atlandı.');
            return;
        }

        $offers = Offer::with('product')->where('status', Offer::STATUS_ACTIVE)->get();
        if ($offers->isEmpty()) {
            $this->command->warn('Aktif teklif bulunamadı, sipariş seed atlandı.');
            return;
        }

        // Statuses to create
        $orderScenarios = [
            ['status' => 'pending', 'payment_status' => 'pending', 'shipping_status' => 'pending'],
            ['status' => 'shipped', 'payment_status' => 'paid', 'shipping_status' => 'shipped', 'shipped_at' => now()->subDays(2)],
            ['status' => 'delivered', 'payment_status' => 'paid', 'shipping_status' => 'delivered', 'shipped_at' => now()->subDays(7), 'delivered_at' => now()->subDays(3)],
            ['status' => 'returned', 'payment_status' => 'refunded', 'shipping_status' => 'returned'],
            ['status' => 'pending', 'payment_status' => 'pending', 'shipping_status' => 'pending'],
        ];

        $createdCount = 0;

        foreach ($orderScenarios as $idx => $scenario) {
            $retailer = $retailers->random();
            $itemOffers = $offers->random(random_int(1, 3));

            $subtotal = 0;
            $totalCommission = 0;

            $order = Order::create(array_merge([
                'order_number' => 'IKR' . now()->format('ymd') . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                'user_id' => $retailer->id,
                'subtotal' => 0,
                'total_commission' => 0,
                'total_amount' => 0,
                'shipping_cost' => 25.00,
                'shipping_address' => [
                    'business_name' => $retailer->business_name,
                    'phone' => $retailer->phone,
                    'address' => $retailer->address,
                    'city' => $retailer->city,
                    'district' => $retailer->district,
                ],
                'payment_method' => 'card',
            ], $scenario));

            foreach ($itemOffers as $offer) {
                $quantity = random_int(1, 5);
                $unitPrice = (float) $offer->price;
                $totalPrice = round($unitPrice * $quantity, 2);
                $commissionRate = (float) ($offer->product?->category?->commission_rate ?? 12.00);
                $commissionAmount = round($totalPrice * $commissionRate / 100, 2);

                $subtotal += $totalPrice;
                $totalCommission += $commissionAmount;

                $netSellerAmount = round($totalPrice - $commissionAmount, 2);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $offer->product_id,
                    'offer_id' => $offer->id,
                    'seller_id' => $offer->seller_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'marketplace_fee' => 0,
                    'withholding_tax' => 0,
                    'shipping_cost_share' => 0,
                    'net_seller_amount' => $netSellerAmount,
                    'seller_payout_amount' => $netSellerAmount,
                ]);
            }

            $order->update([
                'subtotal' => $subtotal,
                'total_commission' => $totalCommission,
                'total_amount' => $subtotal + 25.00,
            ]);

            $createdCount++;
        }

        $this->command->info("✓ {$createdCount} demo sipariş eklendi.");
    }
}
