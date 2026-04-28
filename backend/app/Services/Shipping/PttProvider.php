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
 * PTT Kargo Provider
 */
class PttProvider implements ShippingProviderInterface
{
    protected array $config;
    protected bool $enabled;

    public function __construct()
    {
        $this->config = [
            'username' => Setting::getValue('shipping.ptt_username', ''),
            'password' => Setting::getValue('shipping.ptt_password', '', true),
            'customer_id' => Setting::getValue('shipping.ptt_customer_id', ''),
            'order_link' => 'https://pttws.ptt.gov.tr/PttKargoWS/PttKargoWS?wsdl',
            'track_link' => 'https://pttws.ptt.gov.tr/GonderiTakip/GonderiTakipWS?wsdl',
            'base_tracking_link' => 'https://gonderitakip.ptt.gov.tr/?kod=',
        ];
        $this->enabled = Setting::getValue('shipping.ptt_enabled', false);
    }

    public function getName(): string
    {
        return 'ptt';
    }

    public function isAvailable(): bool
    {
        return $this->enabled && !empty($this->config['username']) && !empty($this->config['customer_id']);
    }

    protected function getSoapClient(string $wsdl): ?SoapClient
    {
        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);

            return new SoapClient($wsdl, [
                'stream_context' => $context,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => 1,
                'exceptions' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('PTT SOAP client error: ' . $e->getMessage());
            return null;
        }
    }

    public function createShipment(Order $order, array $senderInfo): ShipmentResult
    {
        if (!$this->isAvailable()) {
            return ShipmentResult::failure('PTT Kargo entegrasyonu aktif değil.');
        }

        try {
            $client = $this->getSoapClient($this->config['order_link']);
            if (!$client) {
                return ShipmentResult::failure('PTT Kargo bağlantısı kurulamadı.');
            }

            $shippingAddress = $order->shipping_address;

            $dongu = [
                'aAdres' => $shippingAddress['address'] ?? '',
                'agirlik' => 1,
                'aliciAdi' => $shippingAddress['name'] ?? '',
                'aliciIlAdi' => $shippingAddress['city'] ?? '',
                'aliciIlceAdi' => $shippingAddress['district'] ?? '',
                'aliciSms' => $shippingAddress['phone'] ?? '',
                'aliciTel' => $shippingAddress['phone'] ?? '',
                'barkodNo' => $order->order_number,
                'boy' => 1,
                'desi' => 1,
                'en' => 1,
                'gondericibilgi' => [
                    'gonderici_adi' => $senderInfo['name'] ?? '',
                    'gonderici_adresi' => $senderInfo['address'] ?? '',
                    'gonderici_email' => $senderInfo['email'] ?? '',
                    'gonderici_il_ad' => $senderInfo['city'] ?? '',
                    'gonderici_ilce_ad' => $senderInfo['district'] ?? '',
                    'gonderici_sms' => $senderInfo['phone'] ?? '',
                    'gonderici_telefonu' => $senderInfo['phone'] ?? '',
                ],
                'musteriReferansNo' => $order->order_number,
                'yukseklik' => 1,
            ];

            $input = [
                'dongu' => [$dongu],
                'dosyaAdi' => 'LB_' . date('YmdHis'),
                'gonderiTip' => 'NORMAL',
                'gonderiTur' => 'KARGO',
                'kullanici' => $this->config['username'],
                'musteriId' => (int) $this->config['customer_id'],
                'sifre' => $this->config['password'],
            ];

            $this->logRequest($order, 'create', $input);
            $response = $client->kabulEkle2(['input' => $input]);
            $this->logResponse($order, 'create', (array) $response, 200);

            return ShipmentResult::success($order->order_number, message: 'PTT gönderi kaydı oluşturuldu.');
        } catch (\Throwable $e) {
            $this->logResponse($order, 'create', [], 503, $e->getMessage());
            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function cancelShipment(Order $order): ShipmentResult
    {
        if (!$this->isAvailable()) {
            return ShipmentResult::failure('PTT Kargo entegrasyonu aktif değil.');
        }

        try {
            $client = $this->getSoapClient($this->config['order_link']);
            if (!$client) {
                return ShipmentResult::failure('PTT Kargo bağlantısı kurulamadı.');
            }

            $input = [
                'barcode' => $order->order_number,
                'dosyaAdi' => 'LB_CANCEL_' . date('YmdHis'),
                'musteriId' => (int) $this->config['customer_id'],
                'sifre' => $this->config['password'],
            ];

            $response = $client->barkodVeriSil(['inpDelete' => $input]);
            $result = $response->return ?? null;
            $hataKodu = $result->hataKodu ?? null;

            if ($hataKodu === null || $hataKodu === 0) {
                return ShipmentResult::success($order->tracking_number ?? '', message: 'PTT gönderi iptal edildi.');
            }
            return ShipmentResult::failure($result->aciklama ?? 'İptal başarısız', 400);
        } catch (\Throwable $e) {
            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function trackShipment(Order $order): TrackingResult
    {
        if (!$this->isAvailable()) {
            return TrackingResult::failure('PTT Kargo entegrasyonu aktif değil.');
        }

        try {
            $client = $this->getSoapClient($this->config['track_link']);
            if (!$client) {
                return TrackingResult::failure('PTT Kargo bağlantısı kurulamadı.');
            }

            $input = [
                'barkod' => $order->tracking_number ?? $order->order_number,
                'kullanici' => $this->config['customer_id'],
                'sifre' => $this->config['password'],
            ];

            $response = $client->gonderiSorgu2(['input' => $input]);
            $result = $response->return ?? null;

            if ($result) {
                $delivered = !empty($result->TESALAN);
                $trackingCode = $result->BARNO ?? $order->order_number;

                return TrackingResult::fromStatus(
                    status: $delivered ? 'delivered' : 'in_transit',
                    statusLabel: $result->sonucAciklama ?? ($delivered ? 'Teslim edildi' : 'Yolda'),
                    trackingNumber: $trackingCode,
                    trackingUrl: $this->config['base_tracking_link'] . $trackingCode,
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
