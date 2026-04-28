<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Shipping\ShippingManager;
use App\Services\ShippingCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    protected ShippingManager $shippingManager;

    protected ShippingCalculatorService $shippingCalculator;

    public function __construct(ShippingManager $shippingManager, ShippingCalculatorService $shippingCalculator)
    {
        $this->shippingManager = $shippingManager;
        $this->shippingCalculator = $shippingCalculator;
    }

    /**
     * Calculate shipping cost
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'subtotal' => 'required|numeric|min:0',
        ]);

        $subtotal = (float) $request->subtotal;
        $shippingCost = $this->shippingManager->calculateShippingCost($subtotal);
        $remainingForFree = $this->shippingManager->getRemainingForFreeShipping($subtotal);

        return response()->json([
            'shipping_cost' => $shippingCost,
            'is_free' => $shippingCost == 0,
            'remaining_for_free' => $remainingForFree,
            'free_threshold' => $this->shippingManager->getFreeThreshold(),
        ]);
    }

    /**
     * Get shipping config for frontend
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'flat_rate' => $this->shippingManager->getFlatRate(),
            'free_threshold' => $this->shippingManager->getFreeThreshold(),
            'provider' => $this->shippingManager->getActiveProvider(),
            'enabled' => $this->shippingManager->isEnabled(),
        ]);
    }

    /**
     * Get shipping options based on cart desi and order amount
     */
    public function getOptions(Request $request): JsonResponse
    {
        $request->validate([
            'total_desi' => 'required|numeric|min:0',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $totalDesi = (float) $request->total_desi;
        $orderAmount = (float) $request->order_amount;

        $options = $this->shippingCalculator->getShippingOptions($totalDesi, $orderAmount);

        return response()->json([
            'success' => true,
            'options' => $options,
            'total_desi' => $totalDesi,
            'order_amount' => $orderAmount,
        ]);
    }

    /**
     * Create shipment for an order (seller action)
     */
    public function createShipment(Order $order, Request $request): JsonResponse
    {
        $user = $request->user();

        // Verify user is seller of this order's items
        $hasSellerItems = $order->items()->where('seller_id', $user->id)->exists();
        if (! $hasSellerItems && ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        // Check order status
        if (! in_array($order->payment_status, ['paid'])) {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş henüz ödenmedi.',
            ], 400);
        }

        if ($order->shipping_status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Kargo zaten oluşturulmuş.',
            ], 400);
        }

        $request->validate([
            'piece_count' => 'nullable|integer|min:1|max:99',
            'is_cod' => 'nullable|boolean',
            'cod_amount' => 'nullable|numeric|min:0',
            'cod_collection_type' => 'nullable|in:0,1',
            'total_desi' => 'nullable|numeric|min:0',
            'total_weight' => 'nullable|numeric|min:0',
        ]);

        $provider = $this->shippingManager->getProvider();

        if (! $provider) {
            return response()->json([
                'success' => false,
                'error' => 'Kargo entegrasyonu aktif değil.',
            ], 400);
        }

        // Sender = ürünü gönderen satıcı (kargoyu oluşturan kullanıcı). Pazaryeri operatörü değil.
        $senderInfo = [
            'name' => $user->business_name ?? $user->trade_name ?? '',
            'address' => $user->address ?? '',
            'city' => $user->city ?? '',
            'city_id' => $user->city_id,
            'district' => $user->district ?? '',
            'district_id' => $user->district_id,
            'phone' => $user->phone ?? '',
        ];

        if (empty($senderInfo['city_id'])) {
            return response()->json([
                'success' => false,
                'error' => 'Adres bilgileriniz eksik. Hesabım > Profil sayfasından il/ilçe bilgilerinizi güncelleyin.',
            ], 400);
        }

        foreach (['piece_count', 'is_cod', 'cod_amount', 'cod_collection_type', 'total_desi', 'total_weight'] as $key) {
            if ($request->filled($key)) {
                $senderInfo[$key] = $request->input($key);
            }
        }

        $result = $provider->createShipment($order, $senderInfo);

        if ($result->success) {
            $order->update([
                'shipping_provider' => $provider->getName(),
                'tracking_number' => $result->trackingNumber,
                'shipping_status' => 'processing',
                'shipping_label_url' => $result->labelUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => $result->message,
                'tracking_number' => $result->trackingNumber,
                'label_url' => $result->labelUrl,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error,
        ], 400);
    }

    /**
     * Track shipment status
     */
    public function track(Order $order, Request $request): JsonResponse
    {
        // Verify access
        if ($order->user_id !== $request->user()->id) {
            $hasSellerItems = $order->items()->where('seller_id', $request->user()->id)->exists();
            if (! $hasSellerItems && ! $request->user()->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bu siparişe erişim yetkiniz yok.',
                ], 403);
            }
        }

        if (! $order->shipping_provider) {
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'status_label' => 'Kargo bekleniyor',
            ]);
        }

        $provider = $this->shippingManager->getProviderByName($order->shipping_provider);

        if (! $provider) {
            return response()->json([
                'success' => true,
                'status' => $order->shipping_status,
                'status_label' => $this->getStatusLabel($order->shipping_status),
                'tracking_number' => $order->tracking_number,
            ]);
        }

        $result = $provider->trackShipment($order);

        if ($result->success) {
            // Update order status if changed
            if ($result->status !== $order->shipping_status) {
                $updateData = ['shipping_status' => $result->status];

                if ($result->status === 'shipped') {
                    $updateData['shipped_at'] = now();
                } elseif ($result->status === 'delivered') {
                    $updateData['delivered_at'] = now();
                }

                $order->update($updateData);
            }

            return response()->json([
                'success' => true,
                'status' => $result->status,
                'status_label' => $result->statusLabel,
                'tracking_number' => $result->trackingNumber,
                'tracking_url' => $result->trackingUrl,
                'current_location' => $result->currentLocation,
                'history' => $result->history,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error,
        ], 400);
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Kargo bekleniyor',
            'processing' => 'Hazırlanıyor',
            'shipped' => 'Kargoya verildi',
            'in_transit' => 'Yolda',
            'out_for_delivery' => 'Dağıtımda',
            'delivered' => 'Teslim edildi',
            'returned' => 'İade edildi',
            'failed' => 'Başarısız',
            default => 'Bilinmiyor',
        };
    }

    /**
     * Generate test mode shipping label
     */
    public function generateTestLabel(Order $order, Request $request): JsonResponse
    {
        $user = $request->user();

        // Verify user is seller of this order's items
        $hasSellerItems = $order->items()->where('seller_id', $user->id)->exists();
        if (! $hasSellerItems && ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        // Check if already has label
        if ($order->shipping_label_url) {
            return response()->json([
                'success' => true,
                'message' => 'Kargo etiketi zaten mevcut.',
                'label_url' => $order->shipping_label_url,
                'tracking_number' => $order->tracking_number,
            ]);
        }

        // Use MockLabelService for test mode
        $mockService = new \App\Services\Shipping\MockLabelService;

        $senderInfo = [
            'name' => $user->business_name,
            'address' => $user->address ?? 'Satıcı Adresi',
            'city' => $user->city ?? 'İstanbul',
            'phone' => $user->phone ?? '',
        ];

        $result = $mockService->generateLabel($order, $senderInfo);

        if ($result['success']) {
            $order->update([
                'shipping_provider' => 'Test',
                'tracking_number' => $result['tracking_number'],
                'shipping_status' => 'processing',
                'shipping_label_url' => $result['label_url'],
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'tracking_number' => $result['tracking_number'],
                'label_url' => $result['label_url'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Etiket oluşturulamadı.',
        ], 500);
    }

    /**
     * Download shipping label — gerçek barkod etiketi üret
     */
    public function downloadLabel(Order $order, Request $request): JsonResponse|\Illuminate\Http\Response
    {
        $user = $request->user();

        // Verify access
        $hasAccess = $order->user_id === $user->id ||
            $order->items()->where('seller_id', $user->id)->exists() ||
            $user->isSuperAdmin();

        if (! $hasAccess) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        // Eğer kargo provider'ı varsa gerçek etiket üret
        if ($order->shipping_provider && $order->shipping_provider !== 'Test') {
            $provider = $this->shippingManager->getProviderByName($order->shipping_provider);

            if ($provider) {
                $labelHtml = $provider->getLabel($order);

                if ($labelHtml) {
                    // HTML olarak döndür (tarayıcıda açılıp yazdırılabilir)
                    if ($request->query('format') === 'html') {
                        return response($labelHtml, 200, [
                            'Content-Type' => 'text/html; charset=utf-8',
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'label_html' => $labelHtml,
                        'tracking_number' => $order->tracking_number,
                        'provider' => $order->shipping_provider,
                    ]);
                }
            }
        }

        // Fallback: mevcut label URL
        if ($order->shipping_label_url) {
            return response()->json([
                'success' => true,
                'label_url' => $order->shipping_label_url,
                'tracking_number' => $order->tracking_number,
                'provider' => $order->shipping_provider,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Kargo etiketi henüz oluşturulmamış.',
        ], 404);
    }

    /**
     * Detaylı kargo bilgisi (desi, ağırlık, şube bilgileri)
     */
    public function shippingDetail(Order $order, Request $request): JsonResponse
    {
        $user = $request->user();

        // Verify access
        $hasAccess = $order->user_id === $user->id ||
            $order->items()->where('seller_id', $user->id)->exists() ||
            $user->isSuperAdmin();

        if (! $hasAccess) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        if (! $order->shipping_provider || $order->shipping_provider === 'Test') {
            return response()->json([
                'success' => true,
                'detail' => null,
                'message' => 'Kargo entegrasyonu aktif değil.',
            ]);
        }

        $provider = $this->shippingManager->getProviderByName($order->shipping_provider);

        if (! $provider || ! method_exists($provider, 'getDetailedInfo')) {
            return response()->json([
                'success' => true,
                'detail' => null,
                'message' => 'Bu kargo firması detay sorgulama desteklemiyor.',
            ]);
        }

        $detail = $provider->getDetailedInfo($order);

        return response()->json([
            'success' => true,
            'detail' => $detail,
            'tracking_number' => $order->tracking_number,
            'provider' => $order->shipping_provider,
        ]);
    }
}
