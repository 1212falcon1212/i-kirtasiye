<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\SellerWallet;
use App\Models\Setting;
use App\Models\SubOrder;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SettlementService
{
    /**
     * Get upcoming (not yet released) settlements grouped by expected release date
     * Now uses sub_orders for per-seller settlement tracking
     */
    public function getUpcomingSettlements(User $seller): array
    {
        $holdDays = (int) Setting::getValue('payment.hold_days', 35);

        $wallet = SellerWallet::where('seller_id', $seller->id)->first();
        if (! $wallet) {
            return [];
        }

        // Get sub_order IDs that have already been released
        $releasedSubOrderIds = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('sub_order_id')
            ->pluck('sub_order_id')
            ->toArray();

        // Also get order IDs for backward compat (pre-suborder releases)
        $releasedOrderIds = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('order_id')
            ->whereNull('sub_order_id')
            ->pluck('order_id')
            ->toArray();

        // Find confirmed sub_orders with pending earnings (exclude returned)
        $pendingSubOrders = SubOrder::where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->where('status', '!=', 'returned')
            ->whereNotNull('buyer_confirmed_at')
            ->whereNotIn('id', $releasedSubOrderIds)
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid')->whereNotIn('id', $releasedOrderIds))
            ->with(['items.product', 'order'])
            ->orderBy('buyer_confirmed_at')
            ->get();

        // Group by expected release date
        $groups = [];
        foreach ($pendingSubOrders as $subOrder) {
            $releaseDate = Carbon::parse($subOrder->buyer_confirmed_at)->addDays($holdDays)->format('Y-m-d');
            if (! isset($groups[$releaseDate])) {
                $groups[$releaseDate] = [
                    'date' => $releaseDate,
                    'sub_orders' => [],
                ];
            }
            $groups[$releaseDate]['sub_orders'][] = $subOrder;
        }

        // Build settlement groups
        $result = [];
        foreach ($groups as $date => $group) {
            $releaseDateCarbon = Carbon::parse($date);
            $totals = $this->calculateSubOrderGroupTotals($group['sub_orders']);

            $result[] = [
                'date' => $date,
                'date_formatted' => $releaseDateCarbon->locale('tr')->translatedFormat('d F Y'),
                'days_remaining' => max(0, (int) now()->startOfDay()->diffInDays($releaseDateCarbon->startOfDay(), false)),
                'total_sales' => round($totals['total_sales'], 2),
                'total_service_fee' => round($totals['total_service_fee'], 2),
                'total_withholding_tax' => round($totals['total_withholding_tax'], 2),
                'total_shipping_share' => round($totals['total_shipping_share'], 2),
                'total_refunds' => round($totals['total_refunds'], 2),
                'net_amount' => round($totals['net_amount'], 2),
                'order_count' => count($group['sub_orders']),
                'item_count' => $totals['item_count'],
            ];
        }

        usort($result, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return $result;
    }

    /**
     * Get past (released) settlements grouped by release date
     */
    public function getPastSettlements(User $seller): array
    {
        $wallet = SellerWallet::where('seller_id', $seller->id)->first();
        if (! $wallet) {
            return [];
        }

        // Get release transactions grouped by date
        $releases = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('order_id')
            ->orderByDesc('created_at')
            ->get();

        if ($releases->isEmpty()) {
            return [];
        }

        // Group by release date
        $groups = [];
        foreach ($releases as $release) {
            $dateKey = Carbon::parse($release->created_at)->format('Y-m-d');
            if (! isset($groups[$dateKey])) {
                $groups[$dateKey] = [
                    'date' => $dateKey,
                    'order_ids' => [],
                    'total_released' => 0,
                ];
            }
            $groups[$dateKey]['order_ids'][] = $release->order_id;
            $groups[$dateKey]['total_released'] += (float) $release->amount;
        }

        $result = [];
        foreach ($groups as $dateKey => $group) {
            $releaseDateCarbon = Carbon::parse($dateKey)->locale('tr');

            // Get order items for these orders
            $orders = Order::whereIn('id', array_unique($group['order_ids']))
                ->whereHas('items', fn ($q) => $q->where('seller_id', $seller->id))
                ->with(['items' => fn ($q) => $q->where('seller_id', $seller->id)])
                ->get();

            $totals = $this->calculateGroupTotals($orders, $seller->id);

            $result[] = [
                'date' => $dateKey,
                'date_formatted' => $releaseDateCarbon->translatedFormat('d F Y'),
                'days_remaining' => 0,
                'total_sales' => round($totals['total_sales'], 2),
                'total_service_fee' => round($totals['total_service_fee'], 2),
                'total_withholding_tax' => round($totals['total_withholding_tax'], 2),
                'total_shipping_share' => round($totals['total_shipping_share'], 2),
                'total_refunds' => round($totals['total_refunds'], 2),
                'net_amount' => round($group['total_released'], 2),
                'order_count' => count($orders),
                'item_count' => $totals['item_count'],
            ];
        }

        // Sort by date descending (newest first)
        usort($result, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $result;
    }

    /**
     * Get settlement details for a specific date
     */
    public function getSettlementDetails(User $seller, string $date, string $type = 'upcoming'): array
    {
        $holdDays = (int) Setting::getValue('payment.hold_days', 35);
        $wallet = SellerWallet::where('seller_id', $seller->id)->first();

        if (! $wallet) {
            return ['summary' => [], 'details' => []];
        }

        if ($type === 'upcoming') {
            $orders = $this->getUpcomingOrdersForDate($seller, $date, $holdDays, $wallet);
        } else {
            $orders = $this->getPastOrdersForDate($seller, $date, $wallet);
        }

        if ($orders->isEmpty()) {
            return ['summary' => [], 'details' => []];
        }

        return $this->buildDetailsResponse($orders, $seller->id);
    }

    /**
     * Get summary statistics for all upcoming settlements (sub_order based)
     */
    public function getUpcomingSummary(User $seller): array
    {
        $wallet = SellerWallet::where('seller_id', $seller->id)->first();
        if (! $wallet) {
            return [
                'total_sales' => 0,
                'total_service_fee' => 0,
                'total_withholding_tax' => 0,
                'total_shipping_share' => 0,
                'net_estimated_total' => 0,
            ];
        }

        $releasedSubOrderIds = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('sub_order_id')
            ->pluck('sub_order_id')
            ->toArray();

        $releasedOrderIds = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('order_id')
            ->whereNull('sub_order_id')
            ->pluck('order_id')
            ->toArray();

        $pendingSubOrders = SubOrder::where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->where('status', '!=', 'returned')
            ->whereNotNull('buyer_confirmed_at')
            ->whereNotIn('id', $releasedSubOrderIds)
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid')->whereNotIn('id', $releasedOrderIds))
            ->with('items')
            ->get();

        $totals = $this->calculateSubOrderGroupTotals($pendingSubOrders);

        return [
            'total_sales' => round($totals['total_sales'], 2),
            'total_service_fee' => round($totals['total_service_fee'], 2),
            'total_withholding_tax' => round($totals['total_withholding_tax'], 2),
            'total_shipping_share' => round($totals['total_shipping_share'], 2),
            'total_refunds' => round($totals['total_refunds'], 2),
            'net_estimated_total' => round($totals['net_amount'], 2),
        ];
    }

    /**
     * Calculate totals for a group of orders (seller-specific items)
     * flat: sabit hizmet bedeli (sipariş başına)
     * percentage: satış tutarının %'si
     * category: her item'ın commission_amount toplamı (sipariş oluşturulurken yazılmış)
     */
    private function calculateGroupTotals(Collection|array $orders, int $sellerId): array
    {
        $feeMode = (string) Setting::getValue('commission.fee_mode', 'flat');
        $flatServiceFee = (float) Setting::getValue('commission.flat_service_fee', 50);
        $commissionPercentage = (float) Setting::getValue('commission.commission_percentage', 10);
        $withholdingRate = (float) Setting::getValue('commission.withholding_tax_rate', 1.00);
        $serviceFeeEnabled = (bool) Setting::getValue('commission.enabled', true);

        $totalSales = 0;
        $totalServiceFee = 0;
        $totalWithholding = 0;
        $totalShipping = 0;
        $itemCount = 0;

        foreach ($orders as $order) {
            $orderSales = 0;
            $orderShipping = 0;
            $orderItems = 0;
            $orderCommissionFromItems = 0;

            $orderWithholdingSum = 0;

            foreach ($order->items as $item) {
                if ((int) $item->seller_id !== $sellerId) {
                    continue;
                }
                $itemPrice = (float) $item->total_price;
                $orderSales += $itemPrice;
                $orderShipping += (float) ($item->shipping_cost_share ?? 0);
                $orderItems += (int) $item->quantity;
                $orderCommissionFromItems += (float) ($item->commission_amount ?? 0);

                // Stopaj: KDV hariç tutar üzerinden
                $vatRate = (float) ($item->product?->category?->vat_rate ?? 20);
                $orderWithholdingSum += ($itemPrice / (1 + $vatRate / 100)) * ($withholdingRate / 100);
            }

            if ($orderSales <= 0) {
                continue;
            }

            $totalSales += $orderSales;

            if ($serviceFeeEnabled) {
                switch ($feeMode) {
                    case 'percentage':
                        $totalServiceFee += $orderSales * ($commissionPercentage / 100);
                        break;
                    case 'category':
                        $totalServiceFee += $orderCommissionFromItems;
                        break;
                    default: // flat
                        $totalServiceFee += $flatServiceFee;
                        break;
                }
            }

            $totalWithholding += $orderWithholdingSum;
            $totalShipping += $orderShipping;
            $itemCount += $orderItems;
        }

        // İade edilen (onaylı/iade edilmiş) taleplerin toplam tutarını düş
        $orderIds = collect($orders)->pluck('id')->unique()->filter()->toArray();
        $totalRefunds = 0;
        if (! empty($orderIds)) {
            $totalRefunds = (float) ReturnRequest::whereIn('order_id', $orderIds)
                ->where('seller_id', $sellerId)
                ->whereIn('status', ['approved', 'refunded'])
                ->sum('refund_amount');
        }

        $netAmount = $totalSales - $totalServiceFee - $totalWithholding - $totalShipping - $totalRefunds;

        return [
            'total_sales' => $totalSales,
            'total_service_fee' => $totalServiceFee,
            'total_withholding_tax' => $totalWithholding,
            'total_shipping_share' => $totalShipping,
            'total_refunds' => $totalRefunds,
            'net_amount' => $netAmount,
            'item_count' => $itemCount,
        ];
    }

    /**
     * Calculate totals for a group of sub_orders (all items belong to the seller)
     */
    private function calculateSubOrderGroupTotals(Collection|array $subOrders): array
    {
        $feeMode = (string) Setting::getValue('commission.fee_mode', 'flat');
        $flatServiceFee = (float) Setting::getValue('commission.flat_service_fee', 50);
        $commissionPercentage = (float) Setting::getValue('commission.commission_percentage', 10);
        $withholdingRate = (float) Setting::getValue('commission.withholding_tax_rate', 1.00);
        $serviceFeeEnabled = (bool) Setting::getValue('commission.enabled', true);

        $totalSales = 0;
        $totalServiceFee = 0;
        $totalWithholding = 0;
        $totalShipping = 0;
        $itemCount = 0;

        foreach ($subOrders as $subOrder) {
            $soSales = 0;
            $soShipping = 0;
            $soItems = 0;
            $soCommissionFromItems = 0;
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

            $totalSales += $soSales;

            if ($serviceFeeEnabled) {
                switch ($feeMode) {
                    case 'percentage':
                        $totalServiceFee += $soSales * ($commissionPercentage / 100);
                        break;
                    case 'category':
                        $totalServiceFee += $soCommissionFromItems;
                        break;
                    default:
                        $totalServiceFee += $flatServiceFee;
                        break;
                }
            }

            $totalWithholding += $soWithholdingSum;
            $totalShipping += $soShipping;
            $itemCount += $soItems;
        }

        // İade edilen (onaylı/iade edilmiş) taleplerin toplam tutarını düş
        $allItemIds = collect($subOrders)->flatMap(fn ($so) => $so->items->pluck('id'))->unique()->filter()->toArray();
        $totalRefunds = 0;
        if (! empty($allItemIds)) {
            $totalRefunds = (float) ReturnRequest::whereIn('order_item_id', $allItemIds)
                ->whereIn('status', ['approved', 'refunded'])
                ->sum('refund_amount');
        }

        $netAmount = $totalSales - $totalServiceFee - $totalWithholding - $totalShipping - $totalRefunds;

        return [
            'total_sales' => $totalSales,
            'total_service_fee' => $totalServiceFee,
            'total_withholding_tax' => $totalWithholding,
            'total_shipping_share' => $totalShipping,
            'total_refunds' => $totalRefunds,
            'net_amount' => $netAmount,
            'item_count' => $itemCount,
        ];
    }

    /**
     * Get upcoming sub_orders for a specific expected release date
     * Returns as a Collection of "virtual order" objects for backward-compat with buildDetailsResponse
     */
    private function getUpcomingOrdersForDate(User $seller, string $date, int $holdDays, SellerWallet $wallet): Collection
    {
        $releasedSubOrderIds = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('sub_order_id')
            ->pluck('sub_order_id')
            ->toArray();

        $releasedOrderIds = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('order_id')
            ->whereNull('sub_order_id')
            ->pluck('order_id')
            ->toArray();

        // Calculate the confirmation date range that would result in this release date
        $releaseDate = Carbon::parse($date);
        $confirmStart = $releaseDate->copy()->subDays($holdDays)->startOfDay();
        $confirmEnd = $releaseDate->copy()->subDays($holdDays)->endOfDay();

        // Query sub_orders by THEIR buyer_confirmed_at (not parent order's)
        $subOrders = SubOrder::where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->where('status', '!=', 'returned')
            ->whereNotNull('buyer_confirmed_at')
            ->whereNotIn('id', $releasedSubOrderIds)
            ->whereBetween('buyer_confirmed_at', [$confirmStart, $confirmEnd])
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid')->whereNotIn('id', $releasedOrderIds))
            ->with(['items.product', 'order'])
            ->get();

        // Map sub_orders to order-like objects for buildDetailsResponse compatibility
        return $subOrders->map(function ($subOrder) {
            $order = $subOrder->order;
            $order->setRelation('items', $subOrder->items);

            return $order;
        });
    }

    /**
     * Get past orders for a specific release date
     */
    private function getPastOrdersForDate(User $seller, string $date, SellerWallet $wallet): Collection
    {
        $dateStart = Carbon::parse($date)->startOfDay();
        $dateEnd = Carbon::parse($date)->endOfDay();

        $orderIds = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->whereNotNull('order_id')
            ->whereBetween('created_at', [$dateStart, $dateEnd])
            ->pluck('order_id')
            ->unique()
            ->toArray();

        return Order::whereIn('id', $orderIds)
            ->whereHas('items', fn ($q) => $q->where('seller_id', $seller->id))
            ->with(['items' => fn ($q) => $q->where('seller_id', $seller->id)->with('product')])
            ->get();
    }

    /**
     * Build the details response with summary rows and per-order detail rows
     */
    private function buildDetailsResponse(Collection $orders, int $sellerId): array
    {
        $feeMode = (string) Setting::getValue('commission.fee_mode', 'flat');
        $flatServiceFee = (float) Setting::getValue('commission.flat_service_fee', 50);
        $commissionPercentage = (float) Setting::getValue('commission.commission_percentage', 10);
        $withholdingRate = (float) Setting::getValue('commission.withholding_tax_rate', 1.00);
        $serviceFeeEnabled = (bool) Setting::getValue('commission.enabled', true);

        $totals = $this->calculateGroupTotals($orders, $sellerId);

        // Mode-specific label
        switch ($feeMode) {
            case 'percentage':
                $feeLabel = "Komisyon (%{$commissionPercentage})";
                $feeDescription = "Satış tutarının %{$commissionPercentage}'i";
                break;
            case 'category':
                $feeLabel = 'Kategori Komisyonu';
                $feeDescription = 'Kategori bazlı komisyon kesintisi';
                break;
            default:
                $feeLabel = 'Hizmet Bedeli';
                $feeDescription = 'Platform hizmet bedeli';
                break;
        }

        // Summary rows
        $summary = [
            [
                'label' => 'Satış Tutarı',
                'description' => 'Ürün satış gelirleri',
                'amount' => round($totals['total_sales'], 2),
                'type' => 'credit',
            ],
        ];

        if ($totals['total_shipping_share'] > 0) {
            $summary[] = [
                'label' => 'Kargo Kesintisi',
                'description' => 'Kargo maliyet payı',
                'amount' => round(-$totals['total_shipping_share'], 2),
                'type' => 'debit',
            ];
        }

        if ($totals['total_service_fee'] > 0) {
            $summary[] = [
                'label' => $feeLabel,
                'description' => $feeDescription,
                'amount' => round(-$totals['total_service_fee'], 2),
                'type' => 'debit',
            ];
        }

        if ($totals['total_withholding_tax'] > 0) {
            $summary[] = [
                'label' => 'Stopaj',
                'description' => "E-ticaret stopajı (%{$withholdingRate}, KDV hariç tutar üzerinden)",
                'amount' => round(-$totals['total_withholding_tax'], 2),
                'type' => 'debit',
            ];
        }

        if ($totals['total_refunds'] > 0) {
            $summary[] = [
                'label' => 'İade Kesintisi',
                'description' => 'Onaylanan iade talepleri tutarı',
                'amount' => round(-$totals['total_refunds'], 2),
                'type' => 'debit',
            ];
        }

        $summary[] = [
            'label' => 'Net Hakediş',
            'description' => '',
            'amount' => round($totals['net_amount'], 2),
            'type' => 'total',
        ];

        // Detail rows (per order, not per item)
        $details = [];
        foreach ($orders as $order) {
            $orderTotal = 0;
            $orderShipping = 0;
            $orderItemCount = 0;
            $orderCommissionFromItems = 0;
            $orderWithholding = 0;

            foreach ($order->items as $item) {
                if ((int) $item->seller_id !== $sellerId) {
                    continue;
                }
                $itemPrice = (float) $item->total_price;
                $orderTotal += $itemPrice;
                $orderShipping += (float) ($item->shipping_cost_share ?? 0);
                $orderItemCount += (int) $item->quantity;
                $orderCommissionFromItems += (float) ($item->commission_amount ?? 0);

                // Stopaj: KDV hariç tutar üzerinden
                $vatRate = (float) ($item->product?->category?->vat_rate ?? 20);
                $orderWithholding += ($itemPrice / (1 + $vatRate / 100)) * ($withholdingRate / 100);
            }

            if ($orderTotal <= 0) {
                continue;
            }

            // Calculate service fee based on mode
            $orderServiceFee = 0;
            if ($serviceFeeEnabled) {
                switch ($feeMode) {
                    case 'percentage':
                        $orderServiceFee = $orderTotal * ($commissionPercentage / 100);
                        break;
                    case 'category':
                        $orderServiceFee = $orderCommissionFromItems;
                        break;
                    default:
                        $orderServiceFee = $flatServiceFee;
                        break;
                }
            }

            $orderNet = $orderTotal - $orderServiceFee - $orderWithholding - $orderShipping;

            // Collect product items for this order
            $orderItems = [];
            foreach ($order->items as $item) {
                if ((int) $item->seller_id !== $sellerId) {
                    continue;
                }
                $orderItems[] = [
                    'product_name' => $item->product?->name ?? 'Ürün',
                    'quantity' => (int) $item->quantity,
                    'unit_price' => round((float) $item->unit_price, 2),
                    'total_price' => round((float) $item->total_price, 2),
                ];
            }

            $details[] = [
                'order_number' => $order->order_number,
                'order_date' => Carbon::parse($order->created_at)->format('d.m.Y'),
                'item_count' => $orderItemCount,
                'total_price' => round($orderTotal, 2),
                'service_fee' => round($orderServiceFee, 2),
                'withholding_tax' => round($orderWithholding, 2),
                'shipping_share' => round($orderShipping, 2),
                'net_amount' => round($orderNet, 2),
                'items' => $orderItems,
            ];
        }

        return [
            'summary' => $summary,
            'details' => $details,
        ];
    }
}
