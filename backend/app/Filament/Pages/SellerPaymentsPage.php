<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\PayoutRequest;
use App\Models\SellerBankAccount;
use App\Models\SellerWallet;
use App\Models\Setting;
use App\Models\SubOrder;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SellerPaymentsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.seller-payments';

    protected static ?string $navigationLabel = 'Ödemeler';

    protected static ?string $title = 'Satıcı Ödemeleri';

    protected static ?string $navigationGroup = 'Finans';

    protected static ?int $navigationSort = 3;

    /**
     * Get all pending sub_orders as flat rows, sorted by order date desc.
     * Each row = one sub_order (one seller's portion of an order).
     */
    public function getPaymentRows(): array
    {
        $holdDays = (int) Setting::getValue('payment.hold_days', 35);
        $feeMode = (string) Setting::getValue('commission.fee_mode', 'flat');
        $flatServiceFee = (float) Setting::getValue('commission.flat_service_fee', 50);
        $commissionPercentage = (float) Setting::getValue('commission.commission_percentage', 10);
        $withholdingRate = (float) Setting::getValue('commission.withholding_tax_rate', 1.00);
        $serviceFeeEnabled = (bool) Setting::getValue('commission.enabled', true);

        // Collect all released sub_order IDs across all wallets
        $releasedSubOrderIds = WalletTransaction::where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('sub_order_id')
            ->pluck('sub_order_id')
            ->toArray();

        $releasedOrderIds = WalletTransaction::where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('order_id')
            ->whereNull('sub_order_id')
            ->pluck('order_id')
            ->toArray();

        // Get all pending sub_orders
        $subOrders = SubOrder::where('status', 'delivered')
            ->whereNotNull('buyer_confirmed_at')
            ->whereNotIn('id', $releasedSubOrderIds)
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid')->whereNotIn('id', $releasedOrderIds))
            ->with(['items.product', 'order', 'seller'])
            ->orderByDesc('created_at')
            ->get();

        $rows = [];

        foreach ($subOrders as $subOrder) {
            $soSales = 0;
            $soShipping = 0;
            $soItems = 0;
            $soCommissionFromItems = 0;
            $description = '';

            $soWithholdingSum = 0;

            foreach ($subOrder->items as $item) {
                $itemPrice = (float) $item->total_price;
                $soSales += $itemPrice;
                $soShipping += (float) ($item->shipping_cost_share ?? 0);
                $soItems += (int) $item->quantity;
                $soCommissionFromItems += (float) ($item->commission_amount ?? 0);

                // Stopaj: KDV hariç tutar üzerinden
                $vatRate = (float) ($item->product?->category?->vat_rate ?? 20);
                $soWithholdingSum += ($itemPrice / (1 + $vatRate / 100)) * ($withholdingRate / 100);
            }

            if ($soSales <= 0) {
                continue;
            }

            // Build description
            $firstItem = $subOrder->items->first();
            $description = $subOrder->order->order_number . ' kodlu siparis satisi.';

            // Calculate fees
            $serviceFee = 0;
            if ($serviceFeeEnabled) {
                switch ($feeMode) {
                    case 'percentage':
                        $serviceFee = $soSales * ($commissionPercentage / 100);
                        break;
                    case 'category':
                        $serviceFee = $soCommissionFromItems;
                        break;
                    default:
                        $serviceFee = $flatServiceFee;
                        break;
                }
            }

            $withholding = $soWithholdingSum;
            $netAmount = $soSales - $serviceFee - $withholding - $soShipping;

            $releaseDate = Carbon::parse($subOrder->buyer_confirmed_at)->addDays($holdDays);
            $daysRemaining = max(0, (int) now()->startOfDay()->diffInDays($releaseDate->startOfDay(), false));

            // Last payout date for this seller
            $lastPayout = PayoutRequest::where('seller_id', $subOrder->seller_id)
                ->where('status', PayoutRequest::STATUS_COMPLETED)
                ->orderByDesc('processed_at')
                ->first();

            $sellerName = $subOrder->seller?->business_name ?? $subOrder->seller?->name ?? '-';

            // Check IBAN
            $hasIban = SellerBankAccount::where('seller_id', $subOrder->seller_id)
                ->where('is_default', true)
                ->exists();

            $rows[] = [
                'sub_order_id' => $subOrder->id,
                'seller_id' => $subOrder->seller_id,
                'seller_name' => $sellerName,
                'seller_email' => $subOrder->seller?->email ?? '',
                'has_iban' => $hasIban,
                'order_number' => $subOrder->order->order_number,
                'order_date' => Carbon::parse($subOrder->order->created_at)->format('d/m/Y H:i'),
                'order_date_sort' => $subOrder->order->created_at,
                'description' => $description,
                'total_sales' => round($soSales, 2),
                'service_fee' => round($serviceFee, 2),
                'withholding' => round($withholding, 2),
                'shipping' => round($soShipping, 2),
                'net_amount' => round($netAmount, 2),
                'release_date' => $releaseDate->format('d/m/Y'),
                'days_remaining' => $daysRemaining,
                'last_payout_date' => $lastPayout ? Carbon::parse($lastPayout->processed_at)->format('d/m/Y H:i') : null,
                'payment_status' => 'Odenmedi',
            ];
        }

        // Sort by order date desc (newest first)
        usort($rows, fn($a, $b) => strcmp((string) $b['order_date_sort'], (string) $a['order_date_sort']));

        return $rows;
    }

    /**
     * Get summary stats
     */
    public function getSummaryStats(): array
    {
        $totalPendingBalance = SellerWallet::sum('pending_balance');
        $totalAvailableBalance = SellerWallet::sum('balance');
        $pendingPayoutCount = PayoutRequest::where('status', 'pending')->count();
        $pendingPayoutAmount = PayoutRequest::where('status', 'pending')->sum('amount');
        $completedThisMonth = PayoutRequest::where('status', 'completed')
            ->where('processed_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return [
            'total_pending' => round((float) $totalPendingBalance, 2),
            'total_available' => round((float) $totalAvailableBalance, 2),
            'pending_payout_count' => $pendingPayoutCount,
            'pending_payout_amount' => round((float) $pendingPayoutAmount, 2),
            'completed_this_month' => round((float) $completedThisMonth, 2),
        ];
    }

    /**
     * Mark a seller's all pending payments as processed
     */
    public function markAsProcessed(int $sellerId): void
    {
        $wallet = SellerWallet::where('seller_id', $sellerId)->first();
        if (!$wallet) {
            Notification::make()->title('Hata')->body('Satici cuzdan bilgisi bulunamadi')->danger()->send();
            return;
        }

        $bankAccount = SellerBankAccount::where('seller_id', $sellerId)->where('is_default', true)->first();
        if (!$bankAccount) {
            Notification::make()->title('Hata')->body('Saticinin banka hesap bilgisi bulunamadi')->danger()->send();
            return;
        }

        // Calculate total net from rows
        $rows = $this->getPaymentRows();
        $totalNet = 0;
        foreach ($rows as $row) {
            if ($row['seller_id'] === $sellerId) {
                $totalNet += $row['net_amount'];
            }
        }

        if ($totalNet <= 0) {
            Notification::make()->title('Hata')->body('Odenecek tutar bulunamadi')->danger()->send();
            return;
        }

        PayoutRequest::create([
            'seller_id' => $sellerId,
            'bank_account_id' => $bankAccount->id,
            'amount' => $totalNet,
            'status' => PayoutRequest::STATUS_APPROVED,
            'notes' => 'Admin tarafindan olusturuldu',
            'admin_notes' => 'Toplu odeme - ' . now()->format('d.m.Y'),
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        Notification::make()
            ->title('Odeme Olusturuldu')
            ->body(number_format($totalNet, 2, ',', '.') . ' TL tutarinda odeme talebi olusturuldu')
            ->success()
            ->send();
    }

    public ?int $detailSubOrderId = null;

    public function showDetail(int $subOrderId): void
    {
        $this->detailSubOrderId = $subOrderId;
    }

    public function backToList(): void
    {
        $this->detailSubOrderId = null;
    }

    /**
     * Get detail data for the modal
     */
    public function getDetailData(): ?array
    {
        if (!$this->detailSubOrderId) {
            return null;
        }

        $holdDays = (int) Setting::getValue('payment.hold_days', 35);
        $feeMode = (string) Setting::getValue('commission.fee_mode', 'flat');
        $flatServiceFee = (float) Setting::getValue('commission.flat_service_fee', 50);
        $commissionPercentage = (float) Setting::getValue('commission.commission_percentage', 10);
        $withholdingRate = (float) Setting::getValue('commission.withholding_tax_rate', 1.00);
        $serviceFeeEnabled = (bool) Setting::getValue('commission.enabled', true);

        $subOrder = SubOrder::with(['items.product', 'order', 'seller'])->find($this->detailSubOrderId);
        if (!$subOrder) {
            return null;
        }

        $bankAccount = SellerBankAccount::where('seller_id', $subOrder->seller_id)
            ->where('is_default', true)
            ->first();

        $soSales = 0;
        $soShipping = 0;
        $soCommissionFromItems = 0;
        $soWithholdingSum = 0;
        $items = [];

        foreach ($subOrder->items as $item) {
            $itemPrice = (float) $item->total_price;
            $soSales += $itemPrice;
            $soShipping += (float) ($item->shipping_cost_share ?? 0);
            $soCommissionFromItems += (float) ($item->commission_amount ?? 0);
            $items[] = [
                'product_name' => $item->product?->name ?? 'Urun',
                'image_url' => $item->product?->image_url ?? null,
                'quantity' => (int) $item->quantity,
                'unit_price' => round((float) $item->unit_price, 2),
                'total_price' => round((float) $item->total_price, 2),
            ];

            // Stopaj: KDV hariç tutar üzerinden
            $vatRate = (float) ($item->product?->category?->vat_rate ?? 20);
            $soWithholdingSum += ($itemPrice / (1 + $vatRate / 100)) * ($withholdingRate / 100);
        }

        $serviceFee = 0;
        $feeLabel = 'Hizmet Bedeli';
        if ($serviceFeeEnabled) {
            switch ($feeMode) {
                case 'percentage':
                    $serviceFee = $soSales * ($commissionPercentage / 100);
                    $feeLabel = "Komisyon (%{$commissionPercentage})";
                    break;
                case 'category':
                    $serviceFee = $soCommissionFromItems;
                    $feeLabel = 'Kategori Komisyonu';
                    break;
                default:
                    $serviceFee = $flatServiceFee;
                    break;
            }
        }

        $withholding = $soWithholdingSum;
        $totalDeductions = $serviceFee + $withholding + $soShipping;
        $netAmount = $soSales - $totalDeductions;
        $deductionPercent = $soSales > 0 ? round(($totalDeductions / $soSales) * 100, 1) : 0;
        $netPercent = 100 - $deductionPercent;

        $releaseDate = Carbon::parse($subOrder->buyer_confirmed_at)->addDays($holdDays);

        // Invoice info
        $invoice = Invoice::where('sub_order_id', $this->detailSubOrderId)->latest()->first();
        $invoiceData = null;
        if ($invoice) {
            $invoiceData = [
                'number' => $invoice->invoice_number,
                'erp_status' => $invoice->erp_status,
                'erp_invoice_url' => $invoice->erp_invoice_url,
                'erp_error' => $invoice->erp_error,
                'created_at' => $invoice->created_at?->format('d.m.Y H:i'),
            ];
        }

        return [
            'invoice' => $invoiceData,
            'order_number' => $subOrder->order->order_number,
            'seller_name' => $subOrder->seller?->business_name ?? $subOrder->seller?->name ?? '-',
            'seller_id' => $subOrder->seller_id,
            'order_date' => Carbon::parse($subOrder->order->created_at)->format('d.m.Y'),
            'order_time' => Carbon::parse($subOrder->order->created_at)->format('H:i'),
            'buyer_name' => $subOrder->order->buyer_name ?? '-',
            'buyer_phone' => $subOrder->order->buyer_phone ?? '-',
            'payment_method' => $subOrder->order->payment_method === 'cash' ? 'Nakit Odeme' : 'Kredi Karti',
            'status' => $subOrder->status,
            'release_date' => $releaseDate->format('d.m.Y'),
            'items' => $items,
            'total_sales' => round($soSales, 2),
            'service_fee' => round($serviceFee, 2),
            'fee_label' => $feeLabel,
            'withholding' => round($withholding, 2),
            'withholding_rate' => $withholdingRate,
            'shipping' => round($soShipping, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_amount' => round($netAmount, 2),
            'deduction_percent' => $deductionPercent,
            'net_percent' => $netPercent,
            'bank_holder' => $bankAccount?->account_holder ?? '-',
            'bank_iban' => $bankAccount?->formatted_iban ?? '-',
            'bank_name' => $bankAccount?->bank_name ?? '-',
        ];
    }

    /**
     * Mark single sub_order payment as completed (manual bank transfer).
     * Creates PayoutRequest + Invoice record (ERP sync happens from Faturalar page).
     */
    public function markSingleAsProcessed(int $subOrderId): void
    {
        $subOrder = SubOrder::with(['items.product', 'order', 'seller'])->find($subOrderId);
        if (!$subOrder) {
            Notification::make()->title('Hata')->body('Sub-order bulunamadi')->danger()->send();
            return;
        }

        $sellerId = $subOrder->seller_id;

        $rows = $this->getPaymentRows();
        $matchingRow = collect($rows)->firstWhere('sub_order_id', $subOrderId);
        if (!$matchingRow || $matchingRow['net_amount'] <= 0) {
            Notification::make()->title('Hata')->body('Odenecek tutar bulunamadi')->danger()->send();
            return;
        }
        $amount = $matchingRow['net_amount'];

        $wallet = SellerWallet::where('seller_id', $sellerId)->first();
        if (!$wallet) {
            Notification::make()->title('Hata')->body('Satici cuzdan bilgisi bulunamadi')->danger()->send();
            return;
        }

        $bankAccount = SellerBankAccount::where('seller_id', $sellerId)->where('is_default', true)->first();
        if (!$bankAccount) {
            Notification::make()->title('Hata')->body('Saticinin banka hesap bilgisi bulunamadi')->danger()->send();
            return;
        }

        // 1. Create PayoutRequest
        PayoutRequest::create([
            'seller_id' => $sellerId,
            'bank_account_id' => $bankAccount->id,
            'amount' => $amount,
            'status' => PayoutRequest::STATUS_APPROVED,
            'notes' => 'Admin tarafindan olusturuldu (manuel havale)',
            'admin_notes' => 'Tekli odeme - SubOrder #' . $subOrderId . ' - ' . now()->format('d.m.Y'),
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        // 2. Create Invoice record (ERP pending — Faturalar sayfasindan tetiklenir)
        $soSales = 0;
        $soShipping = 0;
        $soTax = 0;
        $invoiceItems = [];
        foreach ($subOrder->items as $item) {
            $soSales += (float) $item->total_price;
            $soShipping += (float) ($item->shipping_cost_share ?? 0);
            $vatRate = $item->product?->category?->vat_rate ?? 20;
            $tax = (float) $item->total_price * ($vatRate / 100);
            $soTax += $tax;
            $invoiceItems[] = [
                'product_name' => $item->product?->name ?? 'Urun',
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'vat_rate' => $vatRate,
                'tax' => round($tax, 2),
            ];
        }

        // Hizmet bedeli satiri
        $serviceFee = $matchingRow['service_fee'] ?? 0;
        if ($serviceFee > 0) {
            $serviceFeeVat = 20;
            $serviceFeeTax = $serviceFee * ($serviceFeeVat / 100);
            $soTax += $serviceFeeTax;
            $invoiceItems[] = [
                'product_name' => 'Platform Hizmet Bedeli',
                'quantity' => 1,
                'unit_price' => $serviceFee,
                'total_price' => $serviceFee,
                'vat_rate' => $serviceFeeVat,
                'tax' => round($serviceFeeTax, 2),
                'is_deduction' => true,
            ];
        }

        // Kargo payi satiri
        if ($soShipping > 0) {
            $shippingVat = 20;
            $shippingTax = $soShipping * ($shippingVat / 100);
            $soTax += $shippingTax;
            $invoiceItems[] = [
                'product_name' => 'Kargo Hizmet Bedeli',
                'quantity' => 1,
                'unit_price' => $soShipping,
                'total_price' => $soShipping,
                'vat_rate' => $shippingVat,
                'tax' => round($shippingTax, 2),
                'is_deduction' => true,
            ];
        }

        $invoiceSubtotal = $soSales + $serviceFee + $soShipping;

        Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber('seller'),
            'order_id' => $subOrder->order_id,
            'sub_order_id' => $subOrderId,
            'seller_id' => $sellerId,
            'buyer_id' => $subOrder->order?->user_id,
            'type' => Invoice::TYPE_SELLER,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => round($invoiceSubtotal, 2),
            'tax_amount' => round($soTax, 2),
            'tax_rate' => 20,
            'total_amount' => round($invoiceSubtotal + $soTax, 2),
            'seller_info' => [
                'name' => $subOrder->seller?->business_name ?? $subOrder->seller?->name,
                'email' => $subOrder->seller?->email,
            ],
            'buyer_info' => [
                'name' => $subOrder->order?->buyer_name,
                'phone' => $subOrder->order?->buyer_phone,
            ],
            'items' => $invoiceItems,
            'erp_provider' => 'bizimhesap',
            'erp_status' => 'pending',
        ]);

        $this->backToList();

        Notification::make()
            ->title('Odeme Onaylandi')
            ->body(number_format($amount, 2, ',', '.') . ' TL odeme yapildi olarak isaretlendi. Fatura, Faturalar sayfasina dusuruldu.')
            ->success()
            ->send();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = SubOrder::where('status', 'delivered')
            ->whereNotNull('buyer_confirmed_at')
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
            ->count();

        return $count > 0 ? (string) $count : null;
    }
}
