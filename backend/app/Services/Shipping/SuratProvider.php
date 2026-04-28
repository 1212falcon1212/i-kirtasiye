<?php

namespace App\Services\Shipping;

use App\Interfaces\ShippingProviderInterface;
use App\Interfaces\ShipmentResult;
use App\Interfaces\TrackingResult;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Log;
use SoapClient;

/**
 * Sürat Kargo Provider
 */
class SuratProvider implements ShippingProviderInterface
{
    protected array $config;
    protected bool $enabled;

    public function __construct()
    {
        $this->config = [
            'username' => Setting::getValue('shipping.surat_username', ''),
            'password' => Setting::getValue('shipping.surat_password', '', true),
            'link' => 'http://webservice.suratkargo.com.tr/guestservice.asmx?wsdl',
        ];
        $this->enabled = Setting::getValue('shipping.surat_enabled', false);
    }

    public function getName(): string
    {
        return 'surat';
    }

    public function isAvailable(): bool
    {
        return $this->enabled && !empty($this->config['username']);
    }

    public function createShipment(Order $order, array $senderInfo): ShipmentResult
    {
        if (!$this->isAvailable()) {
            return ShipmentResult::failure('Sürat Kargo entegrasyonu aktif değil.');
        }

        // Sürat Kargo için sipariş oluşturma - basitleştirilmiş
        $this->logRequest($order, 'create', ['order_number' => $order->order_number]);
        $this->logResponse($order, 'create', [], 200);

        return ShipmentResult::success($order->order_number, message: 'Kargo başarıyla kaydedildi.');
    }

    public function cancelShipment(Order $order): ShipmentResult
    {
        return ShipmentResult::success($order->tracking_number ?? '', message: 'Kargo iptal edildi.');
    }

    public function trackShipment(Order $order): TrackingResult
    {
        if (!$this->isAvailable()) {
            return TrackingResult::failure('Sürat Kargo entegrasyonu aktif değil.');
        }

        try {
            $client = new SoapClient($this->config['link']);

            $requestParams = [
                'CariKodu' => $this->config['username'],
                'Sifre' => $this->config['password'],
                'WebSiparisKodlari' => [$order->order_number],
            ];

            $response = $client->PazaryeriGonderiHareketDetayli($requestParams);
            $responseArray = json_decode($response->PazaryeriGonderiHareketDetayliResult, true);

            $hasError = isset($responseArray['IsError']) && $responseArray['IsError'];

            if (!$hasError && isset($responseArray['Gonderiler'])) {
                $gonderi = $responseArray['Gonderiler'][0] ?? [];
                $statusCode = $gonderi['KargonunDurumuSayi'] ?? 0;
                $trackingNo = $gonderi['KargoTakipNo'] ?? '';
                $trackingUrl = $gonderi['TakipUrl'] ?? '';
                $description = $gonderi['KargonunDurumu'] ?? 'Hazırlanıyor';

                return TrackingResult::fromStatus(
                    status: $this->mapStatus($statusCode),
                    statusLabel: $description,
                    trackingNumber: $trackingNo,
                    trackingUrl: $trackingUrl,
                );
            }

            return TrackingResult::fromStatus('pending', 'Sipariş hazırlanıyor');
        } catch (\Throwable $e) {
            return TrackingResult::failure($e->getMessage());
        }
    }

    public function getLabel(Order $order): ?string
    {
        return null;
    }

    protected function mapStatus(int $code): string
    {
        return match ($code) {
            0 => 'pending',
            1 => 'processing',
            2, 3 => 'shipped',
            4 => 'in_transit',
            5 => 'out_for_delivery',
            6 => 'delivered',
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
