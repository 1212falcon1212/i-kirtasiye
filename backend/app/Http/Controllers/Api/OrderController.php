<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SubOrder;
use App\Models\User;
use App\Services\CartService;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\RefundService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;

    protected CartService $cartService;

    protected WalletService $walletService;

    protected NotificationService $notificationService;

    protected RefundService $refundService;

    public function __construct(
        OrderService $orderService,
        CartService $cartService,
        WalletService $walletService,
        NotificationService $notificationService,
        RefundService $refundService
    ) {
        $this->orderService = $orderService;
        $this->cartService = $cartService;
        $this->walletService = $walletService;
        $this->notificationService = $notificationService;
        $this->refundService = $refundService;
    }

    /**
     * Get user's orders (as buyer)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $orders = $this->orderService->getUserOrders($request->user()->id, $perPage);

        return response()->json([
            'orders' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Get seller's orders (orders where user is a seller) — uses sub_orders
     */
    public function sellerOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $status = $request->input('status');

        $query = SubOrder::where('seller_id', $user->id)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid');
            })
            ->with([
                'order.user:id,business_name,nickname,email,phone,role',
                'order:id,order_number,payment_status,shipping_address,created_at',
                'items.product',
            ]);

        if ($status) {
            $query->where('status', $status);
        }

        $subOrders = $query->orderByDesc('created_at')->paginate($perPage);

        $ordersData = $subOrders->getCollection()->map(function ($subOrder) {
            $order = $subOrder->order;
            $isPending = $subOrder->status === 'pending';

            return [
                'id' => $order->id,
                'sub_order_id' => $subOrder->id,
                'order_number' => $order->order_number,
                'status' => $subOrder->status,
                'status_label' => $subOrder->status_label,
                'shipping_status' => $subOrder->shipping_status,
                'tracking_number' => $subOrder->tracking_number,
                'buyer' => $isPending ? null : $order->user,
                'shipping_address' => $isPending ? null : $order->shipping_address,
                'items' => $subOrder->items,
                'seller_total' => $subOrder->subtotal,
                'seller_commission' => $subOrder->total_commission,
                'seller_payout' => $subOrder->total_payout,
                'created_at' => $order->created_at,
            ];
        });

        return response()->json([
            'orders' => $ordersData,
            'pagination' => [
                'current_page' => $subOrders->currentPage(),
                'last_page' => $subOrders->lastPage(),
                'per_page' => $subOrders->perPage(),
                'total' => $subOrders->total(),
            ],
        ]);
    }

    /**
     * Get single order details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id);

        if (! $order) {
            return response()->json(['message' => 'Sipariş bulunamadı.'], 404);
        }

        $this->authorize('view', $order);

        $user = $request->user();
        $isBuyer = $order->user_id === $user->id;
        $isSeller = $order->items->contains('seller_id', $user->id);

        // Get invoice for buyer (first seller invoice for this order)
        $invoice = null;
        if ($isBuyer) {
            $invoiceModel = $order->invoices()->where('type', 'seller')->first();
            if ($invoiceModel) {
                $invoice = [
                    'id' => $invoiceModel->id,
                    'invoice_number' => $invoiceModel->invoice_number,
                    'status' => $invoiceModel->status,
                    'status_label' => $invoiceModel->getStatusLabel(),
                    'total_amount' => $invoiceModel->total_amount,
                    'formatted_total' => $invoiceModel->formatted_total,
                    'pdf_path' => $invoiceModel->pdf_path,
                    'created_at' => $invoiceModel->created_at->format('d.m.Y H:i'),
                ];
            }
        }

        $orderData = $order->toArray();
        $orderData['invoice'] = $invoice;
        $orderData['buyer_confirmed_at'] = $order->buyer_confirmed_at;

        // Build sub_orders data for the response
        $order->load('subOrders.seller:id,business_name,nickname,city', 'subOrders.items.product');
        // Load invoices per seller for this order
        $invoicesBySeller = $order->invoices()
            ->where('type', 'seller')
            ->get()
            ->keyBy('seller_id');

        $orderData['sub_orders'] = $order->subOrders->map(function ($subOrder) use ($invoicesBySeller) {
            $data = [
                'id' => $subOrder->id,
                'seller_id' => $subOrder->seller_id,
                'seller_name' => $subOrder->seller->nickname ?? $subOrder->seller->business_name ?? 'Satıcı',
                'status' => $subOrder->status,
                'status_label' => $subOrder->status_label,
                'shipped_at' => $subOrder->shipped_at?->toISOString(),
                'delivered_at' => $subOrder->delivered_at?->toISOString(),
                'buyer_confirmed_at' => $subOrder->buyer_confirmed_at?->toISOString(),
                'tracking_number' => $subOrder->tracking_number,
                'shipping_provider' => $subOrder->shipping_provider,
                'subtotal' => $subOrder->subtotal,
                'item_count' => $subOrder->items->count(),
                'invoice' => null,
            ];

            // Attach seller's invoice for this order if exists
            $inv = $invoicesBySeller->get($subOrder->seller_id);
            if ($inv) {
                $data['invoice'] = [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'status' => $inv->status,
                    'status_label' => $inv->getStatusLabel(),
                    'total_amount' => $inv->total_amount,
                    'formatted_total' => $inv->formatted_total,
                    'pdf_path' => $inv->pdf_path,
                    'created_at' => $inv->created_at->format('d.m.Y H:i'),
                ];
            }

            return $data;
        })->values();

        // Hide buyer info for sellers viewing pending orders
        if ($isSeller && ! $isBuyer && $order->status === 'pending') {
            $orderData['user'] = null;
            $orderData['shipping_address'] = null;
        }

        return response()->json([
            'order' => $orderData,
            'items' => $order->items,
            'items_by_seller' => $order->items_by_seller,
            'is_buyer' => $isBuyer,
            'is_seller' => $isSeller,
        ]);
    }

    /**
     * Create order from cart (checkout)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.phone' => 'required|string|max:20',
            'shipping_address.address' => 'required|string|max:500',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.district' => 'nullable|string|max:100',
            'shipping_address.postal_code' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:500',
            'shipping_provider' => 'nullable|string|max:50',
            'shipping_cost' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:credit_card,bank_transfer',
        ]);

        $cart = $this->cartService->getCart($request->user());

        if (! $cart || $cart->isEmpty()) {
            return response()->json(['message' => 'Sepetiniz boş.'], 422);
        }

        try {
            $order = $this->orderService->createFromCart(
                $cart,
                $validated['shipping_address'],
                $validated['notes'] ?? null,
                $validated['shipping_provider'] ?? null,
                $validated['shipping_cost'] ?? 0,
                $validated['payment_method'] ?? 'credit_card'
            );

            return response()->json([
                'message' => 'Siparişiniz başarıyla oluşturuldu.',
                'order' => $order,
                'order_number' => $order->order_number,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update order status (for sellers) — operates on sub_order level
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,processing,shipped,delivered',
        ]);

        $order = Order::with('subOrders')->find($id);

        if (! $order) {
            return response()->json(['message' => 'Sipariş bulunamadı.'], 404);
        }

        $this->authorize('update', $order);

        $user = $request->user();

        // Find seller's sub_order
        $subOrder = $order->subOrders->where('seller_id', $user->id)->first();

        // Admin can update a specific sub_order or fall back to first
        if (! $subOrder && $user->isSuperAdmin()) {
            $subOrderId = $request->input('sub_order_id');
            $subOrder = $subOrderId
                ? $order->subOrders->where('id', $subOrderId)->first()
                : $order->subOrders->first();
        }

        if (! $subOrder) {
            return response()->json(['message' => 'Alt sipariş bulunamadı.'], 404);
        }

        $newStatus = $validated['status'];

        // Validate sub_order status transition
        if (! $subOrder->canTransitionTo($newStatus)) {
            return response()->json([
                'message' => 'Geçersiz durum geçişi.',
                'current_status' => $subOrder->status,
                'allowed' => SubOrder::STATUS_TRANSITIONS[$subOrder->status] ?? [],
            ], 422);
        }

        $subOrder->update([
            'status' => $newStatus,
            'shipped_at' => $newStatus === 'shipped' ? now() : $subOrder->shipped_at,
            'delivered_at' => $newStatus === 'delivered' ? now() : $subOrder->delivered_at,
        ]);

        // Sync parent order's status from sub_orders
        $order->update(['status' => $order->fresh()->overall_status]);

        // Send notification to buyer (sub_order based)
        $this->notifySubOrderStatusChange($order, $subOrder, $newStatus);

        return response()->json([
            'message' => 'Sipariş durumu güncellendi.',
            'sub_order' => $subOrder->fresh(),
            'order_status' => $order->fresh()->status,
        ]);
    }

    /**
     * Send notification for sub_order status change
     */
    protected function notifySubOrderStatusChange(Order $order, SubOrder $subOrder, string $newStatus): void
    {
        $sellerName = $subOrder->seller->nickname ?? $subOrder->seller->business_name ?? 'Satıcı';

        // In-app bildirimler (senkron)
        match ($newStatus) {
            'confirmed' => $this->notificationService->notifySubOrderConfirmed($order, $subOrder, $sellerName),
            'shipped' => $this->notificationService->notifySubOrderShipped($order, $subOrder, $sellerName),
            'delivered' => $this->notificationService->notifySubOrderDelivered($order, $subOrder, $sellerName),
            default => null,
        };

        // Domain event tetikle (e-posta vb. listener'lar dinler)
        event(new OrderStatusChanged($order, $subOrder, $newStatus, $sellerName));
    }

    /**
     * Process seller earnings for a specific sub_order
     */
    protected function processSubOrderEarnings(Order $order, SubOrder $subOrder): void
    {
        $seller = User::find($subOrder->seller_id);
        if (! $seller) {
            return;
        }

        foreach ($subOrder->items as $item) {
            $this->walletService->addOrderEarnings(
                $seller,
                $order,
                (float) $item->total_price,
                (float) $item->commission_amount,
                (float) $item->shipping_cost_share,
                $item->id,
                $subOrder->id,
                (float) $item->withholding_tax,
            );
        }
    }

    /**
     * Buyer confirms delivery — confirms a specific sub_order or all delivered sub_orders
     */
    public function confirmDelivery(Request $request, int $id): JsonResponse
    {
        $order = Order::with('subOrders.items')->find($id);

        if (! $order) {
            return response()->json(['message' => 'Sipariş bulunamadı.'], 404);
        }

        $this->authorize('confirmDelivery', $order);

        $user = $request->user();

        $subOrderId = $request->input('sub_order_id');

        // If a specific sub_order_id is provided, confirm only that one
        if ($subOrderId) {
            $subOrder = $order->subOrders->where('id', $subOrderId)->first();

            if (! $subOrder) {
                return response()->json(['message' => 'Alt sipariş bulunamadı.'], 404);
            }

            if ($subOrder->status !== 'delivered') {
                return response()->json(['message' => 'Bu teslimat henüz teslim edilmedi.'], 422);
            }

            if ($subOrder->buyer_confirmed_at) {
                return response()->json([
                    'message' => 'Bu teslimat zaten onaylanmış.',
                    'order' => $order->fresh(),
                ]);
            }

            $now = now();
            $subOrder->update(['buyer_confirmed_at' => $now]);

            // Process earnings for this sub_order
            if ($order->payment_status === 'paid') {
                $this->processSubOrderEarnings($order, $subOrder);
            }

            // Notify seller
            $this->notificationService->notifyBuyerConfirmedDelivery($order, [$subOrder->seller_id]);

            // Check if all active sub_orders are now confirmed → set parent order
            $allActiveConfirmed = $order->fresh()->subOrders
                ->reject(fn ($so) => $so->status === 'cancelled')
                ->every(fn ($so) => $so->buyer_confirmed_at !== null);

            if ($allActiveConfirmed) {
                $order->update(['buyer_confirmed_at' => $now]);
            }

            return response()->json([
                'message' => 'Teslimat onayınız alındı. Teşekkürler!',
                'order' => $order->fresh(),
            ]);
        }

        // No sub_order_id: confirm all delivered sub_orders (backward compat)
        $deliveredSubOrders = $order->subOrders
            ->where('status', 'delivered')
            ->whereNull('buyer_confirmed_at');

        if ($deliveredSubOrders->isEmpty()) {
            $allConfirmed = $order->subOrders->where('status', 'delivered')
                ->every(fn ($so) => $so->buyer_confirmed_at !== null);

            if ($allConfirmed) {
                return response()->json([
                    'message' => 'Teslimat zaten onaylanmış.',
                    'order' => $order->fresh(),
                ]);
            }

            return response()->json([
                'message' => 'Henüz teslim edilmiş alt sipariş yok.',
            ], 422);
        }

        $now = now();

        foreach ($deliveredSubOrders as $subOrder) {
            $subOrder->update(['buyer_confirmed_at' => $now]);
        }

        // Also set the parent order's buyer_confirmed_at if all active sub_orders are confirmed
        $allActiveDelivered = $order->fresh()->subOrders
            ->reject(fn ($so) => $so->status === 'cancelled')
            ->every(fn ($so) => $so->buyer_confirmed_at !== null);

        if ($allActiveDelivered) {
            $order->update(['buyer_confirmed_at' => $now]);
        }

        if ($order->payment_status === 'paid') {
            foreach ($deliveredSubOrders as $subOrder) {
                $this->processSubOrderEarnings($order, $subOrder);
            }
        }

        $this->notificationService->notifyBuyerConfirmedDelivery($order, $deliveredSubOrders->pluck('seller_id')->toArray());

        return response()->json([
            'message' => 'Teslimat onayınız alındı. Teşekkürler!',
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id);

        if (! $order) {
            return response()->json(['message' => 'Sipariş bulunamadı.'], 404);
        }

        $this->authorize('cancel', $order);

        $subOrderId = $request->input('sub_order_id');

        try {
            if ($subOrderId) {
                // Cancel specific sub_order only
                $subOrder = $order->subOrders->firstWhere('id', $subOrderId);
                if (! $subOrder) {
                    return response()->json(['message' => 'Alt sipariş bulunamadı.'], 404);
                }
                if (! $subOrder->canBeCancelled()) {
                    return response()->json(['message' => 'Bu teslimat iptal edilemez. Durum: '.$subOrder->status_label], 422);
                }

                // Restore stock for items in this sub_order
                $subOrderItems = $order->items->where('sub_order_id', $subOrder->id);
                foreach ($subOrderItems as $item) {
                    if ($item->offer) {
                        $item->offer->increment('stock', $item->quantity);
                        if ($item->offer->status === 'sold_out') {
                            $item->offer->update(['status' => 'active']);
                        }
                    }
                }

                $subOrder->update(['status' => 'cancelled']);

                // PayTR refund + wallet reversal
                if ($order->payment_status === 'paid') {
                    $this->refundService->processSubOrderRefund($order, $subOrder, 'cancel');
                }

                // If all sub_orders are now cancelled, cancel the parent order too
                $order->load('subOrders');
                $allCancelled = $order->subOrders->every(fn ($so) => $so->status === 'cancelled');
                if ($allCancelled) {
                    $order->update(['status' => 'cancelled']);
                    $this->notificationService->notifyOrderCancelled($order);
                }

                return response()->json([
                    'message' => 'Teslimat iptal edildi.',
                    'sub_order' => $subOrder->fresh(),
                    'order' => $order->fresh(['subOrders', 'items']),
                ]);
            }

            // Cancel entire order (backward compatible)
            $this->orderService->cancelOrder($order);

            // PayTR refund + wallet reversal for each sub-order
            if ($order->payment_status === 'paid') {
                $order->load('subOrders');
                foreach ($order->subOrders as $subOrder) {
                    $this->refundService->processSubOrderRefund($order, $subOrder, 'cancel');
                }
            }

            $this->notificationService->notifyOrderCancelled($order);

            return response()->json([
                'message' => 'Sipariş iptal edildi.',
                'order' => $order->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
