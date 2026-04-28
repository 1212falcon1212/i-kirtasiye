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
 * Navlungo Kargo Provider (REST API with Bearer Token)
 *
 * API Docs: https://domestic-docs.navlungo.com/tr
 * Test: https://domestic-api-qa.navlungo.com/v2.1/
 * Prod: https://domestic-api.navlungo.com/v2.1/
 */
class NavlungoProvider implements ShippingProviderInterface
{
    protected array $config;
    protected bool $enabled;
    protected string $tokenCacheKey = 'navlungo_token';

    public function __construct()
    {
        $this->config = [
            'api_url' => Setting::getValue('shipping.navlungo_api_url', 'https://domestic-api.navlungo.com/v2.1'),
            'username' => Setting::getValue('shipping.navlungo_username', ''),
            'password' => Setting::getValue('shipping.navlungo_password', '', true),
            'sender_address_id' => (int) Setting::getValue('shipping.navlungo_sender_address_id', 0),
            'carrier_id' => (int) Setting::getValue('shipping.navlungo_carrier_id', 1),
        ];
        $this->enabled = (bool) Setting::getValue('shipping.navlungo_enabled', false);
    }

    public function getName(): string
    {
        return 'navlungo';
    }

    public function isAvailable(): bool
    {
        return $this->enabled
            && !empty($this->config['username'])
            && !empty($this->config['password'])
            && $this->config['sender_address_id'] > 0;
    }

    protected function getToken(): ?string
    {
        $cached = Cache::get($this->tokenCacheKey);
        if ($cached && isset($cached['token'], $cached['expires_at'])) {
            if (now()->lt($cached['expires_at'])) {
                return $cached['token'];
            }
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(rtrim($this->config['api_url'], '/') . '/auth/api', [
                'username' => $this->config['username'],
                'password' => $this->config['password'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['status']) && isset($data['data']['access_token'])) {
                    $token = $data['data']['access_token'];
                    $expiresAt = isset($data['data']['expires_in'])
                        ? now()->parse($data['data']['expires_in'])->subMinutes(30)
                        : now()->addHours(7);

                    Cache::put($this->tokenCacheKey, [
                        'token' => $token,
                        'expires_at' => $expiresAt,
                    ], $expiresAt);

                    return $token;
                }
            }

            Log::error('Navlungo token error: ' . $response->body());
            return null;
        } catch (\Throwable $e) {
            Log::error('Navlungo token error: ' . $e->getMessage());
            return null;
        }
    }

    protected function apiHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'X-localization' => 'tr',
        ];
    }

    public function createShipment(Order $order, array $senderInfo): ShipmentResult
    {
        if (!$this->isAvailable()) {
            return ShipmentResult::failure('Navlungo entegrasyonu aktif değil.');
        }

        $token = $this->getToken();
        if (!$token) {
            return ShipmentResult::failure('Navlungo API token alınamadı.');
        }

        try {
            $shippingAddress = $order->shipping_address;

            $postData = [
                'platform' => 'b2b-kirtasiye',
                'posts' => [
                    [
                        'reference_id' => $order->order_number,
                        'carrier_id' => $this->config['carrier_id'],
                        'post_type' => 2, // Standard
                        'sender' => [
                            'addressId' => $this->config['sender_address_id'],
                        ],
                        'recipient' => [
                            'name' => $shippingAddress['name'] ?? '',
                            'phone' => $shippingAddress['phone'] ?? '',
                            'email' => $shippingAddress['email'] ?? '',
                            'address' => $shippingAddress['address'] ?? '',
                            'country' => 'TR',
                            'city' => $shippingAddress['city'] ?? '',
                            'district' => $shippingAddress['district'] ?? '',
                        ],
                        'post' => [
                            'desi' => 1,
                            'package_count' => 1,
                            'price' => (string) $order->total_amount,
                            'note' => 'Sipariş #' . $order->order_number,
                        ],
                        'custom_data_1' => (string) $order->id,
                    ],
                ],
            ];

            $this->logRequest($order, 'create', $postData);

            $response = Http::withHeaders($this->apiHeaders($token))
                ->post(rtrim($this->config['api_url'], '/') . '/post/create', $postData);

            if ($response->successful()) {
                $data = $response->json();

                // Response can be a single object or wrapped in status/data
                $postResult = $data['data'][0] ?? $data[0] ?? $data;

                $postNumber = $postResult['post_number'] ?? null;
                $trackingUrl = $postResult['tracking_url'] ?? null;
                $barcodeUrl = $postResult['barcode_url'] ?? null;

                if ($postNumber) {
                    $this->logResponse($order, 'create', $response->json(), $response->status());
                    return ShipmentResult::success(
                        trackingNumber: $postNumber,
                        labelUrl: $barcodeUrl,
                        message: 'Kargo başarıyla oluşturuldu.',
                    );
                }

                $error = $postResult['message'] ?? $data['message'] ?? 'Gönderi numarası alınamadı';
                $this->logResponse($order, 'create', $response->json(), $response->status(), $error);
                return ShipmentResult::failure($error, $response->status());
            }

            $errorBody = $response->json();
            $errorMsg = $errorBody['message'] ?? $errorBody['error'] ?? ('API hatası: ' . $response->status());
            $this->logResponse($order, 'create', $errorBody, $response->status(), $errorMsg);
            return ShipmentResult::failure($errorMsg, $response->status());
        } catch (\Throwable $e) {
            $this->logResponse($order, 'create', [], 503, $e->getMessage());
            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function cancelShipment(Order $order): ShipmentResult
    {
        $token = $this->getToken();
        if (!$token) {
            return ShipmentResult::failure('Navlungo API token alınamadı.');
        }

        try {
            $postNumber = $order->tracking_number ?? $order->order_number;

            $this->logRequest($order, 'cancel', ['post_number' => $postNumber]);

            $response = Http::withHeaders($this->apiHeaders($token))
                ->post(rtrim($this->config['api_url'], '/') . '/post/cancel', [
                    'post_number' => $postNumber,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['status'])) {
                    $this->logResponse($order, 'cancel', $data, 200);
                    return ShipmentResult::success($postNumber, message: 'Kargo iptal edildi.');
                }
                $error = $data['message'] ?? 'İptal başarısız';
                $this->logResponse($order, 'cancel', $data, $response->status(), $error);
                return ShipmentResult::failure($error, $response->status());
            }

            $errorBody = $response->json();
            $errorMsg = $errorBody['message'] ?? ('API hatası: ' . $response->status());
            $this->logResponse($order, 'cancel', $errorBody, $response->status(), $errorMsg);
            return ShipmentResult::failure($errorMsg, $response->status());
        } catch (\Throwable $e) {
            $this->logResponse($order, 'cancel', [], 503, $e->getMessage());
            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function trackShipment(Order $order): TrackingResult
    {
        $token = $this->getToken();
        if (!$token) {
            return TrackingResult::failure('Navlungo API token alınamadı.');
        }

        try {
            $postNumber = $order->tracking_number ?? $order->order_number;

            $response = Http::withHeaders($this->apiHeaders($token))
                ->get(rtrim($this->config['api_url'], '/') . '/post/check/' . $postNumber);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['status']) && isset($data['data'])) {
                    $trackData = $data['data'];
                    $statusInfo = $trackData['status'] ?? [];
                    $statusCode = $statusInfo['id'] ?? 0;
                    $statusLabel = $statusInfo['name'] ?? 'Bilinmiyor';
                    $trackingUrl = $trackData['tracking_url'] ?? $trackData['carrier_tracking_url'] ?? null;

                    return TrackingResult::fromStatus(
                        status: $this->mapStatus($statusCode),
                        statusLabel: $statusLabel,
                        trackingNumber: $trackData['post_number'] ?? $postNumber,
                        trackingUrl: $trackingUrl,
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
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        try {
            $postNumber = $order->tracking_number ?? $order->order_number;

            $response = Http::withHeaders($this->apiHeaders($token))
                ->post(rtrim($this->config['api_url'], '/') . '/barcode/getBarcode', [
                    'post_number' => $postNumber,
                    'barcode_type' => 'pdf',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['status']) && isset($data['data']['barcode_url'])) {
                    return $data['data']['barcode_url'];
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Navlungo barcode error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Map Navlungo status codes to internal status values.
     *
     * Navlungo codes: 1=Teslim Alınacak, 2=Teslim Edildi, 3=Teslim Edilecek,
     * 4=Dağıtıma Çıktı, 5=Tekrar Sevk, 6=Dağıtım Planlandı,
     * 7=İade Edilecek, 9=İade Edildi, 10=İptal
     */
    protected function mapStatus(int $statusCode): string
    {
        return match ($statusCode) {
            2 => 'delivered',
            3, 5, 6 => 'in_transit',
            4 => 'out_for_delivery',
            1 => 'shipped',
            7, 9 => 'returned',
            10 => 'cancelled',
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
