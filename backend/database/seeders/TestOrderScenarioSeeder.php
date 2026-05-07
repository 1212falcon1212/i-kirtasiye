<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\FeeCalculationService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

/**
 * Yeni komisyon sistemini ve hakediş hesabını canlı verilerle test etmek için
 * 5 farklı senaryolu sipariş üretir.
 *
 *  1) Tek satıcı, eşik altı — sabit kargo + komisyon + hizmet bedeli + stopaj
 *  2) Çoklu satıcı (2) — her satıcı bağımsız hizmet bedeli ve kargo
 *  3) Tek satıcı, ücretsiz kargo eşiği üstü
 *  4) 3 satıcılı geniş sepet — hizmet bedelinin satıcı kalem sayısına dağılımı
 *  5) KDV null fallback — config(default_vat_rate) ile hesaplama
 */
class TestOrderScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureCommissionSettings();

        $retailers = User::where('role', User::ROLE_RETAILER)->get();
        if ($retailers->isEmpty()) {
            $this->command->warn('Kırtasiyeci bulunamadı. KirtasiyeUserSeeder çalıştırılmalı.');

            return;
        }

        $offers = Offer::with('product.category')
            ->where('status', Offer::STATUS_ACTIVE)
            ->where('stock', '>=', 5)
            ->get()
            ->groupBy('seller_id')
            ->filter(fn ($items) => $items->count() >= 2);

        if ($offers->count() < 3) {
            $this->command->warn('Senaryolar için en az 3 farklı satıcının teklifi gerekli.');

            return;
        }

        $sellerIds = $offers->keys()->take(3)->all();
        $fees = app(FeeCalculationService::class);

        $createdSummaries = [];

        // Senaryo 1: Tek satıcı, eşik altı
        $createdSummaries[] = $this->makeOrder(
            label: 'S1-TekSatici-EsikAlti',
            retailer: $retailers->random(),
            sellerOffers: [
                $sellerIds[0] => [['offer' => $offers[$sellerIds[0]][0], 'quantity' => 2]],
            ],
            fees: $fees,
            status: 'delivered',
        );

        // Senaryo 2: 2 satıcı, eşik altı, kargo bedeli her satıcıdan ayrı
        $createdSummaries[] = $this->makeOrder(
            label: 'S2-CokluSatici-2',
            retailer: $retailers->random(),
            sellerOffers: [
                $sellerIds[0] => [['offer' => $offers[$sellerIds[0]][0], 'quantity' => 1]],
                $sellerIds[1] => [['offer' => $offers[$sellerIds[1]][0], 'quantity' => 1]],
            ],
            fees: $fees,
            status: 'shipped',
        );

        // Senaryo 3: Tek satıcı, ücretsiz kargo eşiği üstü
        // Eşik 2500₺; pahalı bir teklifi yüksek miktarla seçelim
        $bigOffer = $offers[$sellerIds[0]]->sortByDesc('price')->first();
        $bigQty = max(1, (int) ceil(3000 / max(1, (float) $bigOffer->price)));
        $createdSummaries[] = $this->makeOrder(
            label: 'S3-UcretsizKargo',
            retailer: $retailers->random(),
            sellerOffers: [
                $sellerIds[0] => [['offer' => $bigOffer, 'quantity' => $bigQty]],
            ],
            fees: $fees,
            status: 'delivered',
        );

        // Senaryo 4: 3 satıcı, her satıcıdan 2 kalem (toplam 6 kalem)
        $createdSummaries[] = $this->makeOrder(
            label: 'S4-CokluSatici-3-FazlaKalem',
            retailer: $retailers->random(),
            sellerOffers: [
                $sellerIds[0] => [
                    ['offer' => $offers[$sellerIds[0]][0], 'quantity' => 1],
                    ['offer' => $offers[$sellerIds[0]][1], 'quantity' => 2],
                ],
                $sellerIds[1] => [
                    ['offer' => $offers[$sellerIds[1]][0], 'quantity' => 3],
                    ['offer' => $offers[$sellerIds[1]][1], 'quantity' => 1],
                ],
                $sellerIds[2] => [
                    ['offer' => $offers[$sellerIds[2]][0], 'quantity' => 2],
                    ['offer' => $offers[$sellerIds[2]][1], 'quantity' => 1],
                ],
            ],
            fees: $fees,
            status: 'pending',
        );

        // Senaryo 5: KDV null kategori (varsa) — fallback %20
        $nullVatOffer = Offer::with('product.category')
            ->where('status', Offer::STATUS_ACTIVE)
            ->where('stock', '>=', 3)
            ->whereHas('product.category', fn ($q) => $q->whereNull('vat_rate'))
            ->first();

        if ($nullVatOffer !== null) {
            $createdSummaries[] = $this->makeOrder(
                label: 'S5-KDV-Null-Fallback',
                retailer: $retailers->random(),
                sellerOffers: [
                    $nullVatOffer->seller_id => [['offer' => $nullVatOffer, 'quantity' => 2]],
                ],
                fees: $fees,
                status: 'pending',
            );
        } else {
            $this->command->warn('S5: KDV null kategorisi olan ürün bulunamadı, atlandı.');
        }

        $this->command->info('✓ '.count($createdSummaries).' senaryo siparişi eklendi.');
        foreach ($createdSummaries as $row) {
            $this->command->line(sprintf(
                '  • #%d %s | subtotal: ₺%s, kargo: ₺%s, komisyon: ₺%s, stopaj: ₺%s, hakediş: ₺%s',
                $row['order_id'],
                str_pad($row['label'], 36),
                number_format($row['subtotal'], 2),
                number_format($row['shipping'], 2),
                number_format($row['commission'], 2),
                number_format($row['withholding'], 2),
                number_format($row['net_seller'], 2),
            ));
        }
    }

    /**
     * Komisyon ayarları yoksa varsayılan değerlerle doldur.
     */
    protected function ensureCommissionSettings(): void
    {
        $defaults = [
            'commission.enabled' => true,
            'commission.fee_mode' => 'combined',
            'commission.commission_percentage' => 10,
            'commission.flat_service_fee' => 50,
            'commission.withholding_tax_rate' => 1.00,
            'commission.default_vat_rate' => 20,
            'commission.min_order_amount' => 0,
            'shipping.flat_rate' => 29.90,
            'shipping.free_threshold' => 2500,
        ];

        foreach ($defaults as $key => $value) {
            if (Setting::getValue($key, null) === null) {
                $group = str_starts_with($key, 'shipping.') ? 'shipping' : 'commission';
                Setting::setValue($key, $value, $group);
            }
        }
    }

    /**
     * @param  array<int, array<int, array{offer: Offer, quantity: int}>>  $sellerOffers
     * @return array{order_id: int, label: string, subtotal: float, shipping: float, commission: float, withholding: float, net_seller: float}
     */
    protected function makeOrder(
        string $label,
        User $retailer,
        array $sellerOffers,
        FeeCalculationService $fees,
        string $status,
    ): array {
        $subtotal = 0.0;
        $items = [];

        foreach ($sellerOffers as $sellerId => $rows) {
            foreach ($rows as $row) {
                /** @var Offer $offer */
                $offer = $row['offer'];
                $quantity = (int) $row['quantity'];
                $unitPrice = (float) $offer->price;
                $totalPrice = round($unitPrice * $quantity, 2);
                $subtotal += $totalPrice;

                $items[] = [
                    'offer' => $offer,
                    'seller_id' => (int) $sellerId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'commission_rate' => (float) ($offer->product?->category?->commission_rate ?? 10.0),
                ];
            }
        }

        $statusFields = match ($status) {
            'shipped' => ['payment_status' => 'paid', 'shipping_status' => 'shipped', 'shipped_at' => now()->subDays(2)],
            'delivered' => [
                'payment_status' => 'paid',
                'shipping_status' => 'delivered',
                'shipped_at' => now()->subDays(7),
                'delivered_at' => now()->subDays(2),
            ],
            default => ['payment_status' => 'pending', 'shipping_status' => 'pending'],
        };

        $order = Order::create(array_merge([
            'order_number' => 'IKR'.now()->format('ymdHis').strtoupper(substr(md5($label.uniqid()), 0, 4)),
            'user_id' => $retailer->id,
            'subtotal' => $subtotal,
            'total_commission' => 0,
            'total_amount' => $subtotal,
            'shipping_cost' => 0,
            'shipping_address' => [
                'business_name' => $retailer->business_name,
                'phone' => $retailer->phone,
                'address' => $retailer->address,
                'city' => $retailer->city,
                'district' => $retailer->district,
            ],
            'payment_method' => 'card',
            'status' => $status,
        ], $statusFields));

        foreach ($items as $row) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $row['offer']->product_id,
                'offer_id' => $row['offer']->id,
                'seller_id' => $row['seller_id'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'total_price' => $row['total_price'],
                'commission_rate' => $row['commission_rate'],
                'commission_amount' => 0,
                'marketplace_fee' => 0,
                'withholding_tax' => 0,
                'shipping_cost_share' => 0,
                'net_seller_amount' => $row['total_price'],
                'seller_payout_amount' => $row['total_price'],
            ]);
        }

        $order->load('items.seller');
        $totals = $fees->applyFeesToOrder($order);

        $shippingTotal = (float) $totals['total_shipping_share'];

        $order->update([
            'subtotal' => $subtotal,
            'shipping_cost' => round($shippingTotal, 2),
            'total_commission' => round((float) $totals['total_commission'], 2),
            'total_amount' => round($subtotal + $shippingTotal, 2),
        ]);

        $this->syncWallets($order, $status);

        return [
            'order_id' => $order->id,
            'label' => $label,
            'subtotal' => $subtotal,
            'shipping' => $shippingTotal,
            'commission' => (float) $totals['total_commission'],
            'withholding' => (float) $totals['total_withholding_tax'],
            'net_seller' => (float) $totals['total_net_seller'],
        ];
    }

    /**
     * Ödenen siparişleri satıcı cüzdanına yansıt; teslim edilenlerde pending → available release.
     */
    protected function syncWallets(Order $order, string $status): void
    {
        if (! in_array($order->payment_status, ['paid'], true)) {
            return;
        }

        $wallets = app(WalletService::class);

        $bySeller = $order->items->groupBy('seller_id');
        foreach ($bySeller as $sellerId => $items) {
            $seller = $items->first()->seller;
            if ($seller === null) {
                continue;
            }

            $sale = (float) $items->sum('total_price');
            $commission = (float) $items->sum('commission_amount');
            $shipping = (float) $items->sum('shipping_cost_share');
            $withholding = (float) $items->sum('withholding_tax');

            $wallets->addOrderEarnings(
                seller: $seller,
                order: $order,
                saleAmount: $sale,
                commission: $commission,
                shippingCost: $shipping,
                withholdingTax: $withholding,
            );

            if ($order->shipping_status === 'delivered') {
                $wallets->releasePendingBalance($seller, $order);
            }
        }
    }
}
