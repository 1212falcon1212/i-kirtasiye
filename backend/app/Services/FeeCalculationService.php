<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Setting;

/**
 * Komisyon, sabit hizmet bedeli ve stopaj kesintilerini hesaplar.
 *
 * Hesaplama kuralları (T0 - 2026-05):
 *  - Her sipariş kalemine yüzdelik komisyon uygulanır (varsayılan %10).
 *    Komisyon, kalemin KDV dahil satış tutarı üzerinden hesaplanır.
 *  - Her siparişte sabit ₺50 hizmet bedeli alınır. Çoklu satıcılı
 *    siparişlerde her satıcı bağımsız ₺50 alır ve bu tutar satıcının
 *    item sayısına bölünerek kalemlere dağıtılır (mevcut davranış).
 *  - Stopaj, kalemin KDV HARİÇ tutarı üzerinden hesaplanır (varsayılan %1).
 *    KDV oranı önceliği: ürün > kategori > config('commission.default_vat_rate').
 *  - Hizmet bedeli sistemi pasifse hem komisyon hem hizmet bedeli sıfırlanır;
 *    stopaj her durumda uygulanır (mali zorunluluk).
 *
 * fee_mode legacy alanı:
 *  - 'flat'        → sadece sabit ₺50 hizmet bedeli (yüzdelik komisyon yok)
 *  - 'percentage'  → sadece yüzdelik komisyon (sabit hizmet bedeli yok)
 *  - 'category'    → kategori bazlı yüzdelik komisyon
 *  - 'combined'    → yeni varsayılan: yüzdelik komisyon + sabit hizmet bedeli
 *
 * @phpstan-type FeeBreakdown array{
 *     total_price: float,
 *     net_price: float,
 *     vat_amount: float,
 *     vat_rate: float,
 *     commission_rate: float,
 *     commission_amount: float,
 *     service_fee_amount: float,
 *     flat_service_fee: float,
 *     marketplace_fee_rate: float,
 *     marketplace_fee: float,
 *     withholding_tax_rate: float,
 *     withholding_tax: float,
 *     shipping_cost_share: float,
 *     total_fees: float,
 *     net_seller_amount: float
 * }
 */
class FeeCalculationService
{
    protected string $feeMode;

    protected float $flatServiceFee;

    protected float $commissionPercentage;

    protected float $withholdingTaxRate;

    protected float $defaultVatRate;

    protected bool $serviceFeeEnabled;

    public function __construct()
    {
        $this->feeMode = (string) Setting::getValue('commission.fee_mode', 'combined');
        $this->flatServiceFee = (float) Setting::getValue(
            'commission.flat_service_fee',
            (float) config('commission.flat_service_fee', 50)
        );
        $this->commissionPercentage = (float) Setting::getValue(
            'commission.commission_percentage',
            (float) config('commission.commission_percentage', 10)
        );
        $this->withholdingTaxRate = (float) Setting::getValue(
            'commission.withholding_tax_rate',
            (float) config('commission.withholding_tax_rate', 1.0)
        );
        $this->serviceFeeEnabled = (bool) Setting::getValue('commission.enabled', true);

        // config dosyası decimal (0-1) tutuyor; UI ile uyumlu olması için yüzdeye çeviriyoruz.
        $this->defaultVatRate = (float) config('commission.default_vat_rate', 0.20) * 100;
    }

    /**
     * Tek bir sipariş kalemi için komisyon, hizmet bedeli, stopaj ve net hakediş hesaplar.
     *
     * @param  float  $totalPrice  Kalemin KDV dahil satış toplamı (unit_price * qty)
     * @param  float  $flatFeeShare  Sabit hizmet bedelinin bu kaleme düşen payı (TL)
     * @param  float  $shippingCostShare  Kargo bedelinin bu kaleme düşen payı (TL)
     * @param  float|null  $categoryCommissionRate  fee_mode='category' iken kullanılan kategori komisyon yüzdesi
     * @param  float|null  $vatRate  KDV yüzdesi (örn. 20.0). null ise config default kullanılır.
     * @return FeeBreakdown
     */
    public function calculateFees(
        float $totalPrice,
        float $flatFeeShare = 0,
        float $shippingCostShare = 0,
        ?float $categoryCommissionRate = null,
        ?float $vatRate = null
    ): array {
        $effectiveVatRate = $vatRate ?? $this->defaultVatRate;

        // KDV ayrıştırması — Offer.price KDV dahil olarak depolanır.
        $netPrice = $totalPrice / (1 + $effectiveVatRate / 100);
        $vatAmount = $totalPrice - $netPrice;

        if (! $this->serviceFeeEnabled) {
            $commissionAmount = 0.0;
            $commissionRate = 0.0;
            $flatFeeAmount = 0.0;
        } else {
            switch ($this->feeMode) {
                case 'percentage':
                    // Sadece yüzdelik komisyon (legacy)
                    $commissionRate = $this->commissionPercentage;
                    $commissionAmount = $totalPrice * ($commissionRate / 100);
                    $flatFeeAmount = 0.0;
                    break;

                case 'category':
                    // Kategori bazlı komisyon (legacy)
                    $commissionRate = $categoryCommissionRate ?? 0.0;
                    $commissionAmount = $totalPrice * ($commissionRate / 100);
                    $flatFeeAmount = 0.0;
                    break;

                case 'flat':
                    // Sadece sabit hizmet bedeli (legacy)
                    $commissionRate = 0.0;
                    $commissionAmount = 0.0;
                    $flatFeeAmount = $flatFeeShare;
                    break;

                case 'combined':
                default:
                    // Yeni standart: %10 komisyon + ₺50 sabit hizmet bedeli
                    $commissionRate = $this->commissionPercentage;
                    $commissionAmount = $totalPrice * ($commissionRate / 100);
                    $flatFeeAmount = $flatFeeShare;
                    break;
            }
        }

        // Stopaj her zaman KDV hariç tutar üzerinden hesaplanır.
        $withholdingTax = $netPrice * ($this->withholdingTaxRate / 100);

        $serviceFeeAmount = $commissionAmount + $flatFeeAmount;
        $totalFees = $serviceFeeAmount + $withholdingTax + $shippingCostShare;
        $netSellerAmount = $totalPrice - $totalFees;

        return [
            'total_price' => round($totalPrice, 2),
            'net_price' => round($netPrice, 2),
            'vat_amount' => round($vatAmount, 2),
            'vat_rate' => round($effectiveVatRate, 2),
            'commission_rate' => round($commissionRate, 2),
            'commission_amount' => round($commissionAmount, 2),
            'service_fee_amount' => round($serviceFeeAmount, 2),
            'flat_service_fee' => round($flatFeeAmount, 2),
            'marketplace_fee_rate' => 0,
            'marketplace_fee' => 0,
            'withholding_tax_rate' => $this->withholdingTaxRate,
            'withholding_tax' => round($withholdingTax, 2),
            'shipping_cost_share' => round($shippingCostShare, 2),
            'total_fees' => round($totalFees, 2),
            'net_seller_amount' => round($netSellerAmount, 2),
        ];
    }

    /**
     * Mevcut bir OrderItem üzerinde kesintileri hesaplayıp persist eder.
     *
     * KDV oranı önceliği: product->category->vat_rate → config default.
     */
    public function applyFeesToOrderItem(OrderItem $orderItem, float $flatFeeShare = 0, float $shippingCostShare = 0): void
    {
        $categoryRate = null;
        if ($this->feeMode === 'category') {
            $categoryRate = (float) ($orderItem->product?->category?->commission_rate ?? 0);
        }

        $vatRate = $this->resolveVatRate($orderItem);

        $fees = $this->calculateFees(
            (float) $orderItem->total_price,
            $flatFeeShare,
            $shippingCostShare,
            $categoryRate,
            $vatRate
        );

        $orderItem->update([
            'commission_rate' => $fees['commission_rate'],
            'commission_amount' => $fees['commission_amount'] + $fees['flat_service_fee'],
            'marketplace_fee' => $fees['marketplace_fee'],
            'withholding_tax' => $fees['withholding_tax'],
            'shipping_cost_share' => $fees['shipping_cost_share'],
            'net_seller_amount' => $fees['net_seller_amount'],
            'seller_payout_amount' => $fees['net_seller_amount'],
        ]);
    }

    /**
     * Siparişteki tüm kalemlere kesintileri uygular.
     *
     * Sabit hizmet bedeli her satıcı için bağımsız uygulanır ve satıcının
     * item sayısına bölünerek kalemlere eşit dağıtılır.
     * Kargo bedeli kalem sayısına bölünerek dağıtılır.
     *
     * @return array{
     *     total_commission: float,
     *     total_service_fee: float,
     *     total_flat_fee: float,
     *     total_marketplace_fee: float,
     *     total_withholding_tax: float,
     *     total_shipping_share: float,
     *     total_net_seller: float,
     *     platform_revenue: float
     * }
     */
    public function applyFeesToOrder($order): array
    {
        $totalCommission = 0.0;
        $totalFlatFee = 0.0;
        $totalWithholdingTax = 0.0;
        $totalShippingShare = 0.0;
        $totalNetSeller = 0.0;

        // Kargo bedeli artık satıcı bazlı resolve edilir; her satıcı kendi
        // kargo ücretini ödemiş olur. Mevcut order.shipping_cost toplam kargo
        // tutarıdır; satıcı başına dağıtım ShippingFeeResolver ile yapılır.
        $shippingResolver = app(ShippingFeeResolver::class);

        $sellerSubtotals = $order->items
            ->groupBy('seller_id')
            ->map(fn ($items, $sid) => [
                'seller_id' => (int) $sid,
                'subtotal' => (float) $items->sum('total_price'),
            ])
            ->values()
            ->all();

        $perSellerShipping = $shippingResolver->resolveForCart($sellerSubtotals)['per_seller'];

        $sellerItems = $order->items->groupBy('seller_id');
        foreach ($sellerItems as $sellerId => $items) {
            // Her satıcı bağımsız sabit hizmet bedeli alır; satıcının item sayısına bölünür.
            $appliesFlatFee = $this->serviceFeeEnabled
                && in_array($this->feeMode, ['flat', 'combined'], true);

            $flatFeePerItem = $appliesFlatFee
                ? $this->flatServiceFee / $items->count()
                : 0.0;

            $sellerShipFee = $perSellerShipping[(int) $sellerId]['fee'] ?? 0.0;
            $shippingPerItem = $items->count() > 0
                ? $sellerShipFee / $items->count()
                : 0.0;

            foreach ($items as $item) {
                $this->applyFeesToOrderItem($item, $flatFeePerItem, $shippingPerItem);
                $item->refresh();

                // commission_amount kolonu artık (yüzdelik komisyon + sabit hizmet bedeli payı) toplamını tutuyor.
                $totalCommission += (float) $item->commission_amount;
                $totalFlatFee += $flatFeePerItem;
                $totalWithholdingTax += (float) $item->withholding_tax;
                $totalShippingShare += (float) $item->shipping_cost_share;
                $totalNetSeller += (float) $item->net_seller_amount;
            }
        }

        return [
            'total_commission' => round($totalCommission, 2),
            'total_service_fee' => round($totalCommission, 2),
            'total_flat_fee' => round($totalFlatFee, 2),
            'total_marketplace_fee' => 0,
            'total_withholding_tax' => round($totalWithholdingTax, 2),
            'total_shipping_share' => round($totalShippingShare, 2),
            'total_net_seller' => round($totalNetSeller, 2),
            'platform_revenue' => round($totalCommission, 2),
        ];
    }

    /**
     * Kesinti özetini formatla (görüntüleme için).
     *
     * @return list<array{label: string, value: float, formatted: string, type: string, visible?: bool}>
     */
    public function formatFeeBreakdown(OrderItem $orderItem): array
    {
        $serviceFeeAmount = (float) $orderItem->commission_amount;

        switch ($this->feeMode) {
            case 'percentage':
                $feeLabel = "Komisyon (%{$this->commissionPercentage})";
                break;
            case 'category':
                $rate = (float) $orderItem->commission_rate;
                $feeLabel = "Kategori Komisyonu (%{$rate})";
                break;
            case 'flat':
                $feeLabel = 'Hizmet Bedeli (sabit)';
                break;
            case 'combined':
            default:
                $feeLabel = "Komisyon (%{$this->commissionPercentage}) + Sabit Hizmet Bedeli";
                break;
        }

        return [
            [
                'label' => 'Ürün Toplamı',
                'value' => (float) $orderItem->total_price,
                'formatted' => '₺'.number_format((float) $orderItem->total_price, 2, ',', '.'),
                'type' => 'subtotal',
            ],
            [
                'label' => $feeLabel,
                'value' => -$serviceFeeAmount,
                'formatted' => '-₺'.number_format($serviceFeeAmount, 2, ',', '.'),
                'type' => 'deduction',
            ],
            [
                'label' => 'Stopaj (%'.$this->withholdingTaxRate.' KDV hariç)',
                'value' => -(float) $orderItem->withholding_tax,
                'formatted' => '-₺'.number_format((float) $orderItem->withholding_tax, 2, ',', '.'),
                'type' => 'deduction',
            ],
            [
                'label' => 'Kargo Payı',
                'value' => -(float) $orderItem->shipping_cost_share,
                'formatted' => '-₺'.number_format((float) $orderItem->shipping_cost_share, 2, ',', '.'),
                'type' => 'deduction',
                'visible' => $orderItem->shipping_cost_share > 0,
            ],
            [
                'label' => 'Net Hakediş',
                'value' => (float) $orderItem->net_seller_amount,
                'formatted' => '₺'.number_format((float) $orderItem->net_seller_amount, 2, ',', '.'),
                'type' => 'total',
            ],
        ];
    }

    /**
     * Satıcı bazlı kesinti özeti.
     *
     * @return array{
     *     seller_id: int,
     *     total_sales: float,
     *     deductions: array{service_fee: float, withholding_tax: float, shipping_share: float},
     *     total_deductions: float,
     *     net_amount: float
     * }
     */
    public function getSellerFeesSummary($order, int $sellerId): array
    {
        $items = $order->items->where('seller_id', $sellerId);

        $totalSales = (float) $items->sum('total_price');
        $totalCommission = (float) $items->sum('commission_amount');
        $totalWithholdingTax = (float) $items->sum('withholding_tax');
        $totalShippingShare = (float) $items->sum('shipping_cost_share');
        $totalNetAmount = (float) $items->sum('net_seller_amount');

        return [
            'seller_id' => $sellerId,
            'total_sales' => round($totalSales, 2),
            'deductions' => [
                'service_fee' => round($totalCommission, 2),
                'withholding_tax' => round($totalWithholdingTax, 2),
                'shipping_share' => round($totalShippingShare, 2),
            ],
            'total_deductions' => round($totalCommission + $totalWithholdingTax + $totalShippingShare, 2),
            'net_amount' => round($totalNetAmount, 2),
        ];
    }

    /**
     * Tek tek hesaplama ya da public erişim için ayar paketini döner.
     *
     * @return array{
     *     fee_mode: string,
     *     flat_service_fee: float,
     *     commission_percentage: float,
     *     withholding_tax_rate: float,
     *     default_vat_rate: float,
     *     service_fee_enabled: bool,
     *     commission_enabled: bool
     * }
     */
    public function getRates(): array
    {
        return [
            'fee_mode' => $this->feeMode,
            'flat_service_fee' => $this->flatServiceFee,
            'commission_percentage' => $this->commissionPercentage,
            'withholding_tax_rate' => $this->withholdingTaxRate,
            'default_vat_rate' => $this->defaultVatRate,
            'service_fee_enabled' => $this->serviceFeeEnabled,
            'commission_enabled' => $this->serviceFeeEnabled,
        ];
    }

    /**
     * Bir OrderItem için geçerli KDV oranını çözümler.
     *
     * Öncelik sırası: ürün.kategori.vat_rate → config('commission.default_vat_rate').
     */
    protected function resolveVatRate(OrderItem $orderItem): float
    {
        $categoryVatRate = $orderItem->product?->category?->vat_rate;

        if ($categoryVatRate !== null) {
            return (float) $categoryVatRate;
        }

        return $this->defaultVatRate;
    }
}
