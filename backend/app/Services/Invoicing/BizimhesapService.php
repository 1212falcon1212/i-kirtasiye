<?php

namespace App\Services\Invoicing;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\SubOrder;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bizimhesap e-fatura servisi
 * API: https://bizimhesap.com/api/b2b/addinvoice
 */
class BizimhesapService
{
    protected string $apiToken;
    protected string $apiUrl = 'https://bizimhesap.com/api/b2b/addinvoice';
    protected bool $enabled;

    public function __construct()
    {
        $this->apiToken = Setting::getValue('erp.bizimhesap_api_token', '');
        $this->enabled = !empty($this->apiToken);
    }

    /**
     * Create a sales invoice on BizimHesap from a stored Invoice record.
     * Uses Invoice->items (which includes product lines + hizmet bedeli + kargo).
     */
    public function createInvoiceFromRecord(Invoice $invoice): InvoiceResult
    {
        if (!$this->enabled) {
            return InvoiceResult::pending('Bizimhesap entegrasyonu aktif degil.');
        }

        $order = $invoice->order;
        $buyer = $order?->user;

        if (!$buyer) {
            return InvoiceResult::failure('Siparis alici bilgisi bulunamadi.');
        }

        $storedItems = $invoice->items ?? [];
        if (empty($storedItems)) {
            return InvoiceResult::failure('Fatura kalemleri bulunamadi.');
        }

        $details = [];
        $totalNet = 0;
        $totalTax = 0;

        foreach ($storedItems as $item) {
            $net = (float) ($item['total_price'] ?? 0);
            $vatRate = (float) ($item['vat_rate'] ?? 20);
            $tax = $net * ($vatRate / 100);

            $details[] = [
                'productId' => $item['product_name'] ?? 'ITEM',
                'productName' => $item['product_name'] ?? 'Urun',
                'note' => '',
                'barcode' => '',
                'taxRate' => number_format($vatRate, 2, '.', ''),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unitPrice' => number_format((float) ($item['unit_price'] ?? $net), 2, '.', ''),
                'grossPrice' => number_format($net, 2, '.', ''),
                'discount' => '0.00',
                'net' => number_format($net, 2, '.', ''),
                'tax' => number_format($tax, 2, '.', ''),
                'total' => number_format($net + $tax, 2, '.', ''),
            ];

            $totalNet += $net;
            $totalTax += $tax;
        }

        $invoiceData = [
            'firmId' => $this->apiToken,
            'invoiceNo' => $invoice->invoice_number,
            'invoiceType' => 3,
            'note' => "Siparis #{$order->order_number}" . ($invoice->sub_order_id ? " - SubOrder #{$invoice->sub_order_id}" : ''),
            'dates' => [
                'invoiceDate' => ($invoice->created_at ?? now())->toIso8601String(),
                'dueDate' => ($invoice->created_at ?? now())->addDays(30)->toIso8601String(),
            ],
            'customer' => [
                'customerId' => $buyer->id,
                'title' => $buyer->business_name ?? $buyer->name,
                'taxOffice' => '',
                'taxNo' => $buyer->vergi_no ?? '',
                'email' => $buyer->email,
                'phone' => $buyer->phone ?? '',
                'address' => $order->shipping_address['address'] ?? $buyer->address ?? '',
            ],
            'amounts' => [
                'currency' => 'TL',
                'gross' => number_format($totalNet, 2, '.', ''),
                'discount' => '0.00',
                'net' => number_format($totalNet, 2, '.', ''),
                'tax' => number_format($totalTax, 2, '.', ''),
                'total' => number_format($totalNet + $totalTax, 2, '.', ''),
            ],
            'details' => $details,
        ];

        return $this->sendInvoice($invoiceData);
    }

    /**
     * Create a sales invoice (Satis Faturasi) from seller to buyer
     * invoiceType: 3 = Satis
     */
    public function createSalesInvoice(Order $order, User $seller): InvoiceResult
    {
        if (!$this->enabled) {
            return InvoiceResult::pending('Bizimhesap entegrasyonu aktif degil.');
        }

        $buyer = $order->user;
        $sellerItems = $order->items->where('seller_id', $seller->id);

        if ($sellerItems->isEmpty()) {
            return InvoiceResult::failure('Bu saticiya ait siparis kalemi bulunamadi.');
        }

        $details = [];
        $totalNet = 0;
        $totalTax = 0;
        $totalGross = 0;

        foreach ($sellerItems as $item) {
            $product = $item->product;
            $category = $product->category;
            $vatRate = $category?->vat_rate ?? 20;

            $net = (float) $item->total_price;
            $tax = $net * ($vatRate / 100);
            $gross = $net;

            $details[] = [
                'productId' => $product->id,
                'productName' => $product->name,
                'note' => $product->barcode ?? '',
                'barcode' => $product->barcode ?? '',
                'taxRate' => number_format($vatRate, 2, '.', ''),
                'quantity' => $item->quantity,
                'unitPrice' => number_format((float) $item->unit_price, 2, '.', ''),
                'grossPrice' => number_format($gross, 2, '.', ''),
                'discount' => '0.00',
                'net' => number_format($net, 2, '.', ''),
                'tax' => number_format($tax, 2, '.', ''),
                'total' => number_format($net + $tax, 2, '.', ''),
            ];

            $totalNet += $net;
            $totalTax += $tax;
            $totalGross += $gross;
        }

        $invoiceData = [
            'firmId' => $this->apiToken,
            'invoiceNo' => 'SF-' . $order->order_number . '-' . $seller->id,
            'invoiceType' => 3,
            'note' => "Siparis #{$order->order_number}",
            'dates' => [
                'invoiceDate' => now()->toIso8601String(),
                'dueDate' => now()->addDays(30)->toIso8601String(),
            ],
            'customer' => [
                'customerId' => $buyer->id,
                'title' => $buyer->business_name,
                'taxOffice' => '',
                'taxNo' => $buyer->vergi_no ?? '',
                'email' => $buyer->email,
                'phone' => $buyer->phone ?? '',
                'address' => $order->shipping_address['address'] ?? $buyer->address ?? '',
            ],
            'amounts' => [
                'currency' => 'TL',
                'gross' => number_format($totalGross, 2, '.', ''),
                'discount' => '0.00',
                'net' => number_format($totalNet, 2, '.', ''),
                'tax' => number_format($totalTax, 2, '.', ''),
                'total' => number_format($totalNet + $totalTax, 2, '.', ''),
            ],
            'details' => $details,
        ];

        return $this->sendInvoice($invoiceData);
    }

    /**
     * Create a commission invoice (Komisyon Faturasi) from platform to seller
     * invoiceType: 3 = Satis (platform sells service to seller)
     */
    public function createCommissionInvoice(Order $order, User $seller): InvoiceResult
    {
        if (!$this->enabled) {
            return InvoiceResult::pending('Bizimhesap entegrasyonu aktif degil.');
        }

        $sellerItems = $order->items->where('seller_id', $seller->id);
        $totalCommission = $sellerItems->sum('commission_amount');

        if ($totalCommission <= 0) {
            return InvoiceResult::pending('Komisyon tutari sifir.');
        }

        $commissionVatRate = (float) Setting::getValue('platform_commission_vat', 20);
        $commissionTax = $totalCommission * ($commissionVatRate / 100);

        $invoiceData = [
            'firmId' => $this->apiToken,
            'invoiceNo' => 'KF-' . $order->order_number . '-' . $seller->id,
            'invoiceType' => 3,
            'note' => "Platform Komisyon Faturasi - Siparis #{$order->order_number}",
            'dates' => [
                'invoiceDate' => now()->toIso8601String(),
                'dueDate' => now()->toIso8601String(),
            ],
            'customer' => [
                'customerId' => $seller->id,
                'title' => $seller->business_name,
                'taxOffice' => '',
                'taxNo' => $seller->vergi_no ?? '',
                'email' => $seller->email,
                'phone' => $seller->phone ?? '',
                'address' => $seller->address ?? '',
            ],
            'amounts' => [
                'currency' => 'TL',
                'gross' => number_format((float) $totalCommission, 2, '.', ''),
                'discount' => '0.00',
                'net' => number_format((float) $totalCommission, 2, '.', ''),
                'tax' => number_format($commissionTax, 2, '.', ''),
                'total' => number_format((float) $totalCommission + $commissionTax, 2, '.', ''),
            ],
            'details' => [
                [
                    'productId' => 'KOMISYON',
                    'productName' => 'Platform Aracilik Hizmet Bedeli',
                    'note' => "Siparis #{$order->order_number}",
                    'barcode' => '',
                    'taxRate' => number_format($commissionVatRate, 2, '.', ''),
                    'quantity' => 1,
                    'unitPrice' => number_format((float) $totalCommission, 2, '.', ''),
                    'grossPrice' => number_format((float) $totalCommission, 2, '.', ''),
                    'discount' => '0.00',
                    'net' => number_format((float) $totalCommission, 2, '.', ''),
                    'tax' => number_format($commissionTax, 2, '.', ''),
                    'total' => number_format((float) $totalCommission + $commissionTax, 2, '.', ''),
                ],
            ],
        ];

        return $this->sendInvoice($invoiceData);
    }

    /**
     * Send invoice to Bizimhesap API with Key + Token auth headers
     */
    protected function sendInvoice(array $data): InvoiceResult
    {
        try {
            Log::info('Sending invoice to Bizimhesap', ['invoice_no' => $data['invoiceNo']]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Key' => $this->apiToken,
                ])
                ->post($this->apiUrl, $data);

            if ($response->successful()) {
                $result = $response->json();

                if (empty($result['error'])) {
                    Log::info('Invoice created successfully', [
                        'invoice_no' => $data['invoiceNo'],
                        'guid' => $result['guid'] ?? null,
                        'url' => $result['url'] ?? null,
                    ]);

                    return InvoiceResult::created(
                        invoiceId: $result['guid'] ?? $data['invoiceNo'],
                        invoiceUrl: $result['url'] ?? null,
                    );
                } else {
                    Log::error('Bizimhesap API error', ['error' => $result['error']]);
                    return InvoiceResult::failure($result['error']);
                }
            } else {
                Log::error('Bizimhesap API HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return InvoiceResult::failure('API baglanti hatasi: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Bizimhesap exception: ' . $e->getMessage());
            return InvoiceResult::failure('Baglanti hatasi: ' . $e->getMessage());
        }
    }

    /**
     * Get invoice by ID (placeholder)
     */
    public function getInvoice(string $invoiceId): ?array
    {
        return null;
    }
}

/**
 * Invoice creation result
 */
class InvoiceResult
{
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $invoiceId = null,
        public ?string $invoiceUrl = null,
        public ?string $message = null,
        public ?string $error = null,
    ) {
    }

    public static function created(string $invoiceId, ?string $invoiceUrl = null): self
    {
        return new self(
            success: true,
            status: 'created',
            invoiceId: $invoiceId,
            invoiceUrl: $invoiceUrl,
        );
    }

    public static function pending(?string $message = null): self
    {
        return new self(
            success: false,
            status: 'pending',
            message: $message,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            status: 'failed',
            error: $error,
        );
    }
}
