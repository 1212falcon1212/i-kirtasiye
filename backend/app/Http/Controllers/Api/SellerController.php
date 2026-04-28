<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SubOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    /**
     * Get seller dashboard stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Bu ayki satışlar
        $currentMonthSales = OrderItem::where('seller_id', $user->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', ['paid']);
            })
            ->whereBetween('created_at', [$startOfMonth, $now])
            ->sum('total_price');

        // Geçen ayki satışlar
        $lastMonthSales = OrderItem::where('seller_id', $user->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('payment_status', ['paid']);
            })
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_price');

        // Satış değişim yüzdesi
        $salesChange = $lastMonthSales > 0
            ? round((($currentMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 1)
            : ($currentMonthSales > 0 ? 100 : 0);

        // Aktif teklifler
        $activeOffers = Offer::where('seller_id', $user->id)
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->where('expiry_date', '>', $now)
            ->count();

        // Bekleyen siparişler (sub_order bazli)
        $pendingOrders = SubOrder::where('seller_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->count();

        // Cüzdan bakiyesi
        $walletBalance = $user->wallet?->balance ?? 0;

        // Bekleyen hakediş
        $pendingPayout = $user->wallet?->pending_balance ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => [
                    'value' => $currentMonthSales,
                    'formatted' => '₺'.number_format($currentMonthSales, 2, ',', '.'),
                    'change' => ($salesChange >= 0 ? '+' : '').$salesChange.'%',
                    'trend' => $salesChange >= 0 ? 'up' : 'down',
                ],
                'active_offers' => [
                    'value' => $activeOffers,
                    'formatted' => (string) $activeOffers,
                ],
                'pending_orders' => [
                    'value' => $pendingOrders,
                    'formatted' => (string) $pendingOrders,
                ],
                'wallet_balance' => [
                    'value' => $walletBalance,
                    'formatted' => '₺'.number_format($walletBalance, 2, ',', '.'),
                    'pending' => '₺'.number_format($pendingPayout, 2, ',', '.'),
                ],
            ],
        ]);
    }

    /**
     * Get seller's recent orders (sub_order based)
     */
    public function recentOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->input('limit', 5);

        $subOrders = SubOrder::where('seller_id', $user->id)
            ->with([
                'items.product:id,name,brand',
                'order.user:id,business_name,nickname,city',
                'order:id,order_number,created_at',
            ])
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($subOrder) {
                $order = $subOrder->order;
                $productNames = $subOrder->items->pluck('product.name')->take(2)->join(', ');

                if ($subOrder->items->count() > 2) {
                    $productNames .= ' +'.($subOrder->items->count() - 2).' ürün';
                }

                return [
                    'id' => $order->id,
                    'sub_order_id' => $subOrder->id,
                    'order_number' => $order->order_number,
                    'product' => $productNames,
                    'buyer' => $subOrder->status === 'pending'
                        ? 'Onay bekleniyor'
                        : ($order->user->nickname ?? $order->user->business_name ?? 'Bilinmiyor'),
                    'amount' => '₺'.number_format($subOrder->subtotal, 2, ',', '.'),
                    'status' => $subOrder->status,
                    'status_label' => $this->getStatusLabel($subOrder->status),
                    'created_at' => $order->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subOrders,
        ]);
    }

    /**
     * Get seller's products (products they have offers for)
     */
    public function products(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);

        $offers = Offer::where('seller_id', $user->id)
            ->with(['product:id,name,barcode,brand,image', 'product.category:id,name'])
            ->latest()
            ->paginate($perPage);

        $products = $offers->map(function ($offer) {
            return [
                'id' => $offer->product->id,
                'offer_id' => $offer->id,
                'name' => $offer->product->name,
                'barcode' => $offer->product->barcode,
                'brand' => $offer->product->brand,
                'image' => $offer->product->image,
                'category' => $offer->product->category?->name,
                'price' => $offer->price,
                'stock' => $offer->stock,
                'status' => $offer->status,
                'expiry_date' => $offer->expiry_date?->format('Y-m-d'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
            'pagination' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }

    /**
     * Get seller's orders (sub_order based)
     */
    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);

        $query = SubOrder::where('seller_id', $user->id)
            ->with([
                'items.product:id,name,brand,image',
                'order.user:id,business_name,nickname,city',
                'order:id,order_number,payment_status,created_at',
            ]);

        if ($status) {
            $query->where('status', $status);
        }

        $subOrders = $query->latest()->paginate($perPage);

        $formattedOrders = $subOrders->map(function ($subOrder) {
            $order = $subOrder->order;
            $isPending = $subOrder->status === 'pending';

            return [
                'id' => $order->id,
                'sub_order_id' => $subOrder->id,
                'order_number' => $order->order_number,
                'buyer' => $isPending ? [
                    'name' => 'Onay bekleniyor',
                    'city' => '',
                ] : [
                    'name' => $order->user->nickname ?? $order->user->business_name ?? 'Bilinmiyor',
                    'city' => $order->user->city ?? '',
                ],
                'items' => $subOrder->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product->name,
                        'brand' => $item->product->brand,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total_price,
                    ];
                })->values(),
                'total' => $subOrder->subtotal,
                'formatted_total' => '₺'.number_format((float) $subOrder->subtotal, 2, ',', '.'),
                'status' => $subOrder->status,
                'status_label' => $this->getStatusLabel($subOrder->status),
                'payment_status' => $order->payment_status,
                'created_at' => $order->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedOrders,
            'pagination' => [
                'current_page' => $subOrders->currentPage(),
                'last_page' => $subOrders->lastPage(),
                'per_page' => $subOrders->perPage(),
                'total' => $subOrders->total(),
            ],
        ]);
    }

    /**
     * Get status label in Turkish
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Bekliyor',
            'confirmed' => 'Onaylandı',
            'processing' => 'Hazırlanıyor',
            'shipped' => 'Kargoda',
            'delivered' => 'Teslim Edildi',
            'cancelled' => 'İptal Edildi',
            default => $status,
        };
    }

    /**
     * Get single order detail with fee breakdown (sub_order based)
     */
    public function orderDetail(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();

        // Load order with sub_order for this seller
        $order = Order::with([
            'user:id,business_name,nickname,email,phone,city,address',
        ])->find($orderId);

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Sipariş bulunamadı'], 404);
        }

        // Find seller's sub_order
        $subOrder = SubOrder::where('order_id', $orderId)
            ->where('seller_id', $user->id)
            ->with(['items.product:id,name,brand,barcode,image,desi'])
            ->first();

        if (! $subOrder) {
            return response()->json(['success' => false, 'message' => 'Bu siparişe erişim yetkiniz yok'], 403);
        }

        $sellerItems = $subOrder->items;

        // Fee rates
        $feeMode = \App\Models\Setting::getValue('commission.fee_mode', 'flat');
        $marketplaceFeeEnabled = (bool) \App\Models\Setting::getValue('commission.marketplace_fee_enabled', false);
        $marketplaceFeeRate = $marketplaceFeeEnabled ? (float) \App\Models\Setting::getValue('commission.marketplace_fee_rate', 0.89) : 0;
        $withholdingTaxRate = (float) \App\Models\Setting::getValue('commission.withholding_tax_rate', 1.00);

        // Kesinti hesapları
        $totalSales = $sellerItems->sum('total_price');
        $totalCommission = $sellerItems->sum('commission_amount');
        $totalMarketplaceFee = $marketplaceFeeEnabled ? $sellerItems->sum('marketplace_fee') : 0;
        $totalWithholdingTax = $sellerItems->sum('withholding_tax');
        $totalShippingShare = $sellerItems->sum('shipping_cost_share');
        $netSellerAmount = $sellerItems->sum('net_seller_amount');

        // Eğer yeni alanlar henüz hesaplanmamışsa, hesapla
        if ($totalMarketplaceFee == 0 && $totalWithholdingTax == 0) {
            $totalMarketplaceFee = $marketplaceFeeEnabled ? $totalSales * ($marketplaceFeeRate / 100) : 0;
            $totalWithholdingTax = $totalSales * ($withholdingTaxRate / 100);
            $netSellerAmount = $totalSales - $totalCommission - $totalMarketplaceFee - $totalWithholdingTax - $totalShippingShare;
        }

        // İade/iade talebi bilgileri
        $returnRequests = \App\Models\ReturnRequest::where('order_id', $order->id)
            ->where('seller_id', $user->id)
            ->with('orderItem.product:id,name,image')
            ->get();

        $refundedAmount = (float) $returnRequests
            ->whereIn('status', ['approved', 'refunded'])
            ->sum('refund_amount');

        // İade tutarını net hakediş'ten düş
        if ($refundedAmount > 0) {
            $netSellerAmount -= $refundedAmount;
        }

        // Check if invoice exists for this order + seller
        $invoice = \App\Models\Invoice::where('order_id', $orderId)
            ->where('seller_id', $user->id)
            ->where('type', 'seller')
            ->first();

        // Use sub_order status/shipping data instead of parent order
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'sub_order_id' => $subOrder->id,
                'order_number' => $order->order_number,
                'status' => $subOrder->status,
                'status_label' => $this->getStatusLabel($subOrder->status),
                'payment_status' => $order->payment_status,
                'shipping_status' => $subOrder->shipping_status,
                'tracking_number' => $subOrder->tracking_number,
                'shipping_provider' => $subOrder->shipping_provider,
                'shipping_label_url' => $order->shipping_label_url,
                'created_at' => $order->created_at->toISOString(),
                'shipped_at' => $subOrder->shipped_at?->toISOString(),
                'delivered_at' => $subOrder->delivered_at?->toISOString(),
                'buyer_confirmed_at' => $subOrder->buyer_confirmed_at?->toISOString(),

                // Alıcı bilgileri — pending siparişlerde gizli
                'buyer' => $subOrder->status === 'pending' ? null : [
                    'name' => $order->user->nickname ?? $order->user->business_name ?? 'Bilinmiyor',
                    'invoice_name' => $order->user->business_name ?? $order->user->trade_name ?? $order->user->nickname ?? 'Bilinmiyor',
                    'email' => $order->user->email,
                    'phone' => $order->user->phone,
                    'city' => $order->shipping_address['city'] ?? $order->user->city,
                    'district' => $order->shipping_address['district'] ?? '',
                    'address' => $order->shipping_address['address'] ?? $order->user->address ?? '',
                ],

                // Sipariş kalemleri
                'items' => $sellerItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'brand' => $item->product->brand,
                        'barcode' => $item->product->barcode,
                        'image' => $item->product->image,
                        'desi' => $item->product->desi,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                    ];
                })->values(),

                // Finansal özet
                'financials' => [
                    'subtotal' => [
                        'label' => 'Ürün Toplamı',
                        'value' => round($totalSales, 2),
                        'formatted' => '₺'.number_format($totalSales, 2, ',', '.'),
                    ],
                    'deductions' => [
                        [
                            'label' => match ($feeMode) {
                                'flat' => 'Sabit Hizmet Bedeli',
                                'percentage' => 'Komisyon',
                                'category' => 'Kategori Komisyonu',
                                default => 'Komisyon',
                            },
                            'rate' => match ($feeMode) {
                                'percentage' => (float) \App\Models\Setting::getValue('commission.commission_percentage', 10),
                                default => null,
                            },
                            'value' => round($totalCommission, 2),
                            'formatted' => '-₺'.number_format($totalCommission, 2, ',', '.'),
                        ],
                        [
                            'label' => 'Pazaryeri Hizmet Bedeli',
                            'rate' => $marketplaceFeeRate,
                            'value' => round($totalMarketplaceFee, 2),
                            'formatted' => '-₺'.number_format($totalMarketplaceFee, 2, ',', '.'),
                            'visible' => $marketplaceFeeEnabled,
                        ],
                        [
                            'label' => 'Stopaj',
                            'rate' => $withholdingTaxRate,
                            'value' => round($totalWithholdingTax, 2),
                            'formatted' => '-₺'.number_format($totalWithholdingTax, 2, ',', '.'),
                        ],
                        [
                            'label' => 'Kargo Payı',
                            'rate' => null,
                            'value' => round($totalShippingShare, 2),
                            'formatted' => '-₺'.number_format($totalShippingShare, 2, ',', '.'),
                            'visible' => $totalShippingShare > 0,
                        ],
                        ...($refundedAmount > 0 ? [[
                            'label' => 'İade Tutarı',
                            'rate' => null,
                            'value' => round($refundedAmount, 2),
                            'formatted' => '-₺'.number_format($refundedAmount, 2, ',', '.'),
                        ]] : []),
                    ],
                    'total_deductions' => [
                        'label' => 'Toplam Kesinti',
                        'value' => round($totalCommission + $totalMarketplaceFee + $totalWithholdingTax + $totalShippingShare + $refundedAmount, 2),
                        'formatted' => '-₺'.number_format($totalCommission + $totalMarketplaceFee + $totalWithholdingTax + $totalShippingShare + $refundedAmount, 2, ',', '.'),
                    ],
                    'total_refunded' => [
                        'label' => 'Toplam İade',
                        'value' => round($refundedAmount, 2),
                        'formatted' => '₺'.number_format($refundedAmount, 2, ',', '.'),
                    ],
                    'net_amount' => [
                        'label' => 'Net Hakediş',
                        'value' => round($netSellerAmount, 2),
                        'formatted' => '₺'.number_format($netSellerAmount, 2, ',', '.'),
                    ],
                ],

                // Fatura durumu — sub_order status kullan
                'can_create_invoice' => ! $invoice && in_array($subOrder->status, ['confirmed', 'processing', 'shipped', 'delivered']),
                'can_create_label' => in_array($subOrder->status, ['confirmed', 'processing']) && empty($subOrder->tracking_number),

                // Existing invoice if any
                'invoice' => $invoice ? [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'total_amount' => $invoice->total_amount,
                    'formatted_total' => '₺'.number_format($invoice->total_amount, 2, ',', '.'),
                    'created_at' => $invoice->created_at->toISOString(),
                    'pdf_path' => $invoice->pdf_path,
                ] : null,

                // İade talepleri
                'return_requests' => $returnRequests->map(function ($rr) {
                    return [
                        'id' => $rr->id,
                        'type' => $rr->type,
                        'type_label' => $rr->type_label,
                        'status' => $rr->status,
                        'status_label' => $rr->status_label,
                        'reason' => $rr->reason,
                        'reason_label' => $rr->reason_label,
                        'reason_detail' => $rr->reason_detail,
                        'quantity' => $rr->quantity,
                        'refund_amount' => (float) $rr->refund_amount,
                        'formatted_refund' => '₺'.number_format((float) $rr->refund_amount, 2, ',', '.'),
                        'product' => $rr->orderItem?->product ? [
                            'id' => $rr->orderItem->product->id,
                            'name' => $rr->orderItem->product->name,
                            'image' => $rr->orderItem->product->image,
                        ] : null,
                        'created_at' => $rr->created_at->toISOString(),
                        'approved_at' => $rr->approved_at?->toISOString(),
                        'refunded_at' => $rr->refunded_at?->toISOString(),
                    ];
                })->values(),
            ],
        ]);
    }
}
