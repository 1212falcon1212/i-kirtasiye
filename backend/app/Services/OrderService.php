<?php

namespace App\Services;

use App\Events\OrderCreated;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use App\Models\SubOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sepetten sipariş oluşturma ve sipariş yaşam döngüsü işlemleri.
 *
 * Komisyon formülü (T0 - 2026-05, kullanıcı kuralı):
 *   Her sipariş kalemi için:
 *     vatRate     = item.product.category.vat_rate ?? config('commission.default_vat_rate')
 *     totalPrice  = item.unit_price * item.quantity (KDV dahil)
 *     netPrice    = totalPrice / (1 + vatRate / 100)
 *
 *     commission  = totalPrice * commission_percentage / 100   (varsayılan %10)
 *     flatFee     = flat_service_fee / sellerItemCount         (her satıcı bağımsız ₺50)
 *     withholding = netPrice * withholding_tax_rate / 100      (varsayılan %1, KDV hariç)
 *
 *     sellerPayout = totalPrice - commission - flatFee - withholding
 *
 * Sabit hizmet bedeli her satıcı için bağımsız uygulanır; satıcının
 * kalem sayısına eşit bölünür. Yüzdelik komisyon her kaleme ayrı uygulanır.
 */
class OrderService
{
    protected ShippingFeeResolver $shippingResolver;

    public function __construct(
        protected CartService $cartService,
        protected ?FeeCalculationService $feeCalculator = null,
        ?ShippingFeeResolver $shippingResolver = null,
    ) {
        $this->feeCalculator = $feeCalculator ?? app(FeeCalculationService::class);
        $this->shippingResolver = $shippingResolver ?? app(ShippingFeeResolver::class);
    }

    /**
     * Create an order from cart
     */
    public function createFromCart(
        Cart $cart,
        array $shippingAddress,
        ?string $notes = null,
        ?string $shippingProvider = null,
        float $shippingCost = 0,
        string $paymentMethod = 'credit_card'
    ): Order {
        // Validate cart first
        $issues = $this->cartService->validateCart($cart);
        $criticalIssues = array_filter($issues, fn ($i) => in_array($i['type'], ['unavailable', 'stock']));

        if (! empty($criticalIssues)) {
            throw new \Exception('Sepetinizde düzeltilmesi gereken sorunlar var.');
        }

        // Sync prices to current values
        $this->cartService->syncPrices($cart);
        $cart->refresh();
        $cart->load(['items.product.category', 'items.offer', 'items.seller']);

        if ($cart->isEmpty()) {
            throw new \Exception('Sepetiniz boş.');
        }

        // Minimum sipariş tutarı kontrolü
        $minOrderAmount = (float) Setting::getValue('commission.min_order_amount', 2000);
        $cartTotal = $cart->items->sum(fn ($i) => $i->price_at_addition * $i->quantity);
        if ($cartTotal < $minOrderAmount) {
            throw new \Exception('Minimum sipariş tutarı ₺'.number_format($minOrderAmount, 0, ',', '.')."'dir.");
        }

        return DB::transaction(function () use ($cart, $shippingAddress, $notes, $shippingProvider, $paymentMethod) {
            $orderNumber = $this->generateOrderNumber();
            $subtotal = 0;
            $totalCommission = 0;

            $rates = $this->feeCalculator->getRates();

            // Satıcı bazlı kalem sayısı + alt toplam:
            //  - Kalem sayısı: sabit hizmet bedelinin satıcıdaki kalemlere
            //    eşit dağıtılması için.
            //  - Alt toplam: satıcı bazlı kargo ücretini ve ücretsiz kargo
            //    eşiğini değerlendirmek için.
            $sellerItemCounts = [];
            $sellerSubtotals = [];
            foreach ($cart->items as $item) {
                $sid = $item->seller_id;
                $sellerItemCounts[$sid] = ($sellerItemCounts[$sid] ?? 0) + 1;
                $sellerSubtotals[$sid] = ($sellerSubtotals[$sid] ?? 0)
                    + ((float) $item->price_at_addition * $item->quantity);
            }

            // Her satıcı için kargo ücretini çöz (override > global default).
            $sellerShipping = $this->shippingResolver->resolveForCart(
                collect($sellerSubtotals)->map(fn ($sub, $sid) => [
                    'seller_id' => (int) $sid,
                    'subtotal' => (float) $sub,
                ])->values()->all()
            );

            // Toplam kargo: satıcılar arası toplam. Müşterinin gönderdiği
            // shippingCost backwards-compat amaçlı kabul edilir; artık
            // sunucu satıcı bazlı kuralla yeniden hesaplar.
            $resolvedShippingCost = $sellerShipping['total'];

            // Calculate totals
            $orderItemsData = [];
            foreach ($cart->items as $item) {
                $unitPrice = (float) $item->price_at_addition;
                $quantity = $item->quantity;
                $totalPrice = $unitPrice * $quantity;

                // Sabit hizmet bedeli payı (satıcıdaki item sayısına bölünür).
                $appliesFlatFee = $rates['service_fee_enabled']
                    && in_array($rates['fee_mode'], ['flat', 'combined'], true);
                $flatFeeShare = $appliesFlatFee
                    ? $rates['flat_service_fee'] / $sellerItemCounts[$item->seller_id]
                    : 0.0;

                // Satıcı bazlı kargo payı: o satıcının toplam kargosu / kalem sayısı.
                $sellerShipFee = $sellerShipping['per_seller'][$item->seller_id]['fee'] ?? 0.0;
                $shippingShare = $sellerItemCounts[$item->seller_id] > 0
                    ? $sellerShipFee / $sellerItemCounts[$item->seller_id]
                    : 0.0;

                // KDV oranı: önce kategori.vat_rate, yoksa config('commission.default_vat_rate').
                $vatRate = $item->product?->category?->vat_rate !== null
                    ? (float) $item->product->category->vat_rate
                    : (float) $rates['default_vat_rate'];

                // fee_mode='category' ise kategorinin kendi komisyon oranını kullan.
                $categoryRate = $rates['fee_mode'] === 'category'
                    ? (float) ($item->product?->category?->commission_rate ?? 0)
                    : null;

                $fees = $this->feeCalculator->calculateFees(
                    totalPrice: $totalPrice,
                    flatFeeShare: $flatFeeShare,
                    shippingCostShare: $shippingShare,
                    categoryCommissionRate: $categoryRate,
                    vatRate: $vatRate,
                );

                // commission_amount kolonu satıcıdan toplam kesinti payını tutar:
                // (yüzdelik komisyon + sabit hizmet bedeli payı). Stopaj ve kargo
                // payı ayrı kolonlarda izlenir. Net hakediş = total_price - tüm kesintiler.
                $commissionAmount = $fees['service_fee_amount'];
                $sellerPayoutAmount = $fees['net_seller_amount'];

                $subtotal += $totalPrice;
                $totalCommission += $commissionAmount;

                $orderItemsData[] = [
                    'product_id' => $item->product_id,
                    'offer_id' => $item->offer_id,
                    'seller_id' => $item->seller_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'commission_rate' => $fees['commission_rate'],
                    'commission_amount' => $commissionAmount,
                    'withholding_tax' => $fees['withholding_tax'],
                    'shipping_cost_share' => $fees['shipping_cost_share'],
                    'seller_payout_amount' => $sellerPayoutAmount,
                    'net_seller_amount' => $sellerPayoutAmount,
                ];

                // Decrease stock
                if (! $item->offer->decreaseStock($quantity)) {
                    throw new \Exception("Stok yetersiz: {$item->product->name}");
                }
            }

            // Toplam kargo: satıcı bazlı override > global default kuralıyla
            // sunucu tarafından yeniden hesaplanır. Frontend'in gönderdiği
            // shippingCost değeri yalnızca legacy/diagnostic amaçlıdır.
            $finalShippingCost = $resolvedShippingCost;
            $totalAmount = $subtotal + $finalShippingCost;

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $cart->user_id,
                'subtotal' => $subtotal,
                'total_commission' => $totalCommission,
                'total_amount' => $totalAmount,
                'shipping_cost' => $finalShippingCost,
                'shipping_provider' => $shippingProvider,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'payment_status' => 'pending',
                'shipping_address' => $shippingAddress,
                'notes' => $notes,
            ]);

            // Create sub-orders grouped by seller, then create order items under each
            $itemsBySeller = collect($orderItemsData)->groupBy('seller_id');

            foreach ($itemsBySeller as $sellerId => $sellerItems) {
                $subOrder = SubOrder::create([
                    'order_id' => $order->id,
                    'seller_id' => $sellerId,
                    'status' => 'pending',
                    'subtotal' => $sellerItems->sum('total_price'),
                    'total_commission' => $sellerItems->sum('commission_amount'),
                    'total_payout' => $sellerItems->sum('seller_payout_amount'),
                ]);

                foreach ($sellerItems as $itemData) {
                    $order->items()->create([
                        ...$itemData,
                        'sub_order_id' => $subOrder->id,
                    ]);
                }
            }

            // Mark cart as converted
            $cart->markAsConverted();

            // Load items with relations for notifications
            $order->load('items.product', 'items.seller', 'subOrders', 'user');

            // Don't notify on order creation - notify after payment confirmed
            // app(NotificationService::class)->notifyOrderCreated($order);

            // Siparis olusturuldu event'i tetikle (e-posta vb. listener'lar dinler)
            event(new OrderCreated($order));

            return $order;
        });
    }

    /**
     * Generate unique order number
     */
    public function generateOrderNumber(): string
    {
        $prefix = 'IKR'; // i-kirtasiye
        $date = now()->format('ymd');
        $random = strtoupper(Str::random(4));
        $sequence = str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$date}{$sequence}{$random}";
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order): void
    {
        if (! $order->canBeCancelled()) {
            throw new \Exception('Bu sipariş iptal edilemez.');
        }

        DB::transaction(function () use ($order) {
            // Restore stock for each item
            foreach ($order->items as $item) {
                if ($item->offer) {
                    $item->offer->increment('stock', $item->quantity);
                    if ($item->offer->status === 'sold_out') {
                        $item->offer->update(['status' => 'active']);
                    }
                }
            }

            $order->cancel();
        });
    }

    /**
     * Get order by ID with relations
     */
    public function getOrder(int $orderId): ?Order
    {
        return Order::with(['items.product', 'items.seller', 'subOrders.seller:id,business_name,nickname,city', 'user'])
            ->find($orderId);
    }

    /**
     * Get user's orders
     */
    public function getUserOrders(int $userId, int $perPage = 10)
    {
        return Order::forUser($userId)
            ->with(['items.product', 'items.seller:id,business_name,nickname,city,role', 'subOrders.seller:id,business_name,nickname,city'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
