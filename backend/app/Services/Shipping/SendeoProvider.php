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
 * Sendeo Kargo Provider (REST API with Token)
 */
class SendeoProvider implements ShippingProviderInterface
{
    protected array $config;
    protected bool $enabled;
    protected string $tokenCacheKey = 'sendeo_token';

    public function __construct()
    {
        $this->config = [
            'api_url' => Setting::getValue('shipping.sendeo_api_url', 'https://api.sendeo.com.tr'),
            'customer_code' => Setting::getValue('shipping.sendeo_customer_code', ''),
            'password' => Setting::getValue('shipping.sendeo_password', '', true),
        ];
        $this->enabled = Setting::getValue('shipping.sendeo_enabled', false);
    }

    public function getName(): string
    {
        return 'sendeo';
    }

    public function isAvailable(): bool
    {
        return $this->enabled && !empty($this->config['customer_code']);
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
            $response = Http::post($this->config['api_url'] . '/Token/LoginAES', [
                'CustomerCode' => $this->config['customer_code'],
                'Password' => $this->config['password'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['Token'])) {
                    $token = $data['Token'];
                    Cache::put($this->tokenCacheKey, [
                        'token' => $token,
                        'expires_at' => now()->addHours(19),
                    ], now()->addHours(19));
                    return $token;
                }
            }
            return null;
        } catch (\Throwable $e) {
            Log::error('Sendeo token error: ' . $e->getMessage());
            return null;
        }
    }

    public function createShipment(Order $order, array $senderInfo): ShipmentResult
    {
        if (!$this->isAvailable()) {
            return ShipmentResult::failure('Sendeo entegrasyonu aktif değil.');
        }

        $token = $this->getToken();
        if (!$token) {
            return ShipmentResult::failure('Sendeo API token alınamadı.');
        }

        try {
            $shippingAddress = $order->shipping_address;

            $deliveryData = [
                'Token' => $token,
                'Delivery' => [
                    'Sender' => [
                        'Name' => $senderInfo['name'] ?? '',
                        'Address' => $senderInfo['address'] ?? '',
                        'City' => $senderInfo['city'] ?? '',
                        'District' => $senderInfo['district'] ?? '',
                        'Phone' => $senderInfo['phone'] ?? '',
                    ],
                    'Receiver' => [
                        'Name' => $shippingAddress['name'] ?? '',
                        'Address' => $shippingAddress['address'] ?? '',
                        'City' => $shippingAddress['city'] ?? '',
                        'District' => $shippingAddress['district'] ?? '',
                        'Phone' => $shippingAddress['phone'] ?? '',
                    ],
                    'PaymentType' => 'Gonderici_Odeyecek',
                    'InvoiceNumber' => $order->order_number,
                    'TotalWeight' => 1000,
                    'TotalDeci' => 1,
                ],
            ];

            $this->logRequest($order, 'create', $deliveryData);
            $response = Http::post($this->config['api_url'] . '/Cargo/SetDelivery', $deliveryData);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['IsSuccess']) && $data['IsSuccess']) {
                    $trackingNumber = $data['TrackingNumber'] ?? $order->order_number;
                    $this->logResponse($order, 'create', $data, 200);
                    return ShipmentResult::success($trackingNumber, message: 'Kargo başarıyla kaydedildi.');
                } else {
                    $error = $data['Message'] ?? 'Hata';
                    $this->logResponse($order, 'create', $data, 400, $error);
                    return ShipmentResult::failure($error, 400);
                }
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
            return ShipmentResult::failure('Sendeo API token alınamadı.');
        }

        try {
            $response = Http::post($this->config['api_url'] . '/Cargo/CancelDelivery', [
                'Token' => $token,
                'TrackingNumber' => $order->tracking_number ?? $order->order_number,
                'CancelReason' => 'Sipariş iptali',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['IsSuccess']) && $data['IsSuccess']) {
                    return ShipmentResult::success($order->tracking_number ?? '', message: 'Kargo iptal edildi.');
                }
                return ShipmentResult::failure($data['Message'] ?? 'Hata', 400);
            }
            return ShipmentResult::failure('API hatası', $response->status());
        } catch (\Throwable $e) {
            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function trackShipment(Order $order): TrackingResult
    {
        $token = $this->getToken();
        if (!$token) {
            return TrackingResult::failure('Sendeo API token alınamadı.');
        }

        try {
            $response = Http::post($this->config['api_url'] . '/Cargo/TrackDelivery', [
                'Token' => $token,
                'TrackingNumber' => $order->tracking_number ?? $order->order_number,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['IsSuccess']) && $data['IsSuccess']) {
                    return TrackingResult::fromStatus(
                        status: $this->mapStatus($data['Status'] ?? ''),
                        statusLabel: $data['StatusDescription'] ?? 'Yolda',
                        trackingNumber: $order->tracking_number,
                    );
                }
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
            'delivered' => 'delivered',
            'in_transit', 'transit' => 'in_transit',
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
