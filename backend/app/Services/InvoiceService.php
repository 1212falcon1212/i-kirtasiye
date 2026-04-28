<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Category;

class InvoiceService
{
    /**
     * Sipariş için satıcı faturası oluştur
     */
    public function createSellerInvoice(Order $order, int $sellerId): Invoice
    {
        $seller = User::findOrFail($sellerId);
        $buyer = $order->user;

        // Satıcıya ait sipariş kalemlerini al
        $items = $order->items()->where('seller_id', $sellerId)->with('product.category')->get();

        if ($items->isEmpty()) {
            throw new \Exception('Bu siparişte satıcıya ait ürün bulunamadı.');
        }

        $subtotal = $items->sum('total_price');
        $taxRate = $this->calculateAverageTaxRate($items);
        $taxAmount = $subtotal * ($taxRate / 100);
        $totalAmount = $subtotal + $taxAmount;

        // Fatura kalemlerini hazırla
        $invoiceItems = $items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Ürün',
                'barcode' => $item->product->barcode ?? '',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'tax_rate' => $item->product->category->tax_rate ?? 8,
            ];
        })->toArray();

        return Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(Invoice::TYPE_SELLER),
            'order_id' => $order->id,
            'seller_id' => $sellerId,
            'buyer_id' => $buyer->id,
            'type' => Invoice::TYPE_SELLER,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'seller_info' => [
                'name' => $seller->business_name,
                'gln' => $seller->vergi_no,
                'address' => $seller->address,
                'city' => $seller->city,
                'phone' => $seller->phone,
                'email' => $seller->email,
            ],
            'buyer_info' => [
                'name' => $buyer->business_name,
                'gln' => $buyer->vergi_no,
                'address' => $buyer->address,
                'city' => $buyer->city,
                'phone' => $buyer->phone,
                'email' => $buyer->email,
            ],
            'items' => $invoiceItems,
        ]);
    }

    /**
     * Sipariş için komisyon faturası oluştur (Admin → Satıcı)
     */
    public function createCommissionInvoice(Order $order, int $sellerId): Invoice
    {
        $seller = User::findOrFail($sellerId);

        // Satıcıya ait sipariş kalemlerini al
        $items = $order->items()->where('seller_id', $sellerId)->with('product.category')->get();

        if ($items->isEmpty()) {
            throw new \Exception('Bu siparişte satıcıya ait ürün bulunamadı.');
        }

        // Komisyon hesapla
        $totalSales = $items->sum('total_price');
        $totalCommission = $items->sum('commission_amount');
        $averageCommissionRate = $totalSales > 0 ? ($totalCommission / $totalSales) * 100 : 0;

        // Komisyon üzerinden KDV hesapla (%18)
        $taxRate = 18;
        $taxAmount = $totalCommission * ($taxRate / 100);
        $totalAmount = $totalCommission + $taxAmount;

        // Komisyon fatura kalemlerini hazırla
        $invoiceItems = $items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Ürün',
                'sale_amount' => $item->total_price,
                'commission_rate' => $item->commission_rate,
                'commission_amount' => $item->commission_amount,
            ];
        })->toArray();

        return Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(Invoice::TYPE_COMMISSION),
            'order_id' => $order->id,
            'seller_id' => $sellerId,
            'buyer_id' => null, // Admin'e kesilen fatura
            'type' => Invoice::TYPE_COMMISSION,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => $totalCommission,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'commission_rate' => $averageCommissionRate,
            'commission_amount' => $totalCommission,
            'seller_info' => [
                'name' => $seller->business_name,
                'gln' => $seller->vergi_no,
                'address' => $seller->address,
                'city' => $seller->city,
                'phone' => $seller->phone,
                'email' => $seller->email,
            ],
            'buyer_info' => [
                'name' => 'i-kirtasiye A.Ş.',
                'address' => 'Platform Merkezi',
                'city' => 'İstanbul',
                'tax_office' => 'Beşiktaş V.D.',
                'tax_number' => '1234567890',
            ],
            'items' => $invoiceItems,
        ]);
    }

    /**
     * Toplu komisyon faturası oluştur (Aylık)
     */
    public function createMonthlyCommissionInvoice(int $sellerId, string $month): Invoice
    {
        $seller = User::findOrFail($sellerId);

        // Ay başı ve sonu
        $startDate = \Carbon\Carbon::parse($month)->startOfMonth();
        $endDate = \Carbon\Carbon::parse($month)->endOfMonth();

        // Satıcının o aydaki tüm satışlarını al
        $items = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('product.category', 'order')
            ->get();

        if ($items->isEmpty()) {
            throw new \Exception('Bu dönemde satış bulunamadı.');
        }

        $totalSales = $items->sum('total_price');
        $totalCommission = $items->sum('commission_amount');

        $taxRate = 18;
        $taxAmount = $totalCommission * ($taxRate / 100);
        $totalAmount = $totalCommission + $taxAmount;

        // Sipariş bazlı özet
        $orderSummary = $items->groupBy('order_id')->map(function ($orderItems) {
            return [
                'order_number' => $orderItems->first()->order->order_number,
                'sale_amount' => $orderItems->sum('total_price'),
                'commission_amount' => $orderItems->sum('commission_amount'),
            ];
        })->values()->toArray();

        return Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(Invoice::TYPE_COMMISSION),
            'order_id' => null,
            'seller_id' => $sellerId,
            'buyer_id' => null,
            'type' => Invoice::TYPE_COMMISSION,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => $totalCommission,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'commission_rate' => $totalSales > 0 ? ($totalCommission / $totalSales) * 100 : 0,
            'commission_amount' => $totalCommission,
            'seller_info' => [
                'name' => $seller->business_name,
                'gln' => $seller->vergi_no,
                'address' => $seller->address,
                'city' => $seller->city,
            ],
            'buyer_info' => [
                'name' => 'i-kirtasiye A.Ş.',
                'tax_office' => 'Beşiktaş V.D.',
                'tax_number' => '1234567890',
            ],
            'items' => $orderSummary,
            'notes' => "Dönem: {$startDate->format('d.m.Y')} - {$endDate->format('d.m.Y')}",
        ]);
    }

    /**
     * Kategori bazlı ortalama vergi oranı hesapla
     */
    protected function calculateAverageTaxRate($items): float
    {
        $totalTax = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $taxRate = $item->product->category->tax_rate ?? 8;
            $totalTax += $item->total_price * ($taxRate / 100);
            $totalAmount += $item->total_price;
        }

        if ($totalAmount == 0) {
            return 8; // Varsayılan
        }

        return round(($totalTax / $totalAmount) * 100, 2);
    }

    /**
     * Satıcının komisyon özetini getir
     */
    public function getSellerCommissionSummary(int $sellerId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid');
            });

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $items = $query->get();

        return [
            'total_sales' => $items->sum('total_price'),
            'total_commission' => $items->sum('commission_amount'),
            'total_payout' => $items->sum('seller_payout_amount'),
            'order_count' => $items->pluck('order_id')->unique()->count(),
            'item_count' => $items->count(),
        ];
    }

    /**
     * Satıcının faturalarını listele
     */
    public function getSellerInvoices(int $sellerId, ?string $type = null, int $perPage = 15)
    {
        $query = Invoice::where('seller_id', $sellerId);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
