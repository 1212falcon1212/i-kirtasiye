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
 * MNG Kargo Provider
 */
class MngProvider implements ShippingProviderInterface
{
    protected array $config;
    protected bool $enabled;
    protected ?SoapClient $client = null;

    public function __construct()
    {
        $this->config = [
            'username' => Setting::getValue('shipping.mng_username', ''),
            'password' => Setting::getValue('shipping.mng_password', '', true),
            'link' => 'http://service.mngkargo.com.tr/kargoservice/mngkargo.asmx?wsdl',
        ];
        $this->enabled = Setting::getValue('shipping.mng_enabled', false);
    }

    public function getName(): string
    {
        return 'mng';
    }

    public function isAvailable(): bool
    {
        return $this->enabled && !empty($this->config['username']);
    }

    protected function getClient(): ?SoapClient
    {
        if (!$this->client && $this->isAvailable()) {
            try {
                $this->client = new SoapClient($this->config['link']);
            } catch (\Throwable $e) {
                Log::error('MNG SOAP client error: ' . $e->getMessage());
            }
        }
        return $this->client;
    }

    public function createShipment(Order $order, array $senderInfo): ShipmentResult
    {
        if (!$this->isAvailable()) {
            return ShipmentResult::failure('MNG Kargo entegrasyonu aktif değil.');
        }

        try {
            $client = $this->getClient();
            if (!$client) {
                return ShipmentResult::failure('MNG Kargo bağlantısı kurulamadı.');
            }

            $shippingAddress = $order->shipping_address;

            $requestParams = [
                'pKullaniciAdi' => $this->config['username'],
                'pSifre' => $this->config['password'],
                'pSiparisNo' => $order->order_number,
                'pBarkodText' => $order->order_number,
                'pIrsaliyeNo' => $order->order_number,
                'pUrunBedeli' => 0,
                'pKapidaOdeme' => 'Mal_Bedeli_Tahsil_Edilmesin',
                'pOdemeSekli' => 'Gonderici_Odeyecek',
                'pTeslimSekli' => 'Adrese_Teslim',
                'pKargoCinsi' => 'Koli',
                'pGonSms' => 'SMSGonderilmesin',
                'pAliciSms' => 'SMSGonderilmesin',
                'pKapidaTahsilat' => 'Mal_Bedeli_Tahsil_Edilmesin',
                'pAciklama' => 'Sipariş #' . $order->order_number,
                'pGonderiParcaList' => [
                    'GonderiParca' => [
                        [
                            'Kg' => 1,
                            'Desi' => 1,
                            'Adet' => 1,
                            'Icerik' => 'Ürün',
                        ]
                    ]
                ],
                'pGonderenMusteri' => [
                    'pGonMusteriAdi' => $senderInfo['name'] ?? '',
                    'pGonIlAdi' => $senderInfo['city'] ?? '',
                    'pGonilceAdi' => $senderInfo['district'] ?? '',
                    'pGonAdresText' => $senderInfo['address'] ?? '',
                    'pGonTelCep' => $senderInfo['phone'] ?? '',
                ],
                'pAliciMusteri' => [
                    'pAliciMusteriAdi' => $shippingAddress['name'] ?? '',
                    'pAliciIlAdi' => $shippingAddress['city'] ?? '',
                    'pAliciilceAdi' => $shippingAddress['district'] ?? '',
                    'pAliciAdresText' => $shippingAddress['address'] ?? '',
                    'pAliciTelCep' => $shippingAddress['phone'] ?? '',
                ]
            ];

            $this->logRequest($order, 'create', $requestParams);
            $response = $client->SiparisKayit_C2C($requestParams);

            if ($response->SiparisKayit_C2CResult == 1) {
                $this->logResponse($order, 'create', (array) $response, 200);
                return ShipmentResult::success($order->order_number, message: 'Kargo başarıyla kaydedildi.');
            } elseif (strpos($response->SiparisKayit_C2CResult, 'ZATEN VAR') !== false) {
                $this->logResponse($order, 'create', (array) $response, 201);
                return ShipmentResult::success($order->order_number, message: 'Kargo zaten kaydedilmiş.');
            } else {
                $this->logResponse($order, 'create', (array) $response, 400, $response->SiparisKayit_C2CResult);
                return ShipmentResult::failure($response->SiparisKayit_C2CResult ?? 'Hata', 400);
            }
        } catch (\Throwable $e) {
            $this->logResponse($order, 'create', [], 503, $e->getMessage());
            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function cancelShipment(Order $order): ShipmentResult
    {
        if (!$this->isAvailable()) {
            return ShipmentResult::failure('MNG Kargo entegrasyonu aktif değil.');
        }

        try {
            $client = $this->getClient();
            if (!$client) {
                return ShipmentResult::failure('MNG Kargo bağlantısı kurulamadı.');
            }

            $requestParams = [
                'pKullaniciAdi' => $this->config['username'],
                'pSifre' => $this->config['password'],
                'pSiparisNo' => $order->order_number,
            ];

            $response = $client->SiparisIptali_C2C($requestParams);

            if ($response->SiparisIptali_C2CResult == 1) {
                return ShipmentResult::success($order->tracking_number ?? '', message: 'Kargo iptal edildi.');
            }
            return ShipmentResult::failure('İptal işlemi yapılamadı.', 400);
        } catch (\Throwable $e) {
            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function trackShipment(Order $order): TrackingResult
    {
        if (!$this->isAvailable()) {
            return TrackingResult::failure('MNG Kargo entegrasyonu aktif değil.');
        }

        try {
            $client = $this->getClient();
            if (!$client) {
                return TrackingResult::failure('MNG Kargo bağlantısı kurulamadı.');
            }

            $requestParams = [
                'pRfSipGnMusteriNo' => $this->config['username'],
                'pRfSipGnMusteriSifre' => $this->config['password'],
                'pChBarkod' => '',
                'pChFaturaSeri' => '',
                'pChFaturaNo' => '',
                'pNmGonderiNo' => '',
                'pChSiparisNo' => $order->order_number,
                'pGonderiCikisTarihi' => ''
            ];

            $response = $client->GelecekIadeSiparisKontrol($requestParams);

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response->GelecekIadeSiparisKontrolResult->any);
            libxml_use_internal_errors(false);

            $status = (int) ($xml->NewDataSet->Table1->SIPARIS_STATU ?? 0);
            $trackingNo = (string) ($xml->NewDataSet->Table1->GONDERI_NO ?? '');
            $trackingUrl = (string) ($xml->NewDataSet->Table1->KARGO_TAKIP_URL ?? '');
            $description = (string) ($xml->NewDataSet->Table1->SIPARIS_STATU_ACIKLAMA ?? 'Hazırlanıyor');

            return TrackingResult::fromStatus(
                status: $this->mapStatus($status),
                statusLabel: $description,
                trackingNumber: $trackingNo,
                trackingUrl: $trackingUrl,
            );
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
            1, 2 => 'processing',
            3 => 'shipped',
            4, 5 => 'in_transit',
            6 => 'out_for_delivery',
            7 => 'delivered',
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
