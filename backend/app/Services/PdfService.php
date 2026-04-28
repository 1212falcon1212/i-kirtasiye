<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\PDF;

/**
 * PDF oluşturma servisi - Fatura ve hakediş raporu PDF'leri
 */
class PdfService
{
    /**
     * Sipariş faturası PDF'i oluşturur
     *
     * @param  Order  $order  Sipariş modeli
     * @param  int|null  $sellerId  Belirli satıcıya ait ürünleri filtrele
     */
    public function generateOrderInvoice(Order $order, ?int $sellerId = null): PDF
    {
        $order->load(['items.product.category', 'subOrders', 'user']);

        // Satıcı filtresi uygulanmışsa sadece o satıcının ürünlerini al
        $items = $order->items;
        if ($sellerId !== null) {
            $items = $items->where('seller_id', $sellerId);
        }

        // Satıcı bilgilerini al
        $seller = null;
        if ($sellerId !== null) {
            $seller = User::find($sellerId);
        } elseif ($items->isNotEmpty()) {
            $firstSellerId = $items->first()->seller_id;
            $seller = User::find($firstSellerId);
        }

        // Alıcı bilgileri
        $buyer = $order->user;

        // Mevcut Invoice kaydını kontrol et (invoice_number icin)
        $invoice = null;
        if ($sellerId !== null) {
            $invoice = $order->invoices()
                ->where('seller_id', $sellerId)
                ->where('type', 'seller')
                ->first();
        } else {
            $invoice = $order->invoices()->where('type', 'seller')->first();
        }

        // Vergi gruplarını hesapla
        $taxGroups = [];
        $subtotal = 0;
        foreach ($items as $item) {
            $taxRate = (float) ($item->product?->category?->tax_rate ?? 8);
            $itemTotal = (float) $item->total_price;
            $subtotal += $itemTotal;

            if (! isset($taxGroups[$taxRate])) {
                $taxGroups[$taxRate] = ['rate' => $taxRate, 'base' => 0, 'amount' => 0];
            }
            $baseAmount = $itemTotal / (1 + $taxRate / 100);
            $taxGroups[$taxRate]['base'] += $baseAmount;
            $taxGroups[$taxRate]['amount'] += $itemTotal - $baseAmount;
        }

        $totalTax = array_sum(array_column($taxGroups, 'amount'));
        $totalCommission = $items->sum('commission_amount');
        $totalShipping = (float) $order->shipping_cost;

        $data = [
            'order' => $order,
            'items' => $items,
            'seller' => $seller,
            'buyer' => $buyer,
            'invoice' => $invoice,
            'subtotal' => $subtotal,
            'totalTax' => $totalTax,
            'taxGroups' => $taxGroups,
            'totalCommission' => $totalCommission,
            'totalShipping' => $totalShipping,
            'grandTotal' => $subtotal,
        ];

        /** @var PDF $pdf */
        $pdf = app('dompdf.wrapper')
            ->loadView('pdf.invoice', $data)
            ->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Hakediş raporu PDF'i oluşturur
     *
     * @param  int  $sellerId  Satıcı ID
     * @param  string  $date  Hakediş tarihi (YYYY-MM-DD)
     * @param  string  $type  Hakediş tipi (upcoming veya past)
     */
    public function generateSettlementReport(int $sellerId, string $date, string $type = 'upcoming'): PDF
    {
        $seller = User::findOrFail($sellerId);
        $settlementService = app(SettlementService::class);

        $details = $settlementService->getSettlementDetails($seller, $date, $type);

        $data = [
            'seller' => $seller,
            'date' => $date,
            'type' => $type,
            'summary' => $details['summary'] ?? [],
            'details' => $details['details'] ?? [],
            'generatedAt' => now()->format('d.m.Y H:i'),
        ];

        /** @var PDF $pdf */
        $pdf = app('dompdf.wrapper')
            ->loadView('pdf.settlement-report', $data)
            ->setPaper('a4', 'portrait');

        return $pdf;
    }
}
