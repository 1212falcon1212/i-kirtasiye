<?php

namespace App\Services\Shipping;

use App\Interfaces\ShipmentResult;
use App\Interfaces\ShippingProviderInterface;
use App\Interfaces\TrackingResult;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SoapClient;

/**
 * Yurtiçi Kargo "Yeni Öder Modeli" SOAP Provider.
 *
 * Endpoints:
 *   - LIVE:  https://ws.yurticikargo.com/KOPSWebServices
 *   - TEST:  https://testws.yurticikargo.com/KOPSWebServices
 */
class YurtIciProvider implements ShippingProviderInterface
{
    /** @var array<string, mixed> */
    protected array $config;

    protected bool $enabled;

    public function __construct()
    {
        $testMode = (bool) Setting::getValue('shipping.yurtici_test_mode', true);
        $base = $testMode
            ? 'https://testws.yurticikargo.com/KOPSWebServices'
            : 'https://ws.yurticikargo.com/KOPSWebServices';

        $this->config = [
            'username' => Setting::getValue('shipping.yurtici_username', ''),
            'password' => Setting::getValue('shipping.yurtici_password', '', true),
            'customer_id' => Setting::getValue('shipping.yurtici_customer_id', ''),
            'project_code' => Setting::getValue('shipping.yurtici_project_code', ''),
            'order_link' => "$base/NgiShipmentInterfaceServices?wsdl",
            'order_endpoint' => "$base/NgiShipmentInterfaceServices",
            'track_link' => "$base/WsReportWithReferenceServices?wsdl",
            'track_endpoint' => "$base/WsReportWithReferenceServices",
            'base_tracking_link' => 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code=',
            'test_mode' => $testMode,
        ];
        $this->enabled = (bool) Setting::getValue('shipping.yurtici_enabled', false);
    }

    public function getName(): string
    {
        return 'yurtici';
    }

    public function isAvailable(): bool
    {
        return $this->enabled
            && ! empty($this->config['username'])
            && ! empty($this->config['password'])
            && ! empty($this->config['customer_id']);
    }

    public function createShipment(Order $order, array $senderInfo): ShipmentResult
    {
        if (! $this->isAvailable()) {
            return ShipmentResult::failure('Yurtiçi Kargo entegrasyonu aktif değil.');
        }

        try {
            $shippingAddress = (array) $order->shipping_address;
            [$weight, $desi] = $this->calculateWeightAndDesi($order, $senderInfo);

            $piece = max(1, (int) ($senderInfo['piece_count'] ?? 1));
            $isCod = ! empty($senderInfo['is_cod']);
            $codAmount = (float) ($senderInfo['cod_amount'] ?? 0);
            $codType = (string) ($senderInfo['cod_collection_type'] ?? '1');

            $senderCityId = $senderInfo['city_id'] ?? null;
            $consigneeCityId = $shippingAddress['city_id'] ?? null;

            if (empty($senderCityId)) {
                return ShipmentResult::failure('Gönderici il kodu (city_id) tanımlı değil. Kargo Ayarları sayfasından doldurun.');
            }
            if (empty($consigneeCityId)) {
                return ShipmentResult::failure('Alıcı adresinde il bilgisi eksik. Müşteriden adresini güncellemesini isteyin.');
            }

            $shipmentData = [
                'ngiDocumentKey' => $order->order_number,
                'cargoType' => 2,
                'totalCargoCount' => $piece,
                'totalDesi' => (string) $desi,
                'totalWeight' => (string) $weight,
                'personGiver' => mb_substr((string) ($senderInfo['name'] ?? ''), 0, 80),
                'description' => 'Sipariş #'.$order->order_number,
                'productCode' => 'STA',
                'docCargoDataArray' => [
                    'ngiCargoKey' => $order->order_number,
                    'cargoType' => 2,
                    'cargoDesi' => (string) $desi,
                    'cargoWeight' => (string) $weight,
                    'cargoCount' => 1,
                    'width' => '',
                    'height' => '',
                    'length' => '',
                    'weightUnit' => 'KGM',
                    'dimensionsUnit' => 'CM',
                ],
                'specialFieldDataArray' => [
                    [
                        'specialFieldName' => '53',
                        'specialFieldValue' => $order->order_number,
                    ],
                ],
            ];

            if ($isCod && $codAmount > 0) {
                $shipmentData['codData'] = [
                    'ttInvoiceAmount' => number_format($codAmount, 2, '.', ''),
                    'ttDocumentId' => $order->order_number,
                    'ttCollectionType' => $codType, // 0-Nakit, 1-Kredi Kartı
                    'ttDocumentSaveType' => '1',      // 1-Farklı Fatura
                    'dcSelectedCredit' => '1',
                    'dcCreditRule' => '1',      // Tek Çekime izin ver
                ];
            }

            $requestParams = [
                'wsUserName' => $this->config['username'],
                'wsPassword' => $this->config['password'],
                'wsUserLanguage' => 'TR',
                'shipmentData' => $shipmentData,
                'XSenderCustAddress' => [
                    'senderCustName' => mb_substr((string) ($senderInfo['name'] ?? ''), 0, 100),
                    'senderAddress' => mb_substr((string) ($senderInfo['address'] ?? ''), 0, 100),
                    'cityId' => (string) $senderCityId,
                    'townName' => mb_substr((string) ($senderInfo['district'] ?? ''), 0, 40),
                    'senderPhone' => $this->normalizePhone($senderInfo['phone'] ?? ''),
                    'senderMobilePhone' => $this->normalizePhone($senderInfo['phone'] ?? ''),
                ],
                'XConsigneeCustAddress' => [
                    'consigneeCustName' => mb_substr((string) ($shippingAddress['name'] ?? ''), 0, 100),
                    'consigneeAddress' => mb_substr((string) ($shippingAddress['address'] ?? ''), 0, 100),
                    'cityId' => (string) $consigneeCityId,
                    'townName' => mb_substr((string) ($shippingAddress['district'] ?? ''), 0, 40),
                    'consigneePhone' => $this->normalizePhone($shippingAddress['phone'] ?? ''),
                    'consigneeMobilePhone' => $this->normalizePhone($shippingAddress['phone'] ?? ''),
                ],
                'payerCustData' => [
                    'invCustId' => $this->config['customer_id'],
                ],
            ];

            $this->logRequest($order, 'create', $requestParams);

            $client = new SoapClient($this->config['order_link'], [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => 1,
                'exceptions' => true,
                'connection_timeout' => 20,
                'location' => $this->config['order_endpoint'],
            ]);

            $response = $client->createNgiShipmentWithAddress($requestParams);

            if (! isset($response->XShipmentDataResponse)) {
                $this->logResponse($order, 'create', (array) $response, 500, 'Beklenmedik API yanıtı');

                return ShipmentResult::failure('Beklenmedik API yanıtı.', 500);
            }

            $result = $response->XShipmentDataResponse;
            if ((string) ($result->outFlag ?? '') !== '0') {
                $errorMsg = $result->outResult ?? 'Bilinmeyen hata';
                $this->logResponse($order, 'create', (array) $result, 400, $errorMsg);

                return ShipmentResult::failure($errorMsg, 400);
            }

            $this->logResponse($order, 'create', (array) $result, 200);

            $trackingNumber = $this->extractDocId($result) ?? $order->order_number;
            $labelUrl = $this->generateLabel($order, $trackingNumber, $senderInfo);

            return new ShipmentResult(
                success: true,
                trackingNumber: $trackingNumber,
                labelUrl: $labelUrl,
                message: 'Kargo başarıyla kaydedildi.',
            );
        } catch (\SoapFault $e) {
            $this->logResponse($order, 'create', [], 503, 'SOAP: '.$e->getMessage());
            Log::error('YurtIci createShipment SOAP error', ['error' => $e->getMessage()]);

            return ShipmentResult::failure('Yurtiçi sunucusuna ulaşılamadı: '.$e->getMessage(), 503);
        } catch (\Throwable $e) {
            $this->logResponse($order, 'create', [], 503, $e->getMessage());
            Log::error('YurtIci createShipment error', ['error' => $e->getMessage()]);

            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function cancelShipment(Order $order): ShipmentResult
    {
        if (! $this->isAvailable()) {
            return ShipmentResult::failure('Yurtiçi Kargo entegrasyonu aktif değil.');
        }

        try {
            $requestParams = [
                'wsUserName' => $this->config['username'],
                'wsPassword' => $this->config['password'],
                'wsUserLanguage' => 'TR',
                'ngiCargoKey' => $order->order_number,
                'ngiDocumentKey' => $order->order_number,
                'cancellationDescription' => 'Sipariş iptali',
            ];

            $this->logRequest($order, 'cancel', $requestParams);

            $client = new SoapClient($this->config['order_link'], [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => 1,
                'exceptions' => true,
                'connection_timeout' => 20,
                'location' => $this->config['order_endpoint'],
            ]);

            $response = $client->cancelNgiShipment($requestParams);

            if (! isset($response->XCancelShipmentResponse)) {
                return ShipmentResult::failure('Beklenmedik API yanıtı.', 500);
            }

            $result = $response->XCancelShipmentResponse;

            if ((string) ($result->outFlag ?? '') !== '0') {
                $errorMsg = $result->outResult ?? 'İptal işlemi yapılamadı.';
                $this->logResponse($order, 'cancel', (array) $result, 400, $errorMsg);

                return ShipmentResult::failure($errorMsg, 400);
            }

            $this->logResponse($order, 'cancel', (array) $result, 200);

            return ShipmentResult::success(
                trackingNumber: (string) ($result->docId ?? $order->tracking_number ?? ''),
                message: 'Kargo iptal edildi.',
            );
        } catch (\Throwable $e) {
            $this->logResponse($order, 'cancel', [], 503, $e->getMessage());

            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    public function trackShipment(Order $order): TrackingResult
    {
        if (! $this->isAvailable()) {
            return TrackingResult::failure('Yurtiçi Kargo entegrasyonu aktif değil.');
        }

        try {
            $client = new SoapClient($this->config['track_link'], [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => 1,
                'exceptions' => true,
                'connection_timeout' => 20,
                'location' => $this->config['track_endpoint'],
            ]);

            $requestParams = [
                'wsUserName' => $this->config['username'],
                'wsPassword' => $this->config['password'],
                'wsUserLanguage' => 'TR',
                'custParamsVO' => [
                    'invCustIdArray' => $this->config['customer_id'],
                ],
                'fieldName' => '53',
                'fieldValueArray' => $order->order_number,
                'withCargoLifecycle' => '1',
            ];

            $response = $client->listInvDocumentInterfaceByReference($requestParams);

            if (! isset($response->ShippingDataResponseVO)) {
                return TrackingResult::fromStatus('pending', 'Sipariş hazırlanıyor');
            }

            $result = $response->ShippingDataResponseVO;
            if ((string) ($result->outFlag ?? '') !== '0' || empty($result->shippingDataDetailVOArray)) {
                return TrackingResult::fromStatus('pending', 'Sipariş hazırlanıyor');
            }

            $detail = is_array($result->shippingDataDetailVOArray)
                ? $result->shippingDataDetailVOArray[0]
                : $result->shippingDataDetailVOArray;

            [$status, $statusLabel] = $this->mapStatus($detail);
            $trackingNo = $detail->docId ?? $order->order_number;
            $trackingUrl = $detail->trackingUrl ?? $this->config['base_tracking_link'].$trackingNo;

            return new TrackingResult(
                success: true,
                status: $status,
                statusLabel: $statusLabel,
                trackingNumber: (string) $trackingNo,
                trackingUrl: (string) $trackingUrl,
                currentLocation: (string) ($detail->arrivalUnitName ?? $detail->departureUnitName ?? ''),
                lastUpdate: $this->formatYurticiDate($detail->documentDate ?? null, $detail->documentTime ?? null),
                history: $this->buildHistory($detail),
            );
        } catch (\Throwable $e) {
            return TrackingResult::failure($e->getMessage());
        }
    }

    public function getLabel(Order $order): ?string
    {
        if (! empty($order->shipping_label_url)) {
            return $order->shipping_label_url;
        }

        // Etiket createShipment akışında üretilir. Sonradan istenirse satıcı user'ından üret.
        $seller = $order->items()->with('seller')->first()?->seller;
        if (! $seller) {
            return null;
        }

        return $this->generateLabel($order, $order->tracking_number ?? $order->order_number, [
            'name' => $seller->business_name ?? $seller->trade_name ?? '',
            'address' => $seller->address ?? '',
            'phone' => $seller->phone ?? '',
            'city_id' => $seller->city_id,
            'district' => $seller->district ?? '',
        ]);
    }

    /**
     * Generate 100x150mm thermal PDF label and return public URL.
     */
    protected function generateLabel(Order $order, string $trackingNumber, array $senderInfo): ?string
    {
        try {
            $shippingAddress = (array) $order->shipping_address;

            $pdf = Pdf::loadView('shipping.yurtici-label', [
                'order' => $order,
                'trackingNumber' => $trackingNumber,
                'projectCode' => $this->config['project_code'] ?: '—',
                'sender' => [
                    'name' => $senderInfo['name'] ?? '',
                    'address' => $senderInfo['address'] ?? '',
                    'phone' => $this->normalizePhone($senderInfo['phone'] ?? ''),
                ],
                'consignee' => [
                    'name' => $shippingAddress['name'] ?? '',
                    'address' => $shippingAddress['address'] ?? '',
                    'phone' => $this->normalizePhone($shippingAddress['phone'] ?? ''),
                ],
            ])->setPaper([0, 0, 283.46, 425.20], 'portrait'); // 100mm × 150mm

            $path = "labels/yurtici-{$order->id}-".time().'.pdf';
            Storage::disk('public')->put($path, $pdf->output());

            return Storage::url($path);
        } catch (\Throwable $e) {
            Log::error('YurtIci generateLabel error', ['order' => $order->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Calculate total weight (kg) and desi from order items.
     *
     * @return array{0: float, 1: float}
     */
    protected function calculateWeightAndDesi(Order $order, array $senderInfo): array
    {
        if (isset($senderInfo['total_weight']) && isset($senderInfo['total_desi'])) {
            return [(float) $senderInfo['total_weight'], (float) $senderInfo['total_desi']];
        }

        $weight = 0.0;
        $desi = 0.0;

        $items = $order->items()->with('product')->get();
        foreach ($items as $item) {
            $qty = max(1, (int) $item->quantity);
            $product = $item->product;
            $itemWeight = $product?->weight ? (float) $product->weight : 0.5;
            $itemDesi = $product?->desi ? (float) $product->desi : 0.5;

            $weight += $itemWeight * $qty;
            $desi += $itemDesi * $qty;
        }

        return [
            round(max($weight, 0.5), 2),
            round(max($desi, 1.0), 2),
        ];
    }

    /**
     * Map Yurtici cargoEventId/documentEventId to our shipping_status enum.
     *
     * @return array{0: string, 1: string}
     */
    protected function mapStatus(object $detail): array
    {
        $rejectFlag = (int) ($detail->rejectFlag ?? 0);
        $cargoEventId = strtoupper((string) ($detail->cargoEventId ?? ''));
        $documentEventId = strtoupper((string) ($detail->documentEventId ?? ''));
        $deliveryDate = (string) ($detail->deliveryDate ?? '');

        if ($rejectFlag === 1) {
            return ['failed', 'Teslim başarısız'];
        }

        if ($cargoEventId === 'OK' && $deliveryDate !== '') {
            return ['delivered', 'Teslim edildi'];
        }

        return match (true) {
            in_array($documentEventId, ['BD'], true) => ['processing', 'Belge düzenlendi'],
            in_array($cargoEventId, ['DA', 'DAGITIMDA'], true) => ['out_for_delivery', 'Şubede dağıtımda'],
            in_array($cargoEventId, ['SS', 'TR', 'TRANSFER', 'YOLDA'], true) => ['in_transit', 'Kargo yolda'],
            in_array($cargoEventId, ['IADE', 'IA'], true) => ['returned', 'İade edildi'],
            default => ['processing', 'Kargo işleniyor'],
        };
    }

    /**
     * @return array<int, array{date: ?string, status: string, location: string}>
     */
    protected function buildHistory(object $detail): array
    {
        $history = [];
        if (! empty($detail->cargoLifeCycle)) {
            $cycles = is_array($detail->cargoLifeCycle) ? $detail->cargoLifeCycle : [$detail->cargoLifeCycle];
            foreach ($cycles as $cycle) {
                $history[] = [
                    'date' => $this->formatYurticiDate($cycle->eventDate ?? null, $cycle->eventTime ?? null),
                    'status' => (string) ($cycle->eventExplanation ?? $cycle->eventId ?? ''),
                    'location' => (string) ($cycle->unitName ?? ''),
                ];
            }
        }

        return $history;
    }

    protected function extractDocId(object $result): ?string
    {
        if (empty($result->specialFieldDataArray)) {
            return null;
        }

        $fields = is_array($result->specialFieldDataArray)
            ? $result->specialFieldDataArray
            : [$result->specialFieldDataArray];

        foreach ($fields as $field) {
            if ((string) ($field->specialFieldName ?? '') === '53') {
                return (string) ($field->specialFieldValue ?? '');
            }
        }

        return null;
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $digits = ltrim($digits, '0');
        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        return $digits;
    }

    protected function formatYurticiDate(?string $date, ?string $time = null): ?string
    {
        if (empty($date) || strlen($date) < 8) {
            return null;
        }
        $iso = sprintf('%s-%s-%s', substr($date, 0, 4), substr($date, 4, 2), substr($date, 6, 2));
        if (! empty($time) && strlen($time) >= 6) {
            $iso .= sprintf(' %s:%s:%s', substr($time, 0, 2), substr($time, 2, 2), substr($time, 4, 2));
        }

        return $iso;
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
