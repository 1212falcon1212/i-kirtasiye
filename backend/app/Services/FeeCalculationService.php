<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Setting;

class FeeCalculationService
{
    protected string $feeMode;
    protected float $flatServiceFee;
    protected float $commissionPercentage;
    protected float $withholdingTaxRate;
    protected bool $serviceFeeEnabled;

    public function __construct()
    {
        $this->feeMode = (string) Setting::getValue('commission.fee_mode', 'flat');
        $this->flatServiceFee = (float) Setting::getValue('commission.flat_service_fee', 50);
        $this->commissionPercentage = (float) Setting::getValue('commission.commission_percentage', 10);
        $this->withholdingTaxRate = (float) Setting::getValue('commission.withholding_tax_rate', 1.00);
        $this->serviceFeeEnabled = (bool) Setting::getValue('commission.enabled', true);
    }

    /**
     * Sipariş kalemi için tüm kesintileri hesapla
     * flat: Sabit hizmet bedeli (flatFeeShare dışarıdan dağıtılır)
     * percentage: Satış tutarının %'si
     * category: Kategori komisyon oranı
     */
    public function calculateFees(
        float $totalPrice,
        float $flatFeeShare = 0,
        float $shippingCostShare = 0,
        ?float $categoryCommissionRate = null,
        ?float $vatRate = null
    ): array {
        if (!$this->serviceFeeEnabled) {
            $serviceFeeAmount = 0;
            $commissionRate = 0;
        } else {
            switch ($this->feeMode) {
                case 'percentage':
                    $serviceFeeAmount = $totalPrice * ($this->commissionPercentage / 100);
                    $commissionRate = $this->commissionPercentage;
                    break;
                case 'category':
                    $rate = $categoryCommissionRate ?? 0;
                    $serviceFeeAmount = $totalPrice * ($rate / 100);
                    $commissionRate = $rate;
                    break;
                default: // flat
                    $serviceFeeAmount = $flatFeeShare;
                    $commissionRate = 0;
                    break;
            }
        }

        // Stopaj (KDV hariç tutar üzerinden hesaplanır)
        $vatRate = $vatRate ?? 20;
        $priceExclVat = $totalPrice / (1 + $vatRate / 100);
        $withholdingTax = $priceExclVat * ($this->withholdingTaxRate / 100);

        // Toplam kesinti
        $totalFees = $serviceFeeAmount + $withholdingTax + $shippingCostShare;

        // Net satıcı tutarı
        $netSellerAmount = $totalPrice - $totalFees;

        return [
            'total_price' => round($totalPrice, 2),
            'commission_rate' => round($commissionRate, 2),
            'commission_amount' => round($serviceFeeAmount, 2),
            'service_fee_amount' => round($serviceFeeAmount, 2),
            'flat_service_fee' => $this->flatServiceFee,
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
     * Mevcut sipariş kalemine kesintileri uygula
     */
    public function applyFeesToOrderItem(OrderItem $orderItem, float $flatFeeShare = 0, float $shippingCostShare = 0): void
    {
        $categoryRate = null;
        if ($this->feeMode === 'category') {
            $categoryRate = (float) ($orderItem->product?->category?->commission_rate ?? 0);
        }

        $vatRate = (float) ($orderItem->product?->category?->vat_rate ?? 20);

        $fees = $this->calculateFees(
            (float) $orderItem->total_price,
            $flatFeeShare,
            $shippingCostShare,
            $categoryRate,
            $vatRate
        );

        $orderItem->update([
            'commission_rate' => $fees['commission_rate'],
            'commission_amount' => $fees['commission_amount'],
            'marketplace_fee' => $fees['marketplace_fee'],
            'withholding_tax' => $fees['withholding_tax'],
            'shipping_cost_share' => $fees['shipping_cost_share'],
            'net_seller_amount' => $fees['net_seller_amount'],
            'seller_payout_amount' => $fees['net_seller_amount'],
        ]);
    }

    /**
     * Siparişteki tüm kalemlere kesintileri uygula
     * flat: Sabit hizmet bedeli satıcı bazında dağıtılır
     * percentage/category: Her item için ayrı hesaplanır
     */
    public function applyFeesToOrder($order): array
    {
        $totalServiceFee = 0;
        $totalWithholdingTax = 0;
        $totalShippingShare = 0;
        $totalNetSeller = 0;

        // Kargo payını satıcılara dağıt
        $shippingCost = (float) ($order->shipping_cost ?? 0);
        $itemCount = $order->items->count();
        $shippingPerItem = $itemCount > 0 ? $shippingCost / $itemCount : 0;

        // Sabit hizmet bedelini satıcı bazında dağıt (sadece flat modda kullanılır)
        $sellerItems = $order->items->groupBy('seller_id');
        foreach ($sellerItems as $sellerId => $items) {
            $flatFeePerItem = ($this->feeMode === 'flat' && $this->serviceFeeEnabled)
                ? $this->flatServiceFee / $items->count()
                : 0;

            foreach ($items as $item) {
                $this->applyFeesToOrderItem($item, $flatFeePerItem, $shippingPerItem);
                $item->refresh();

                $totalServiceFee += $item->commission_amount;
                $totalWithholdingTax += $item->withholding_tax;
                $totalShippingShare += $item->shipping_cost_share;
                $totalNetSeller += $item->net_seller_amount;
            }
        }

        return [
            'total_commission' => round($totalServiceFee, 2),
            'total_service_fee' => round($totalServiceFee, 2),
            'total_marketplace_fee' => 0,
            'total_withholding_tax' => round($totalWithholdingTax, 2),
            'total_shipping_share' => round($totalShippingShare, 2),
            'total_net_seller' => round($totalNetSeller, 2),
            'platform_revenue' => round($totalServiceFee, 2),
        ];
    }

    /**
     * Kesinti özetini formatla (görüntüleme için)
     */
    public function formatFeeBreakdown(OrderItem $orderItem): array
    {
        $serviceFeeAmount = (float) $orderItem->commission_amount;

        // Mode-specific label
        switch ($this->feeMode) {
            case 'percentage':
                $feeLabel = "Komisyon (%{$this->commissionPercentage})";
                break;
            case 'category':
                $rate = (float) $orderItem->commission_rate;
                $feeLabel = "Kategori Komisyonu (%{$rate})";
                break;
            default:
                $feeLabel = 'Hizmet Bedeli (sabit)';
                break;
        }

        return [
            [
                'label' => 'Ürün Toplamı',
                'value' => $orderItem->total_price,
                'formatted' => '₺' . number_format((float) $orderItem->total_price, 2, ',', '.'),
                'type' => 'subtotal',
            ],
            [
                'label' => $feeLabel,
                'value' => -$serviceFeeAmount,
                'formatted' => '-₺' . number_format($serviceFeeAmount, 2, ',', '.'),
                'type' => 'deduction',
            ],
            [
                'label' => 'Stopaj (%' . $this->withholdingTaxRate . ' KDV hariç)',
                'value' => -$orderItem->withholding_tax,
                'formatted' => '-₺' . number_format((float) $orderItem->withholding_tax, 2, ',', '.'),
                'type' => 'deduction',
            ],
            [
                'label' => 'Kargo Payı',
                'value' => -$orderItem->shipping_cost_share,
                'formatted' => '-₺' . number_format((float) $orderItem->shipping_cost_share, 2, ',', '.'),
                'type' => 'deduction',
                'visible' => $orderItem->shipping_cost_share > 0,
            ],
            [
                'label' => 'Net Hakediş',
                'value' => $orderItem->net_seller_amount,
                'formatted' => '₺' . number_format((float) $orderItem->net_seller_amount, 2, ',', '.'),
                'type' => 'total',
            ],
        ];
    }

    /**
     * Satıcı bazlı kesinti özeti
     */
    public function getSellerFeesSummary($order, int $sellerId): array
    {
        $items = $order->items->where('seller_id', $sellerId);

        $totalSales = $items->sum('total_price');
        $totalCommission = $items->sum('commission_amount');
        $totalWithholdingTax = $items->sum('withholding_tax');
        $totalShippingShare = $items->sum('shipping_cost_share');
        $totalNetAmount = $items->sum('net_seller_amount');

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
     * Oranları getir
     */
    public function getRates(): array
    {
        return [
            'fee_mode' => $this->feeMode,
            'flat_service_fee' => $this->flatServiceFee,
            'commission_percentage' => $this->commissionPercentage,
            'withholding_tax_rate' => $this->withholdingTaxRate,
            'service_fee_enabled' => $this->serviceFeeEnabled,
            'commission_enabled' => $this->serviceFeeEnabled,
        ];
    }
}
