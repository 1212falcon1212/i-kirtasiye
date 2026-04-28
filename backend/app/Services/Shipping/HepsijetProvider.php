<?php

namespace App\Services\Shipping;

use App\Interfaces\ShippingProviderInterface;
use App\Interfaces\ShipmentResult;
use App\Interfaces\TrackingResult;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hepsijet Kargo Provider (REST API with Bearer Token)
 */
class HepsijetProvider implements ShippingProviderInterface
{
    protected array $config;
    protected bool $enabled;
    protected string $tokenCacheKey = 'hepsijet_token';

    public function __construct()
    {
        $this->config = [
            'api_url' => Setting::getValue('shipping.hepsijet_api_url', 'https://integration-apitest.hepsijet.com'),
            'api_key' => Setting::getValue('shipping.hepsijet_api_key', ''),
            'api_secret' => Setting::getValue('shipping.hepsijet_api_secret', ''),
        ];
        $this->enabled = (bool) Setting::getValue('shipping.hepsijet_enabled', false);
    }

    public function getName(): string
    {
        return 'hepsijet';
    }

    public function isAvailable(): bool
    {
        return $this->enabled && !empty($this->config['api_key']);
    }

    protected function getToken(): ?string
    {
        $cached = Cache::get($this->tokenCacheKey);
        if ($cached && isset($cached['token']) && isset($cached['expires_at'])) {
            if (now()->lt($cached['expires_at'])) {
                return $cached['token'];
            }
        }

        try {
            $authString = base64_encode($this->config['api_key'] . ':' . $this->config['api_secret']);

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => 'Basic ' . $authString,
            ])->get($this->config['api_url'] . '/auth/getToken');

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['token'] ?? $data['access_token'] ?? null;
                $expiresIn = (int) ($data['expires_in'] ?? 3600);

                if ($token) {
                    Cache::put($this->tokenCacheKey, [
                        'token' => $token,
                        'expires_at' => now()->addSeconds($expiresIn - 300),
                    ], now()->addSeconds($expiresIn - 60));
                    return $token;
                }
            }
            return null;
        } catch (\Throwable $e) {
            Log::error('Hepsijet token error: ' . $e->getMessage());
            return null;
        }
    }

    public function createShipment(Order $order, array $senderInfo): ShipmentResult
    {
        if (!$this->isAvailable()) {
            return ShipmentResult::failure('Hepsijet entegrasyonu aktif değil.');
        }

        $token = $this->getToken();
        if (!$token) {
            return ShipmentResult::failure('Hepsijet API token alınamadı.');
        }

        try {
            $shippingAddress = $order->shipping_address;

            $shipmentData = [
                'customerOrderId' => $order->order_number,
                'sender' => [
                    'name' => $senderInfo['name'] ?? '',
                    'address' => [
                        'city' => ['name' => $senderInfo['city'] ?? ''],
                        'town' => ['name' => $senderInfo['district'] ?? ''],
                        'district' => ['name' => ''],
                        'addressLine1' => $senderInfo['address'] ?? '',
                        'addressLine2' => '',
                    ],
                    'phone' => $senderInfo['phone'] ?? '',
                ],
                'receiver' => [
                    'name' => $shippingAddress['name'] ?? '',
                    'address' => [
                        'city' => ['name' => $shippingAddress['city'] ?? ''],
                        'town' => ['name' => $shippingAddress['district'] ?? ''],
                        'district' => ['name' => ''],
                        'addressLine1' => $shippingAddress['address'] ?? '',
                        'addressLine2' => '',
                    ],
                    'phone' => $shippingAddress['phone'] ?? '',
                ],
                'parcels' => [
                    [
                        'desi' => 1,
                        'weight' => 1000,
                        'content' => 'Gönderi',
                    ]
                ],
                'serviceType' => ['STANDART'],
                'paymentType' => 'SENDER_PAYS',
                'codAmount' => 0,
                'invoiceNumber' => $order->order_number,
            ];

            $this->logRequest($order, 'create', $shipmentData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->config['api_url'] . '/delivery/sendDeliveryOrderEnhanced', $shipmentData);

            if ($response->successful()) {
                $data = $response->json();
                $deliveryNo = $data['deliveryNo'] ?? $data['delivery_no'] ?? null;

                if ($deliveryNo) {
                    $this->logResponse($order, 'create', $data, 200);
                    return ShipmentResult::success($deliveryNo, message: 'Kargo başarıyla kaydedildi.');
                }
                $error = $data['message'] ?? $data['error'] ?? 'Hata';
                $this->logResponse($order, 'create', $data, 400, $error);
                return ShipmentResult::failure($error, 400);
            }
            return ShipmentResult::failure('API hatası: ' . $response->status(), $response->status());
        } catch (\Throwable $e) {
            $this->logResponse($order, 'create', [], 503, $e->getMessage());
            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function cancelShipment(Order $order): ShipmentResult
    {
        $token = $this->getToken();
        if (!$token) {
            return ShipmentResult::failure('Hepsijet API token alınamadı.');
        }

        try {
            $deliveryNo = $order->tracking_number ?? $order->order_number;
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->config['api_url'] . '/rest/delivery/deleteDeliveryOrder/' . $deliveryNo, [
                        'reason' => 'Sipariş iptali',
                    ]);

            if ($response->successful()) {
                return ShipmentResult::success($order->tracking_number ?? '', message: 'Kargo iptal edildi.');
            }
            return ShipmentResult::failure('İptal başarısız', $response->status());
        } catch (\Throwable $e) {
            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function trackShipment(Order $order): TrackingResult
    {
        $token = $this->getToken();
        if (!$token) {
            return TrackingResult::failure('Hepsijet API token alınamadı.');
        }

        try {
            $trackData = ['customerOrderId' => $order->order_number];
            if ($order->tracking_number) {
                $trackData['deliveryNo'] = $order->tracking_number;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($this->config['api_url'] . '/deliveryTransaction/getDeliveryTracking', $trackData);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['status'] ?? '';
                $statusDesc = $data['statusDescription'] ?? $status;

                return TrackingResult::fromStatus(
                    status: $this->mapStatus($status),
                    statusLabel: $statusDesc,
                    trackingNumber: $data['deliveryNo'] ?? $order->tracking_number,
                );
            }
            return TrackingResult::fromStatus('pending', 'Hazırlanıyor');
        } catch (\Throwable $e) {
            return TrackingResult::failure($e->getMessage());
        }
    }

    public function getLabel(Order $order): ?string
    {
        return null;
    }

    protected function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'delivered', 'teslim edildi' => 'delivered',
            'in_transit', 'yolda' => 'in_transit',
            'shipped' => 'shipped',
            default => 'pending',
        };
    }

    protected function logRequest(Order $order, string $action, array $request): void
    {
        ShippingLog::create([
            'order_id' => $order->id,
            'provider' => $this->getName(),
            'action' => $action,
            'request' => $request,
            'status' => 'pending',
        ]);
    }

    protected function logResponse(Order $order, string $action, array $response, int $code, ?string $error = null): void
    {
        ShippingLog::where('order_id', $order->id)
            ->where('provider', $this->getName())
            ->where('action', $action)
            ->where('status', 'pending')
            ->latest()
            ->first()
                ?->update([
                'response' => $response,
                'response_code' => $code,
                'status' => $error ? 'failed' : 'success',
                'error' => $error,
            ]);
    }
}
