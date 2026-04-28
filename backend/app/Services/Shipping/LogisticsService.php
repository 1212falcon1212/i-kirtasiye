<?php

namespace App\Services\Shipping;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Kargo lojistik servisi - Stub
 * Kullanıcının API dokümantasyonuna göre implemente edilecek
 */
class LogisticsService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        // These will be loaded from settings when implemented
        $this->apiKey = config('services.shipping.api_key', '');
        $this->baseUrl = config('services.shipping.base_url', '');
    }

    /**
     * Create a shipment for an order
     * 
     * @param Order $order
     * @param array $items Items to ship (subset of order items)
     * @return ShipmentResult
     */
    public function createShipment(Order $order, array $items = []): ShipmentResult
    {
        Log::info("LogisticsService::createShipment called for order: {$order->order_number}");

        // Stub implementation - will be completed with actual API integration
        // When implemented:
        // 1. Build shipment data from order
        // 2. POST to shipping provider API
        // 3. Parse response and return tracking number

        return ShipmentResult::pending(
            message: 'Kargo servisi henüz implemente edilmedi. API bilgileri sağlandığında aktif edilecek.',
        );
    }

    /**
     * Get tracking info for a shipment
     * 
     * @param string $trackingNo
     * @return TrackingInfo|null
     */
    public function getTrackingInfo(string $trackingNo): ?TrackingInfo
    {
        Log::info("LogisticsService::getTrackingInfo called for tracking: {$trackingNo}");

        // Stub - return null for now
        return null;
    }

    /**
     * Cancel a shipment
     * 
     * @param string $trackingNo
     * @return bool
     */
    public function cancelShipment(string $trackingNo): bool
    {
        Log::info("LogisticsService::cancelShipment called for tracking: {$trackingNo}");

        // Stub - return false for now
        return false;
    }

    /**
     * Build shipment data from order
     */
    protected function buildShipmentData(Order $order, array $items): array
    {
        return [
            'sender' => [
                'name' => 'i-kirtasiye',
                'address' => 'Platform Adresi',
                'phone' => '0850 XXX XX XX',
            ],
            'receiver' => [
                'name' => $order->shipping_address['name'] ?? '',
                'address' => $order->shipping_address['address'] ?? '',
                'city' => $order->shipping_address['city'] ?? '',
                'district' => $order->shipping_address['district'] ?? '',
                'phone' => $order->shipping_address['phone'] ?? '',
            ],
            'order_number' => $order->order_number,
            'payment_type' => 'prepaid', // Already paid online
            'items' => collect($items)->map(function ($item) {
                return [
                    'name' => $item['product_name'] ?? 'Ürün',
                    'quantity' => $item['quantity'] ?? 1,
                ];
            })->toArray(),
        ];
    }
}

/**
 * Shipment creation result
 */
class ShipmentResult
{
    public function __construct(
        public bool $success,
        public string $status, // created, pending, failed
        public ?string $trackingNo = null,
        public ?string $trackingUrl = null,
        public ?string $message = null,
        public ?string $error = null,
    ) {
    }

    public static function created(string $trackingNo, ?string $trackingUrl = null): self
    {
        return new self(
            success: true,
            status: 'created',
            trackingNo: $trackingNo,
            trackingUrl: $trackingUrl,
        );
    }

    public static function pending(?string $message = null): self
    {
        return new self(
            success: false,
            status: 'pending',
            message: $message,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            status: 'failed',
            error: $error,
        );
    }
}

/**
 * Tracking information
 */
class TrackingInfo
{
    public function __construct(
        public string $trackingNo,
        public string $status, // picked_up, in_transit, out_for_delivery, delivered, returned
        public string $statusLabel,
        public ?string $lastLocation = null,
        public ?string $estimatedDelivery = null,
        public array $history = [],
    ) {
    }
}
