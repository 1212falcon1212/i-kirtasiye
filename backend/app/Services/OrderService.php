<?php

namespace App\Services;

use App\Events\OrderCreated;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use App\Models\SubOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
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

        return DB::transaction(function () use ($cart, $shippingAddress, $notes, $shippingProvider, $shippingCost, $paymentMethod) {
            $orderNumber = $this->generateOrderNumber();
            $subtotal = 0;
            $totalCommission = 0;

            // Sabit hizmet bedeli (50 TL / satıcı)
            $flatServiceFee = (float) Setting::getValue('commission.flat_service_fee', 50);
            $withholdingRate = (float) Setting::getValue('commission.withholding_tax_rate', 1.00);
            $serviceFeeEnabled = (bool) Setting::getValue('commission.enabled', true);

            // Group items by seller to distribute flat fee
            $sellerItemCounts = [];
            foreach ($cart->items as $item) {
                $sid = $item->seller_id;
                $sellerItemCounts[$sid] = ($sellerItemCounts[$sid] ?? 0) + 1;
            }

            // Calculate totals
            $orderItemsData = [];
            foreach ($cart->items as $item) {
                $unitPrice = (float) $item->price_at_addition;
                $quantity = $item->quantity;
                $totalPrice = $unitPrice * $quantity;

                // Sabit hizmet bedeli payı (satıcıdaki item sayısına bölünür)
                $feeShare = $serviceFeeEnabled
                    ? $flatServiceFee / $sellerItemCounts[$item->seller_id]
                    : 0;
                $vatRate = (float) ($item->product?->category?->vat_rate ?? 20);
                $priceExclVat = $totalPrice / (1 + $vatRate / 100);
                $withholdingTax = $priceExclVat * ($withholdingRate / 100);
                $commissionAmount = $feeShare;
                $sellerPayoutAmount = $totalPrice - $feeShare - $withholdingTax;

                $subtotal += $totalPrice;
                $totalCommission += $commissionAmount;

                $orderItemsData[] = [
                    'product_id' => $item->product_id,
                    'offer_id' => $item->offer_id,
                    'seller_id' => $item->seller_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'commission_rate' => 0,
                    'commission_amount' => $commissionAmount,
                    'seller_payout_amount' => $sellerPayoutAmount,
                ];

                // Decrease stock
                if (! $item->offer->decreaseStock($quantity)) {
                    throw new \Exception("Stok yetersiz: {$item->product->name}");
                }
            }

            // Kargo ücretsiz - satıcı karşılar
            $totalAmount = $subtotal;

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $cart->user_id,
                'subtotal' => $subtotal,
                'total_commission' => $totalCommission,
                'total_amount' => $totalAmount,
                'shipping_cost' => $shippingCost,
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
