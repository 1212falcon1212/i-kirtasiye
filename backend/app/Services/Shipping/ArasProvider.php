<?php

namespace App\Services\Shipping;

use App\Interfaces\ShipmentResult;
use App\Interfaces\ShippingProviderInterface;
use App\Interfaces\TrackingResult;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use SoapClient;

/**
 * Aras Kargo Provider - Full Integration
 *
 * İki ayrı WSDL kullanılır:
 * 1. Sipariş servisi (SetOrder, CancelDispatch, GetOrderWithIntegrationCode): arascargoservice.asmx
 * 2. Sorgulama servisi (GetQueryJSON, GetQueryXML): ArasCargoIntegrationService.svc
 *
 * Döküman: SetOrder--Online Bilgi Gönderimi.docx + Sevkiyat Test Senaryoları.docx
 */
class ArasProvider implements ShippingProviderInterface
{
    protected array $config;

    protected bool $enabled;

    protected bool $testMode;

    // Sipariş oluşturma servisi (SetOrder, CancelDispatch, GetOrderWithIntegrationCode)
    private const ORDER_TEST_URL = 'https://customerservicestest.araskargo.com.tr/arascargoservice/arascargoservice.asmx?wsdl';

    private const ORDER_PROD_URL = 'https://customerws.araskargo.com.tr/arascargoservice.asmx?wsdl';

    // Sorgulama servisi (GetQueryJSON, GetQueryXML)
    private const QUERY_TEST_URL = 'https://customerservicestest.araskargo.com.tr/ArasCargoIntegrationService.svc?wsdl';

    private const QUERY_PROD_URL = 'https://customerservices.araskargo.com.tr/ArasCargoCustomerIntegrationService/ArasCargoIntegrationService.svc?wsdl';

    private const TRACKING_BASE_URL = 'https://kargotakip.araskargo.com.tr/mainpage.aspx?code=';

    /**
     * DURUM KODU eşleştirmesi (Aras Kargo dokümantasyonundan)
     * 1: Çıkış Şubesinde, 2: Yolda, 3: Teslimat Şubesinde,
     * 4: Teslimatta, 5: Parçalı Teslimat, 6: Teslim Edildi, 7: Yönlendirildi
     */
    private const STATUS_MAP = [
        1 => ['code' => 'processing', 'label' => 'Çıkış Şubesinde'],
        2 => ['code' => 'in_transit', 'label' => 'Yolda'],
        3 => ['code' => 'out_for_delivery', 'label' => 'Teslimat Şubesinde'],
        4 => ['code' => 'out_for_delivery', 'label' => 'Teslimatta'],
        5 => ['code' => 'delivered', 'label' => 'Parçalı Teslimat'],
        6 => ['code' => 'delivered', 'label' => 'Teslim Edildi'],
        7 => ['code' => 'in_transit', 'label' => 'Yönlendirildi'],
    ];

    /**
     * TİP KODU: 1=Normal, 2=Yönlendirildi, 3=İade edildi
     */
    private const TYPE_RETURNED = 3;

    /**
     * DEVİR KODLARI ve NEDENLERİ
     */
    private const DEVIR_REASONS = [
        'AY' => 'Adres yanlış/yetersiz',
        'NT' => 'Uğrama notu bırakıldı',
        'MD' => 'Mobil dağıtım gününde teslimata çıkarılacak',
        'TL' => 'Tatil dolayısıyla teslimat yapılamadı',
        'SA' => 'Müşteri kargosunu şubeden alacak',
        'AD' => 'Alıcı adresi dağıtım alanı dışında',
        'TG' => 'Teslimat gün içerisinde yapılamadı',
        'AA' => 'Alıcı adreste tanınmıyor',
        'HT' => 'Hasarlı/tazminlik kargo',
        'KG' => 'Kargo gümrükte',
        'UR' => 'Alıcı taşıma ücretini ödemeyi reddetti',
        'KE' => 'Alıcı kargoyu kabul etmiyor',
        'TT' => 'Tercihli teslimat',
        'PS' => 'Parça sorunlu/teslim edilemedi',
        'TO' => 'Toplumsal olaylar',
    ];

    public function __construct()
    {
        $this->testMode = (bool) Setting::getValue('shipping.aras_test_mode', true);
        $this->config = [
            'customer_code' => Setting::getValue('shipping.aras_customer_code', ''),
            'username' => Setting::getValue('shipping.aras_username', ''),
            'password' => Setting::getValue('shipping.aras_password', ''),
            'configuration_id' => Setting::getValue('shipping.aras_configuration_id', ''),
            'sender_name' => Setting::getValue('shipping.aras_sender_name', ''),
            'sender_phone' => Setting::getValue('shipping.aras_sender_phone', ''),
            'sender_address' => Setting::getValue('shipping.aras_sender_address', ''),
            'sender_city' => Setting::getValue('shipping.aras_sender_city', ''),
            'sender_district' => Setting::getValue('shipping.aras_sender_district', ''),
            'tracking_username' => Setting::getValue('shipping.aras_tracking_username', ''),
            'tracking_password' => Setting::getValue('shipping.aras_tracking_password', ''),
            'tracking_account_id' => Setting::getValue('shipping.aras_tracking_account_id', ''),
        ];
        $this->enabled = (bool) Setting::getValue('shipping.aras_enabled', false);
    }

    public function getName(): string
    {
        return 'aras';
    }

    public function isAvailable(): bool
    {
        return $this->enabled
            && ! empty($this->config['username'])
            && ! empty($this->config['password']);
    }

    /**
     * Sipariş oluştur — SetOrder metodu (arascargoservice.asmx)
     *
     * Döküman: SetOrder--Online Bilgi Gönderimi.docx
     *
     * Payload yapısı (örnek PHP + SOAP XML dökümanı):
     * SetOrder({
     *   orderInfo: { Order: [ { UserName, Password, ... PieceDetails: [ {...} ] } ] },
     *   userName: "...",
     *   password: "...",
     * })
     *
     * $senderInfo keys:
     * - name, address, city, district, phone (zorunlu)
     * - piece_count (opsiyonel, yoksa item quantity sum'dan hesaplanır)
     * - is_cod (opsiyonel, yoksa order->payment_method=cod'dan türetilir)
     * - cod_amount (opsiyonel, yoksa order->total_amount)
     * - cod_collection_type (opsiyonel, 0=Nakit, 1=Kredi Kartı, default 0)
     */
    public function createShipment(Order $order, array $senderInfo): ShipmentResult
    {
        if (! $this->isAvailable()) {
            return ShipmentResult::failure('Aras Kargo entegrasyonu aktif değil.');
        }

        try {
            $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
            $receiverPhone = preg_replace('/\D/', '', (string) ($shippingAddress['phone'] ?? ''));

            $integrationCode = $this->generateIntegrationCode($order);
            $pieceCount = $this->resolvePieceCount($order, $senderInfo);
            $pieceDetails = $this->buildPieceDetails($integrationCode, $pieceCount, $order);
            [$totalDesi, $totalWeight] = $this->resolveMeasurements($order, $senderInfo, $pieceCount);

            $isCod = (bool) ($senderInfo['is_cod'] ?? ($order->payment_method === 'cod'));
            $codAmount = (float) ($senderInfo['cod_amount'] ?? ($isCod ? $order->total_amount : 0));
            $codCollectionType = (string) ($senderInfo['cod_collection_type'] ?? '0');

            $orderInfo = new \stdClass;
            $orderInfo->UserName = $this->config['username'];
            $orderInfo->Password = $this->config['password'];
            $orderInfo->TradingWaybillNumber = substr((string) $order->order_number, 0, 16);
            $orderInfo->InvoiceNumber = substr((string) $order->order_number, 0, 20);
            $orderInfo->IntegrationCode = $integrationCode;
            $orderInfo->ReceiverName = $this->truncate($shippingAddress['name'] ?? ($order->user->name ?? ''), 100);
            $orderInfo->ReceiverAddress = $this->truncate($shippingAddress['address'] ?? '', 250);
            $orderInfo->ReceiverPhone1 = $receiverPhone;
            $orderInfo->ReceiverCityName = $this->truncate($shippingAddress['city'] ?? '', 32);
            $orderInfo->ReceiverTownName = $this->truncate($shippingAddress['district'] ?? '', 32);
            $orderInfo->PieceCount = $pieceCount;
            $orderInfo->VolumetricWeight = $totalDesi;
            $orderInfo->Weight = $totalWeight;
            $orderInfo->PayorTypeCode = 1; // Gönderici öder
            $orderInfo->IsWorldWide = 0;   // Yurtiçi

            if ($isCod) {
                $orderInfo->IsCod = 1;
                $orderInfo->CodAmount = $codAmount;
                $orderInfo->CodCollectionType = $codCollectionType; // 0=Nakit, 1=Kredi Kartı
                $orderInfo->CodBillingType = '0'; // Döküman: sabit "0"
            } else {
                $orderInfo->IsCod = 0;
            }

            $orderInfo->Description = 'Sipariş No: '.$order->order_number;
            $orderInfo->PieceDetails = $pieceDetails;

            $this->logRequest($order, 'create', $this->maskCredentials((array) $orderInfo));

            $client = $this->createOrderSoapClient();
            $response = $client->SetOrder([
                'orderInfo' => ['Order' => [$orderInfo]],
                'userName' => $this->config['username'],
                'password' => $this->config['password'],
            ]);

            $resultInfo = $this->extractSetOrderResult($response);
            $resultCode = (int) ($resultInfo['ResultCode'] ?? -1);
            $resultMessage = (string) ($resultInfo['ResultMessage'] ?? '');

            $logData = ['ResultCode' => $resultCode, 'ResultMessage' => $resultMessage];

            if ($resultCode === 0) {
                $this->logResponse($order, 'create', $logData, 200);

                return ShipmentResult::success(
                    trackingNumber: $integrationCode,
                    message: 'Aras Kargo gönderisi başarıyla oluşturuldu. Kargo takip numarası şube işlemi sonrası atanacaktır.',
                );
            }

            $errorMsg = $resultMessage ?: "Hata kodu: {$resultCode}";
            $this->logResponse($order, 'create', $logData, 400, $errorMsg);

            return ShipmentResult::failure($errorMsg, 400);
        } catch (\Throwable $e) {
            $this->logResponse($order, 'create', [], 503, $e->getMessage());
            Log::error('Aras createShipment error: '.$e->getMessage());

            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    /**
     * SetOrder sonrası doğrulama — GetOrderWithIntegrationCode
     *
     * @return array<string, mixed>
     */
    public function getOrderWithIntegrationCode(string $integrationCode): array
    {
        try {
            $client = $this->createOrderSoapClient();
            $response = $client->GetOrderWithIntegrationCode([
                'integrationCode' => $integrationCode,
                'userName' => $this->config['username'],
                'password' => $this->config['password'],
            ]);

            $result = $response->GetOrderWithIntegrationCodeResult ?? null;

            return $result ? (array) $result : [];
        } catch (\Throwable $e) {
            Log::warning('Aras getOrderWithIntegrationCode error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Kargo iptal — CancelDispatch (arascargoservice.asmx)
     */
    public function cancelShipment(Order $order): ShipmentResult
    {
        if (! $this->isAvailable()) {
            return ShipmentResult::failure('Aras Kargo entegrasyonu aktif değil.');
        }

        try {
            $integrationCode = $order->tracking_number ?: $this->generateIntegrationCode($order);
            $requestParams = [
                'userName' => $this->config['username'],
                'password' => $this->config['password'],
                'integrationCode' => $integrationCode,
            ];

            $this->logRequest($order, 'cancel', $this->maskCredentials($requestParams));

            $client = $this->createOrderSoapClient();
            $response = $client->CancelDispatch($requestParams);

            if (isset($response->CancelDispatchResult)) {
                $resultCode = (int) $response->CancelDispatchResult;

                if ($resultCode === 0 || $resultCode === 1) {
                    $this->logResponse($order, 'cancel', ['ResultCode' => $resultCode], 200);

                    return ShipmentResult::success(
                        trackingNumber: (string) ($order->tracking_number ?? ''),
                        message: 'Kargo iptal edildi.'
                    );
                }

                $this->logResponse($order, 'cancel', ['ResultCode' => $resultCode], 400, "İptal hata kodu: {$resultCode}");

                return ShipmentResult::failure('İptal işlemi yapılamadı. Kod: '.$resultCode, 400);
            }

            return ShipmentResult::failure('Beklenmedik API yanıtı.', 500);
        } catch (\Throwable $e) {
            $this->logResponse($order, 'cancel', [], 503, $e->getMessage());

            return ShipmentResult::failure($e->getMessage(), 503);
        }
    }

    /**
     * Kargo takip — QueryType=1 (temel bilgi) + QueryType=9 (hareket geçmişi)
     */
    public function trackShipment(Order $order): TrackingResult
    {
        if (! $this->isAvailable()) {
            return TrackingResult::failure('Aras Kargo entegrasyonu aktif değil.');
        }

        try {
            $integrationCode = $order->tracking_number ?: $this->generateIntegrationCode($order);

            // QueryType=1: MÖK ile kargo bilgisi
            $mainData = $this->query(1, ['IntegrationCode' => $integrationCode]);

            if (empty($mainData)) {
                return TrackingResult::fromStatus('pending', 'Sipariş hazırlanıyor');
            }

            $record = is_array($mainData) && isset($mainData[0]) ? $mainData[0] : $mainData;

            $durumKodu = (int) ($record['DURUM_KODU'] ?? $record['DURUM KODU'] ?? 0);
            $tipKodu = (int) ($record['TIP_KODU'] ?? $record['TİP KODU'] ?? $record['TIP KODU'] ?? 1);
            $trackingNo = (string) ($record['KARGO_TAKIP_NO'] ?? $record['KARGO TAKIP NO'] ?? $record['KARGO TAKİP NO'] ?? '');
            $durumu = (string) ($record['DURUMU'] ?? '');
            $iadeSebebi = (string) ($record['IADE_SEBEBI'] ?? $record['İADE SEBEBİ'] ?? $record['IADE SEBEBİ'] ?? '');
            $teslimTarihi = (string) ($record['TESLIM_TARIHI'] ?? $record['TESLİM TARİHİ'] ?? $record['TESLIM TARİHİ'] ?? '');
            $currentLocation = (string) ($record['VARIS_SUBE'] ?? $record['VARIŞ ŞUBE'] ?? $record['VARIŞ ŞUBESİ'] ?? '');

            if ($tipKodu === self::TYPE_RETURNED) {
                $status = 'returned';
                $statusLabel = 'İade Edildi';
                if ($iadeSebebi) {
                    $statusLabel .= ' - '.$iadeSebebi;
                }
            } else {
                $mapped = $this->mapStatus($durumKodu);
                $status = $mapped['code'];
                $statusLabel = $durumu ?: $mapped['label'];
            }

            $history = $this->getTrackingHistory($order);
            $trackingUrl = $this->buildTrackingUrl($trackingNo, $integrationCode);

            $result = new TrackingResult(
                success: true,
                status: $status,
                statusLabel: $statusLabel,
                trackingNumber: $trackingNo,
                trackingUrl: $trackingUrl,
                currentLocation: $currentLocation,
                lastUpdate: $teslimTarihi ?: null,
                history: $history,
            );

            $this->logResponse($order, 'track', [
                'durum_kodu' => $durumKodu,
                'tip_kodu' => $tipKodu,
                'tracking_no' => $trackingNo,
                'status' => $status,
            ], 200);

            return $result;
        } catch (\Throwable $e) {
            Log::error('Aras trackShipment error: '.$e->getMessage());

            return TrackingResult::failure($e->getMessage());
        }
    }

    /**
     * Barkod/etiket HTML üret
     */
    public function getLabel(Order $order): ?string
    {
        try {
            $detail = $this->isAvailable() ? $this->getDetailedInfo($order) : [];
            $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];

            $integrationCode = $order->tracking_number ?: $this->generateIntegrationCode($order);
            $pieceCount = (int) ($detail['adet'] ?? $this->resolvePieceCount($order, []));
            $pieces = $this->buildLabelPieces($integrationCode, $pieceCount);

            $isCod = $order->payment_method === 'cod';

            $labelData = [
                'order' => $order,
                'trackingNumber' => $order->tracking_number ?? $integrationCode,
                'senderName' => $this->config['sender_name'],
                'senderPhone' => $this->config['sender_phone'],
                'senderAddress' => $this->config['sender_address'],
                'senderCity' => $this->config['sender_city'],
                'senderDistrict' => $this->config['sender_district'],
                'receiverName' => $shippingAddress['name'] ?? ($order->user->name ?? ''),
                'receiverPhone' => $shippingAddress['phone'] ?? '',
                'receiverAddress' => $shippingAddress['address'] ?? '',
                'receiverCity' => $shippingAddress['city'] ?? '',
                'receiverDistrict' => $shippingAddress['district'] ?? '',
                'pieceCount' => $pieceCount,
                'pieces' => $pieces,
                'desi' => $detail['desi'] ?? '-',
                'weight' => $detail['agirlik'] ?? '-',
                'orderDate' => $order->created_at ? $order->created_at->format('d.m.Y') : '',
                'integrationCode' => $integrationCode,
                'isCod' => $isCod,
                'codAmount' => $isCod ? (float) $order->total_amount : 0.0,
                'codCollectionType' => '0', // Nakit — order'da saklı değilse default
            ];

            return View::make('shipping.aras-label', $labelData)->render();
        } catch (\Throwable $e) {
            Log::error('Aras getLabel error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Kargo hareket geçmişi — QueryType=9
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTrackingHistory(Order $order): array
    {
        try {
            $integrationCode = $order->tracking_number ?: $this->generateIntegrationCode($order);
            $data = $this->query(9, ['IntegrationCode' => $integrationCode]);

            if (empty($data)) {
                return [];
            }

            if (isset($data['İŞLEM TARİHİ']) || isset($data['ISLEM_TARIHI'])) {
                $data = [$data];
            }

            $history = [];
            foreach ($data as $item) {
                $history[] = [
                    'date' => $item['İŞLEM TARİHİ'] ?? $item['ISLEM_TARIHI'] ?? $item['İŞLEM_TARİHİ'] ?? '',
                    'location' => $item['BİRİM'] ?? $item['BIRIM'] ?? '',
                    'action' => $item['İŞLEM'] ?? $item['ISLEM'] ?? '',
                    'description' => $item['AÇIKLAMA'] ?? $item['ACIKLAMA'] ?? '',
                ];
            }

            return $history;
        } catch (\Throwable $e) {
            Log::warning('Aras getTrackingHistory error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Detaylı kargo bilgisi — QueryType=39
     *
     * @return array<string, mixed>
     */
    public function getDetailedInfo(Order $order): array
    {
        try {
            $integrationCode = $order->tracking_number ?: $this->generateIntegrationCode($order);
            $data = $this->query(39, [
                'CustomerCode' => $this->config['customer_code'],
                'IntegrationCode' => $integrationCode,
            ]);

            if (empty($data)) {
                return [];
            }

            $record = is_array($data) && isset($data[0]) ? $data[0] : $data;

            return [
                'tracking_number' => $record['KARGO_TAKIP_NO'] ?? $record['KARGO TAKİP NO'] ?? '',
                'irsaliye_numara' => $record['IRSALIYE_NUMARA'] ?? $record['İRSALİYE NUMARA'] ?? '',
                'gonderici' => $record['GONDERICI'] ?? $record['GÖNDERİCİ'] ?? '',
                'alici' => $record['ALICI'] ?? '',
                'cikis_sube' => $record['CIKIS_SUBE'] ?? $record['ÇIKIŞ ŞUBE'] ?? '',
                'varis_sube' => $record['VARIS_SUBE'] ?? $record['VARIŞ ŞUBE'] ?? '',
                'cikis_tarih' => $record['CIKIS_TARIH'] ?? $record['ÇIKIŞ TARİHİ'] ?? '',
                'adet' => $record['ADET'] ?? 1,
                'desi' => $record['KG_DESI'] ?? $record['DESİ'] ?? $record['DESI'] ?? '',
                'agirlik' => $record['HACIMSEL_AGIRLIK'] ?? $record['AGIRLIK'] ?? '',
                'tutar' => $record['TUTAR(KDV\'SİZ)'] ?? $record['TUTAR'] ?? '',
                'odeme_tipi' => $record['ODEME_TIPI'] ?? $record['ÖDEME TİPİ'] ?? '',
                'teslim_alan' => $record['TESLIM_ALAN'] ?? $record['TESLİM ALAN'] ?? '',
                'teslim_tarihi' => $record['TESLIM_TARIHI'] ?? $record['TESLİM TARİHİ'] ?? '',
                'teslim_saati' => $record['TESLIM_SAATI'] ?? $record['TESLİM SAATİ'] ?? '',
                'durum_kodu' => $record['DURUM_KODU'] ?? $record['DURUM KODU'] ?? '',
                'durumu' => $record['DURUMU'] ?? '',
                'iade_sebebi' => $record['IADE_SEBEBI'] ?? $record['İADE SEBEBİ'] ?? '',
                'devir_kodu' => $record['DEVIR_KODU'] ?? $record['DEVİR KODU'] ?? '',
                'iade' => $record['IADE'] ?? $record['İADE'] ?? '',
            ];
        } catch (\Throwable $e) {
            Log::warning('Aras getDetailedInfo error: '.$e->getMessage());

            return [];
        }
    }

    // ─── Private Helpers ────────────────────────────────────────────

    /**
     * Aras Kargo Sevkiyat Test Senaryoları dökümanı:
     * "Entegrasyon kodu benzersiz, minimum altı karakter olmalı ve rakamlardan oluşmalıdır."
     */
    public function generateIntegrationCode(Order $order): string
    {
        $digits = preg_replace('/\D/', '', (string) $order->order_number);
        if (strlen((string) $digits) < 6) {
            $digits = str_pad((string) $order->id, 6, '0', STR_PAD_LEFT).date('md');
        }

        return substr((string) $digits, 0, 32);
    }

    /**
     * Parça sayısı — senderInfo'da varsa onu al, yoksa item quantity sum
     */
    protected function resolvePieceCount(Order $order, array $senderInfo): int
    {
        if (isset($senderInfo['piece_count']) && (int) $senderInfo['piece_count'] > 0) {
            return (int) $senderInfo['piece_count'];
        }

        $sum = (int) $order->items()->sum('quantity');

        return max(1, $sum);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildPieceDetails(string $integrationCode, int $pieceCount, Order $order): array
    {
        $pieces = [];

        // Tek parça: BarcodeNumber == IntegrationCode (döküman test senaryosu)
        if ($pieceCount === 1) {
            $pieces[] = [
                'VolumetricWeight' => '1',
                'Weight' => '1',
                'BarcodeNumber' => $integrationCode,
                'ProductNumber' => '',
                'Description' => 'Sipariş No: '.$order->order_number,
            ];

            return $pieces;
        }

        // Çoklu parça: IntegrationCode + '01', '02', ...
        for ($i = 1; $i <= $pieceCount; $i++) {
            $pieces[] = [
                'VolumetricWeight' => '1',
                'Weight' => '1',
                'BarcodeNumber' => $integrationCode.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'ProductNumber' => '',
                'Description' => 'Parça '.$i.'/'.$pieceCount,
            ];
        }

        return $pieces;
    }

    /**
     * Etiket üretimi için parça bilgisi (barcode ve sıra)
     *
     * @return list<array{index: int, total: int, barcode: string}>
     */
    protected function buildLabelPieces(string $integrationCode, int $pieceCount): array
    {
        $pieces = [];
        if ($pieceCount === 1) {
            $pieces[] = [
                'index' => 1,
                'total' => 1,
                'barcode' => $integrationCode,
            ];

            return $pieces;
        }

        for ($i = 1; $i <= $pieceCount; $i++) {
            $pieces[] = [
                'index' => $i,
                'total' => $pieceCount,
                'barcode' => $integrationCode.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ];
        }

        return $pieces;
    }

    /**
     * Toplam desi ve ağırlık — senderInfo'da explicit varsa onu al
     *
     * @return array{0: string, 1: string}
     */
    protected function resolveMeasurements(Order $order, array $senderInfo, int $pieceCount): array
    {
        $desi = isset($senderInfo['total_desi']) ? (float) $senderInfo['total_desi'] : (float) $pieceCount;
        $weight = isset($senderInfo['total_weight']) ? (float) $senderInfo['total_weight'] : (float) $pieceCount;

        return [
            number_format(max(0.1, $desi), 2, '.', ''),
            number_format(max(0.1, $weight), 2, '.', ''),
        ];
    }

    /**
     * SetOrder cevabından ResultCode/ResultMessage çıkar
     *
     * @return array{ResultCode?: int, ResultMessage?: string}
     */
    protected function extractSetOrderResult($response): array
    {
        if (! isset($response->SetOrderResult)) {
            return [];
        }

        $setResult = $response->SetOrderResult;

        // Dönen yapı birkaç varyant olabilir:
        // 1. SetOrderResult.OrderResultInfo (tek)
        // 2. SetOrderResult.OrderResultInfo[] (array)
        // 3. SetOrderResult direkt ResultCode/ResultMessage (eski format)
        $info = $setResult->OrderResultInfo ?? $setResult;

        if (is_array($info)) {
            $info = $info[0] ?? [];
        }

        return [
            'ResultCode' => (int) ($info->ResultCode ?? -1),
            'ResultMessage' => (string) ($info->ResultMessage ?? ''),
        ];
    }

    /**
     * Log içinde parola/credentials maskele
     */
    protected function maskCredentials(array $data): array
    {
        $masked = $data;
        foreach (['Password', 'password', 'UserName', 'userName'] as $key) {
            if (isset($masked[$key])) {
                $masked[$key] = '***';
            }
        }

        return $masked;
    }

    /**
     * Field boyut limiti
     */
    protected function truncate(string $value, int $max): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }

    /**
     * GetQueryJSON ile sorgulama yap
     *
     * @return array<int|string, mixed>
     */
    private function query(int $queryType, array $params = []): array
    {
        $loginInfo = '<LoginInfo>'
            .'<UserName>'.htmlspecialchars($this->config['username']).'</UserName>'
            .'<Password>'.htmlspecialchars($this->config['password']).'</Password>'
            .'<CustomerCode>'.htmlspecialchars($this->config['customer_code']).'</CustomerCode>'
            .'</LoginInfo>';

        $queryInfo = '<QueryInfo><QueryType>'.$queryType.'</QueryType>';
        foreach ($params as $key => $value) {
            $queryInfo .= '<'.$key.'>'.htmlspecialchars((string) $value).'</'.$key.'>';
        }
        $queryInfo .= '</QueryInfo>';

        $client = $this->createQuerySoapClient();

        try {
            $response = $client->GetQueryJSON([
                'loginInfo' => $loginInfo,
                'queryInfo' => $queryInfo,
            ]);

            $jsonResult = $response->GetQueryJSONResult ?? null;
            if ($jsonResult) {
                $decoded = json_decode($jsonResult, true);
                if (is_array($decoded)) {
                    $qr = $decoded['QueryResult'] ?? $decoded;
                    if ($qr === null) {
                        return [];
                    }

                    if (isset($qr['Collection'])) {
                        return is_array($qr['Collection']) ? $qr['Collection'] : [$qr['Collection']];
                    }

                    if (is_array($qr)) {
                        $firstKey = array_key_first($qr);
                        if ($firstKey !== null && is_array($qr[$firstKey])) {
                            return $qr[$firstKey];
                        }

                        return $qr;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::info('Aras GetQueryJSON failed, falling back to GetQueryXML: '.$e->getMessage());
        }

        $response = $client->GetQueryXML([
            'loginInfo' => $loginInfo,
            'queryInfo' => $queryInfo,
        ]);

        $xmlResult = $response->GetQueryXMLResult ?? null;
        if (! $xmlResult) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlResult);
        libxml_use_internal_errors(false);

        if (! $xml) {
            return [];
        }

        $json = json_encode($xml);
        $decoded = json_decode($json ?: '{}', true);

        if (isset($decoded['Collection'])) {
            $collection = $decoded['Collection'];
            if (isset($collection[0])) {
                return $collection;
            }

            return [$collection];
        }

        return $decoded ?: [];
    }

    /**
     * Sipariş SOAP client (SetOrder, CancelDispatch, GetOrderWithIntegrationCode)
     */
    private function createOrderSoapClient(): SoapClient
    {
        $url = $this->testMode ? self::ORDER_TEST_URL : self::ORDER_PROD_URL;

        return new SoapClient($url, [
            'encoding' => 'utf-8',
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => 30,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ]);
    }

    /**
     * Sorgulama SOAP client (GetQueryJSON, GetQueryXML)
     */
    private function createQuerySoapClient(): SoapClient
    {
        $url = $this->testMode ? self::QUERY_TEST_URL : self::QUERY_PROD_URL;

        return new SoapClient($url, [
            'encoding' => 'utf-8',
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => 30,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ]);
    }

    /**
     * DURUM KODU → sistem status eşleştirmesi
     *
     * @return array{code: string, label: string}
     */
    protected function mapStatus(int $code): array
    {
        return self::STATUS_MAP[$code] ?? ['code' => 'pending', 'label' => 'Bekliyor'];
    }

    /**
     * Kargo takip URL'i oluştur
     *
     * 3 yöntem (döküman sırasıyla):
     * 1. Kargo takip numarası ile: kargotakip.araskargo.com.tr/mainpage.aspx?code={trackingNo}
     * 2. MÖK (sipariş no) ile: kargotakip.araskargo.com.tr/mainpage.aspx?accountid={}&sifre={}&alici_kod={mok}
     */
    public function buildTrackingUrl(?string $trackingNo, ?string $integrationCode): ?string
    {
        if ($trackingNo) {
            return self::TRACKING_BASE_URL.$trackingNo;
        }

        $accountId = $this->config['tracking_account_id'] ?? '';
        $sifre = $this->config['tracking_username'] ?? '';

        if ($accountId && $sifre && $integrationCode) {
            return 'https://kargotakip.araskargo.com.tr/mainpage.aspx?accountid='
                .urlencode($accountId)
                .'&sifre='.urlencode($sifre)
                .'&alici_kod='.urlencode($integrationCode);
        }

        return null;
    }

    /**
     * Devir kodu açıklaması
     */
    public static function getDevirReason(string $code): string
    {
        return self::DEVIR_REASONS[$code] ?? 'Bilinmeyen neden ('.$code.')';
    }

    // ─── Logging ────────────────────────────────────────────────────

    protected function logRequest(Order $order, string $action, array $request): void
    {
        if (! $order->exists) {
            return; // Test komutu — DB'ye log yazma
        }

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
        if (! $order->exists) {
            return;
        }

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
