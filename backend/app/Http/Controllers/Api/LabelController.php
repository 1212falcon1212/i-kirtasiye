<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    protected ShippingManager $shippingManager;

    public function __construct(ShippingManager $shippingManager)
    {
        $this->shippingManager = $shippingManager;
    }

    /**
     * Generate shipping label for an order
     */
    public function generate(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $order = Order::with(['items.seller', 'items.product', 'user'])->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        // Check if user is seller for this order or super-admin
        $isSellerForOrder = $order->items()
            ->where('seller_id', $user->id)
            ->exists();

        if (!$isSellerForOrder && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Bu sipariş için yetkiniz yok'], 403);
        }

        // Check if order has tracking number
        if (empty($order->tracking_number)) {
            return response()->json([
                'message' => 'Önce kargo kaydı oluşturulmalıdır',
            ], 422);
        }

        try {
            // Get the shipping provider from order or default
            $providerName = $order->shipping_provider ?? $this->getDefaultProvider();
            $provider = $this->shippingManager->getProviderByName($providerName);

            if (!$provider) {
                return response()->json(['message' => 'Kargo sağlayıcı bulunamadı'], 422);
            }

            // getLabel returns ?string (label URL or base64)
            $labelUrl = $provider->getLabel($order);

            return response()->json([
                'success' => true,
                'label_url' => $labelUrl,
                'format' => 'pdf',
                'message' => 'Kargo etiketi oluşturuldu',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Kargo etiketi oluşturulamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create shipment and get label in one call
     */
    public function createShipment(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'nullable|string|in:aras,yurtici,mng,ptt,sendeo,hepsijet,kolaygelsin,surat',
        ]);

        $user = $request->user();
        $order = Order::with(['user', 'items.seller', 'items.product'])->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        // Check authorization
        $isSellerForOrder = $order->items()
            ->where('seller_id', $user->id)
            ->exists();

        if (!$isSellerForOrder && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Bu sipariş için yetkiniz yok'], 403);
        }

        // Check if already shipped
        if (!empty($order->tracking_number)) {
            return response()->json([
                'message' => 'Bu sipariş zaten kargoya verilmiş',
                'tracking_number' => $order->tracking_number,
            ], 422);
        }

        try {
            $providerName = $validated['provider'] ?? $this->getDefaultProvider();

            // Get provider
            $shippingProvider = $this->shippingManager->getProviderByName($providerName);
            if (!$shippingProvider) {
                return response()->json(['message' => 'Kargo sağlayıcı bulunamadı'], 422);
            }

            // Prepare sender info from settings or user
            $senderInfo = $this->getSenderInfo($user);

            // Create shipment - interface expects Order and senderInfo array
            $result = $shippingProvider->createShipment($order, $senderInfo);

            if ($result->success) {
                // Update order with tracking info
                $order->update([
                    'tracking_number' => $result->trackingNumber,
                    'shipping_provider' => $providerName,
                    'status' => 'shipped',
                ]);

                return response()->json([
                    'success' => true,
                    'tracking_number' => $result->trackingNumber,
                    'label_url' => $result->labelUrl,
                    'message' => $result->message ?? 'Kargo kaydı oluşturuldu',
                ]);
            }

            return response()->json([
                'message' => $result->error ?? 'Kargo kaydı oluşturulamadı',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Kargo kaydı oluşturulamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tracking info for an order
     */
    public function track(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $order = Order::with('items')->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        // Check authorization (buyer, seller, or admin)
        $isBuyer = $order->user_id === $user->id;
        $isSeller = $order->items()->where('seller_id', $user->id)->exists();

        if (!$isBuyer && !$isSeller && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Bu sipariş için yetkiniz yok'], 403);
        }

        if (empty($order->tracking_number)) {
            return response()->json([
                'message' => 'Bu sipariş henüz kargoya verilmemiş',
            ], 422);
        }

        try {
            $providerName = $order->shipping_provider ?? $this->getDefaultProvider();
            $provider = $this->shippingManager->getProviderByName($providerName);

            if (!$provider) {
                return response()->json(['message' => 'Kargo sağlayıcı bulunamadı'], 422);
            }

            // trackShipment returns TrackingResult
            $trackingResult = $provider->trackShipment($order);

            return response()->json([
                'success' => $trackingResult->success,
                'tracking_number' => $trackingResult->trackingNumber ?? $order->tracking_number,
                'tracking_url' => $trackingResult->trackingUrl,
                'provider' => $providerName,
                'status' => $trackingResult->status,
                'status_label' => $trackingResult->statusLabel,
                'current_location' => $trackingResult->currentLocation,
                'last_update' => $trackingResult->lastUpdate,
                'history' => $trackingResult->history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Kargo takip bilgisi alınamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get default shipping provider from settings
     */
    private function getDefaultProvider(): string
    {
        return Setting::getValue('shipping.default_provider', 'aras');
    }

    /**
     * Get sender info from user or settings
     */
    private function getSenderInfo($user): array
    {
        return [
            'name' => $user->business_name ?? Setting::getValue('company.name', 'B2B Kırtasiye'),
            'phone' => $user->phone ?? Setting::getValue('company.phone', ''),
            'address' => $user->address ?? Setting::getValue('company.address', ''),
            'city' => $user->city ?? Setting::getValue('company.city', 'İstanbul'),
            'district' => '',
            'postal_code' => '',
        ];
    }
}
