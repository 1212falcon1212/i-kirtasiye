<?php

namespace App\Http\Controllers\Api;

use App\Events\ReturnRequestCreated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\SubOrder;
use App\Services\NotificationService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReturnRequestController extends Controller
{
    /**
     * Get buyer's return requests
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);

        $requests = ReturnRequest::forBuyer($user->id)
            ->with(['order:id,order_number,status', 'orderItem.product:id,name,image'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $requests->map(fn ($r) => $this->formatReturnRequest($r)),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Get seller's received return requests
     */
    public function sellerRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $status = $request->input('status');

        $query = ReturnRequest::forSeller($user->id)
            ->with([
                'order:id,order_number,status',
                'orderItem.product:id,name,image',
                'buyer:id,business_name,email,phone',
            ]);

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $requests->map(fn ($r) => $this->formatReturnRequest($r, true)),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
            'pending_count' => ReturnRequest::forSeller($user->id)->pending()->count(),
        ]);
    }

    /**
     * Get return requests for a specific order (for seller view)
     */
    public function orderRequests(Order $order, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is buyer or seller of this order
        $isBuyer = $order->user_id === $user->id;
        $isSeller = $order->items()->where('seller_id', $user->id)->exists();
        if (! $isBuyer && ! $isSeller && ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        $query = ReturnRequest::where('order_id', $order->id);
        // Sellers only see their own returns, buyers see all returns for their order
        if ($isSeller && ! $isBuyer) {
            $query->where('seller_id', $user->id);
        }

        $requests = $query
            ->with(['orderItem.product:id,name,image', 'buyer:id,business_name,email,phone'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(fn ($r) => $this->formatReturnRequest($r, $isSeller)),
        ]);
    }

    /**
     * Create a new return request (buyer action)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'sub_order_id' => 'nullable|exists:sub_orders,id',
            'order_item_id' => 'nullable|exists:order_items,id',
            'reason' => 'required|in:wrong_product,damaged,not_as_described,quality_issue,expired,changed_mind,other',
            'reason_detail' => 'nullable|string|max:1000',
            'quantity' => 'nullable|integer|min:1',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
        ]);

        $user = $request->user();
        $order = Order::with(['items', 'subOrders'])->findOrFail($validated['order_id']);

        // Check if user is the buyer
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Bu sipariş size ait değil.',
            ], 403);
        }

        // If sub_order_id provided, check sub_order status instead of parent order
        $subOrder = null;
        if (! empty($validated['sub_order_id'])) {
            $subOrder = $order->subOrders->firstWhere('id', $validated['sub_order_id']);
            if (! $subOrder) {
                return response()->json([
                    'success' => false,
                    'error' => 'Alt sipariş bulunamadı.',
                ], 400);
            }
            if (! in_array($subOrder->status, ['shipped', 'delivered'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bu teslimat için iade talebi oluşturulamaz. Durum: '.$subOrder->status_label,
                ], 400);
            }
        } else {
            // Fallback: check parent order status
            if (! in_array($order->status, ['shipped', 'delivered'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bu sipariş için iade talebi oluşturulamaz. Sipariş durumu: '.$order->status_label,
                ], 400);
            }
        }

        // Determine seller and refund amount
        $orderItem = null;
        $sellerId = null;
        $refundAmount = 0;

        if (! empty($validated['order_item_id'])) {
            $orderItem = $order->items->firstWhere('id', $validated['order_item_id']);
            if (! $orderItem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ürün bu siparişe ait değil.',
                ], 400);
            }
            $sellerId = $orderItem->seller_id;
            $quantity = $validated['quantity'] ?? $orderItem->quantity;
            $refundAmount = $orderItem->unit_price * $quantity;
        } elseif ($subOrder) {
            // Return entire sub_order (all items from this seller)
            $sellerId = $subOrder->seller_id;
            $refundAmount = $order->items->where('seller_id', $sellerId)->sum('total_price');
        } else {
            // Return entire order - get first seller
            $firstItem = $order->items->first();
            $sellerId = $firstItem->seller_id;
            $refundAmount = $order->items->where('seller_id', $sellerId)->sum('total_price');
        }

        // Check if a pending return request already exists
        $existingRequest = ReturnRequest::where('order_id', $order->id)
            ->where('buyer_id', $user->id)
            ->where('seller_id', $sellerId)
            ->whereIn('status', ['pending', 'approved', 'shipped'])
            ->when($orderItem, fn ($q) => $q->where('order_item_id', $orderItem->id))
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'error' => 'Bu ürün/sipariş için zaten bekleyen bir iade talebi var.',
            ], 400);
        }

        try {
            // Upload images if provided
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('return-requests', 'public');
                    $imagePaths[] = $path;
                }
            }

            $returnRequest = ReturnRequest::create([
                'order_id' => $order->id,
                'order_item_id' => $orderItem?->id,
                'buyer_id' => $user->id,
                'seller_id' => $sellerId,
                'type' => 'return',
                'reason' => $validated['reason'],
                'reason_detail' => $validated['reason_detail'] ?? null,
                'quantity' => $validated['quantity'] ?? ($orderItem?->quantity ?? 1),
                'refund_amount' => $refundAmount,
                'status' => 'pending',
                'images' => $imagePaths ?: null,
            ]);

            Log::info('Return request created', [
                'return_request_id' => $returnRequest->id,
                'order_id' => $order->id,
                'buyer_id' => $user->id,
                'seller_id' => $sellerId,
            ]);

            // Notify seller about the return request (DB notification)
            $buyerName = $user->business_name ?? $user->nickname ?? 'Alıcı';
            app(NotificationService::class)->notifyReturnRequestCreated(
                $sellerId, $order, $buyerName, $refundAmount
            );

            // Iade talebi olusturuldu event'i tetikle (e-posta listener'i dinler)
            event(new ReturnRequestCreated($returnRequest));

            return response()->json([
                'success' => true,
                'message' => 'İade talebiniz oluşturuldu. Satıcı en kısa sürede değerlendirecektir.',
                'data' => $this->formatReturnRequest($returnRequest->load(['order', 'orderItem.product'])),
            ]);
        } catch (\Exception $e) {
            Log::error('Return request creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'İade talebi oluşturulamadı: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a return request (seller action)
     */
    public function approve(ReturnRequest $returnRequest, Request $request): JsonResponse
    {
        $this->authorize('approve', $returnRequest);

        $user = $request->user();

        if (! $returnRequest->canBeApproved()) {
            return response()->json([
                'success' => false,
                'error' => 'Bu talep onaylanamaz. Mevcut durum: '.$returnRequest->status_label,
            ], 400);
        }

        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $returnRequest->approve($validated['note'] ?? null);

        Log::info('Return request approved', [
            'return_request_id' => $returnRequest->id,
            'seller_id' => $user->id,
        ]);

        // PayTR automatic refund + wallet reversal
        $order = $returnRequest->order()->with(['items', 'subOrders'])->first();
        $subOrder = $order?->subOrders->firstWhere('seller_id', $returnRequest->seller_id);

        if ($returnRequest->order_item_id) {
            // ITEM-LEVEL (partial) return
            if ($order->payment_status === 'paid') {
                $refundResult = app(RefundService::class)
                    ->processItemRefund($order, $returnRequest);

                if ($refundResult->success) {
                    $returnRequest->update(['status' => 'refunded']);
                }
            }
            // Check if all items in this sub-order have been returned
            $allItemsReturned = $this->checkAllItemsReturned($order, $subOrder);
            if ($allItemsReturned && $subOrder) {
                $subOrder->update(['status' => 'returned']);
                $order->load('subOrders');
                $order->update(['status' => $order->overall_status]);
            }
        } elseif ($subOrder) {
            // FULL SUB-ORDER return (existing flow)
            $subOrder->update(['status' => 'returned']);

            // Sync parent order status from sub-orders
            $order->load('subOrders');
            $order->update(['status' => $order->overall_status]);

            if ($order->payment_status === 'paid') {
                $refundResult = app(RefundService::class)
                    ->processSubOrderRefund($order, $subOrder, 'return');

                if ($refundResult->success) {
                    $returnRequest->update(['status' => 'refunded']);
                }
            }
        }

        // Notify buyer that their return request was approved
        app(NotificationService::class)->notifyReturnRequestApproved(
            $returnRequest->buyer_id, $returnRequest->order
        );

        return response()->json([
            'success' => true,
            'message' => 'İade talebi onaylandı.',
            'data' => $this->formatReturnRequest($returnRequest->fresh(['order', 'orderItem.product', 'buyer']), true),
        ]);
    }

    /**
     * Reject a return request (seller action)
     */
    public function reject(ReturnRequest $returnRequest, Request $request): JsonResponse
    {
        $this->authorize('reject', $returnRequest);

        $user = $request->user();

        if (! $returnRequest->canBeRejected()) {
            return response()->json([
                'success' => false,
                'error' => 'Bu talep reddedilemez. Mevcut durum: '.$returnRequest->status_label,
            ], 400);
        }

        $validated = $request->validate([
            'note' => 'required|string|max:500',
        ]);

        $returnRequest->reject($validated['note']);

        Log::info('Return request rejected', [
            'return_request_id' => $returnRequest->id,
            'seller_id' => $user->id,
        ]);

        // Notify buyer that their return request was rejected
        app(NotificationService::class)->notifyReturnRequestRejected(
            $returnRequest->buyer_id, $returnRequest->order
        );

        return response()->json([
            'success' => true,
            'message' => 'İade talebi reddedildi.',
            'data' => $this->formatReturnRequest($returnRequest->fresh(['order', 'orderItem.product', 'buyer']), true),
        ]);
    }

    /**
     * Get reason options for frontend
     */
    public function reasons(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'reasons' => collect(ReturnRequest::REASON_LABELS)->map(fn ($label, $key) => [
                'value' => $key,
                'label' => $label,
            ])->values(),
        ]);
    }

    /**
     * Check if all items in a sub-order have been fully returned
     */
    private function checkAllItemsReturned(Order $order, ?SubOrder $subOrder): bool
    {
        if (! $subOrder) {
            return false;
        }

        $sellerItems = $order->items->where('seller_id', $subOrder->seller_id);

        foreach ($sellerItems as $item) {
            $returnedQty = ReturnRequest::where('order_id', $order->id)
                ->where('order_item_id', $item->id)
                ->whereIn('status', ['approved', 'refunded'])
                ->sum('quantity');

            if ($returnedQty < $item->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format return request for API response
     */
    private function formatReturnRequest(ReturnRequest $request, bool $includeBuyer = false): array
    {
        $data = [
            'id' => $request->id,
            'order_id' => $request->order_id,
            'order_number' => $request->order?->order_number,
            'order_item_id' => $request->order_item_id,
            'product' => $request->orderItem ? [
                'id' => $request->orderItem->product_id,
                'name' => $request->orderItem->product?->name,
                'image' => $request->orderItem->product?->image,
            ] : null,
            'type' => $request->type,
            'type_label' => $request->type_label,
            'reason' => $request->reason,
            'reason_label' => $request->reason_label,
            'reason_detail' => $request->reason_detail,
            'quantity' => $request->quantity,
            'refund_amount' => $request->refund_amount,
            'formatted_refund' => number_format($request->refund_amount ?? 0, 2, ',', '.').' TL',
            'status' => $request->status,
            'status_label' => $request->status_label,
            'seller_note' => $request->seller_note,
            'return_tracking_number' => $request->return_tracking_number,
            'return_shipping_provider' => $request->return_shipping_provider,
            'created_at' => $request->created_at->format('d.m.Y H:i'),
            'approved_at' => $request->approved_at?->format('d.m.Y H:i'),
            'rejected_at' => $request->rejected_at?->format('d.m.Y H:i'),
            'images' => $request->images ? collect($request->images)->map(fn ($path) => [
                'path' => $path,
                'url' => asset('storage/'.$path),
            ])->toArray() : [],
        ];

        if ($includeBuyer && $request->buyer) {
            $data['buyer'] = [
                'id' => $request->buyer->id,
                'business_name' => $request->buyer->business_name,
                'email' => $request->buyer->email,
                'phone' => $request->buyer->phone,
            ];
        }

        return $data;
    }
}
