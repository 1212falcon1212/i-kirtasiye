<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\Erp\ErpManager;
use App\Services\InvoiceService;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    protected PdfService $pdfService;

    public function __construct(InvoiceService $invoiceService, PdfService $pdfService)
    {
        $this->invoiceService = $invoiceService;
        $this->pdfService = $pdfService;
    }

    /**
     * Satıcının faturalarını listele
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->input('type');
        $perPage = $request->input('per_page', 15);

        $invoices = $this->invoiceService->getSellerInvoices($user->id, $type, $perPage);

        return response()->json([
            'success' => true,
            'data' => $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'type' => $invoice->type,
                    'type_label' => $invoice->getTypeLabel(),
                    'status' => $invoice->status,
                    'status_label' => $invoice->getStatusLabel(),
                    'subtotal' => $invoice->subtotal,
                    'tax_amount' => $invoice->tax_amount,
                    'total_amount' => $invoice->total_amount,
                    'formatted_total' => $invoice->formatted_total,
                    'order_number' => $invoice->order?->order_number,
                    'buyer_name' => $invoice->buyer_info['name'] ?? null,
                    'erp_status' => $invoice->erp_status,
                    'pdf_path' => $invoice->pdf_path,
                    'created_at' => $invoice->created_at->format('d.m.Y H:i'),
                ];
            }),
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /**
     * Tek fatura detayı
     */
    public function show(Invoice $invoice, Request $request): JsonResponse
    {
        $this->authorize('view', $invoice);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'type' => $invoice->type,
                'type_label' => $invoice->getTypeLabel(),
                'status' => $invoice->status,
                'status_label' => $invoice->getStatusLabel(),
                'subtotal' => $invoice->subtotal,
                'tax_rate' => $invoice->tax_rate,
                'tax_amount' => $invoice->tax_amount,
                'total_amount' => $invoice->total_amount,
                'formatted_total' => $invoice->formatted_total,
                'commission_rate' => $invoice->commission_rate,
                'commission_amount' => $invoice->commission_amount,
                'seller_info' => $invoice->seller_info,
                'buyer_info' => $invoice->buyer_info,
                'items' => $invoice->items,
                'order_id' => $invoice->order_id,
                'order_number' => $invoice->order?->order_number,
                'erp_provider' => $invoice->erp_provider,
                'erp_status' => $invoice->erp_status,
                'erp_invoice_id' => $invoice->erp_invoice_id,
                'erp_error' => $invoice->erp_error,
                'pdf_path' => $invoice->pdf_path,
                'notes' => $invoice->notes,
                'created_at' => $invoice->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Fatura dosyasını indir/görüntüle
     */
    public function download(Invoice $invoice, Request $request)
    {
        $this->authorize('download', $invoice);

        if (! $invoice->pdf_path) {
            return response()->json([
                'success' => false,
                'error' => 'Fatura dosyası bulunamadı.',
            ], 404);
        }

        $path = storage_path('app/public/'.$invoice->pdf_path);

        if (! file_exists($path)) {
            return response()->json([
                'success' => false,
                'error' => 'Dosya bulunamadı.',
            ], 404);
        }

        $filename = basename($invoice->pdf_path);
        $mimeType = mime_content_type($path);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Sipariş için fatura oluştur (satıcı tarafından)
     */
    public function createForOrder(Order $order, Request $request): JsonResponse
    {
        $user = $request->user();

        // Satıcının bu siparişte ürünü var mı kontrol et
        $hasItems = $order->items()->where('seller_id', $user->id)->exists();
        if (! $hasItems) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişte ürününüz bulunmuyor.',
            ], 403);
        }

        // Sipariş ödenmiş mi kontrol et
        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş henüz ödenmedi.',
            ], 400);
        }

        // Önceden fatura oluşturulmuş mu kontrol et
        $existingInvoice = Invoice::where('order_id', $order->id)
            ->where('seller_id', $user->id)
            ->where('type', Invoice::TYPE_SELLER)
            ->first();

        if ($existingInvoice) {
            return response()->json([
                'success' => true,
                'message' => 'Bu sipariş için fatura zaten oluşturulmuş.',
                'invoice' => [
                    'id' => $existingInvoice->id,
                    'invoice_number' => $existingInvoice->invoice_number,
                    'formatted_total' => $existingInvoice->formatted_total,
                ],
            ]);
        }

        try {
            $invoice = $this->invoiceService->createSellerInvoice($order, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Fatura başarıyla oluşturuldu.',
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'formatted_total' => $invoice->formatted_total,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Sipariş için fatura oluştur ve ERP'ye gönder (tek adımda)
     */
    public function createForOrderViaErp(Order $order, Request $request): JsonResponse
    {
        $user = $request->user();

        // Satıcının bu siparişte ürünü var mı kontrol et
        $hasItems = $order->items()->where('seller_id', $user->id)->exists();
        if (! $hasItems) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişte ürününüz bulunmuyor.',
            ], 403);
        }

        // Sipariş onaylanmış mı kontrol et (pending olmayacak)
        if ($order->status === 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş henüz onaylanmadı. Önce siparişi onaylayın.',
            ], 400);
        }

        // ERP entegrasyonu kontrol et
        $integration = $user->integrations()
            ->where('status', 'active')
            ->whereNotNull('erp_type')
            ->first();

        if (! $integration) {
            return response()->json([
                'success' => false,
                'error' => 'Aktif ERP entegrasyonu bulunamadı. Lütfen ayarlardan ERP entegrasyonunuzu yapılandırın.',
            ], 400);
        }

        // Önceden fatura oluşturulmuş mu kontrol et
        $existingInvoice = Invoice::where('order_id', $order->id)
            ->where('seller_id', $user->id)
            ->where('type', Invoice::TYPE_SELLER)
            ->first();

        try {
            // Fatura yoksa oluştur
            if (! $existingInvoice) {
                $invoice = $this->invoiceService->createSellerInvoice($order, $user->id);
            } else {
                $invoice = $existingInvoice;
            }

            // ERP'ye gönder
            $erpManager = new ErpManager;
            $driver = $erpManager->getDriver($integration);

            // Load order with relations
            $order->load([
                'items.product.vatTax',
                'items.seller',
                'user.customer',
            ]);

            $adaptedOrder = $this->adaptInvoiceOrderForErp($invoice, $order, $user);
            $result = $driver->createInvoice(['order' => $adaptedOrder]);

            Log::info('Invoice ERP Create via Order', [
                'erp_type' => $integration->erp_type,
                'invoice_id' => $invoice->id,
                'order_id' => $order->id,
                'success' => $result['success'] ?? false,
            ]);

            if ($result['success'] ?? false) {
                $invoice->update([
                    'erp_provider' => $integration->erp_type,
                    'erp_status' => 'synced',
                    'erp_invoice_id' => $result['invoice_id'] ?? $result['order_id'] ?? null,
                    'erp_synced_at' => now(),
                    'erp_error' => null,
                ]);

                $integration->update(['last_sync_at' => now()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Fatura başarıyla kesildi ve ERP sistemine gönderildi.',
                    'invoice' => [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'formatted_total' => $invoice->formatted_total,
                        'erp_invoice_id' => $invoice->erp_invoice_id,
                        'erp_provider' => $integration->erp_type,
                    ],
                ]);
            }

            // ERP hatası - fatura oluşturuldu ama gönderilmedi
            $invoice->update([
                'erp_provider' => $integration->erp_type,
                'erp_status' => 'error',
                'erp_error' => $result['message'] ?? 'ERP gönderim hatası',
            ]);

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Fatura oluşturuldu ancak ERP sistemine gönderilemedi.',
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
            ], 400);
        } catch (\Exception $e) {
            Log::error('Invoice ERP Create Error', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fatura oluşturma hatası: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Komisyon özeti (satıcı için)
     */
    public function commissionSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $summary = $this->invoiceService->getSellerCommissionSummary(
            $user->id,
            $startDate,
            $endDate
        );

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => [
                    'value' => $summary['total_sales'],
                    'formatted' => '₺'.number_format($summary['total_sales'], 2, ',', '.'),
                ],
                'total_commission' => [
                    'value' => $summary['total_commission'],
                    'formatted' => '₺'.number_format($summary['total_commission'], 2, ',', '.'),
                ],
                'total_payout' => [
                    'value' => $summary['total_payout'],
                    'formatted' => '₺'.number_format($summary['total_payout'], 2, ',', '.'),
                ],
                'order_count' => $summary['order_count'],
                'item_count' => $summary['item_count'],
                'average_commission_rate' => $summary['total_sales'] > 0
                    ? round(($summary['total_commission'] / $summary['total_sales']) * 100, 2).'%'
                    : '0%',
            ],
        ]);
    }

    /**
     * Faturayı ERP'ye gönder
     */
    public function syncToErp(Invoice $invoice, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'erp_type' => 'nullable|string|in:entegra,bizimhesap,parasut,sentos,stockmount,dopigo,kolaysoft',
        ]);

        $user = $request->user();

        $this->authorize('syncToErp', $invoice);

        // ERP entegrasyonu kontrol et
        $query = $user->integrations()->where('status', 'active');

        if (! empty($validated['erp_type'])) {
            $query->where('erp_type', $validated['erp_type']);
        }

        $integration = $query->first();

        if (! $integration) {
            return response()->json([
                'success' => false,
                'error' => 'Aktif ERP entegrasyonu bulunamadı.',
            ], 400);
        }

        try {
            $erpManager = new ErpManager;
            $driver = $erpManager->getDriver($integration);

            // Load order with relations if exists
            $order = $invoice->order;
            if ($order) {
                $order->load([
                    'items.product.vatTax',
                    'items.seller',
                    'user.customer',
                ]);

                // Create adapted order for ERP
                $adaptedOrder = $this->adaptInvoiceOrderForErp($invoice, $order, $user);
                $result = $driver->createInvoice(['order' => $adaptedOrder]);
            } else {
                // No order - send invoice data directly
                $invoiceData = $this->buildInvoiceData($invoice);
                $result = $driver->createInvoice($invoiceData);
            }

            Log::info('Invoice ERP Sync', [
                'erp_type' => $integration->erp_type,
                'invoice_id' => $invoice->id,
                'success' => $result['success'] ?? false,
            ]);

            if ($result['success'] ?? false) {
                $invoice->update([
                    'erp_provider' => $integration->erp_type,
                    'erp_status' => 'synced',
                    'erp_invoice_id' => $result['invoice_id'] ?? $result['order_id'] ?? null,
                    'erp_synced_at' => now(),
                    'erp_error' => null,
                ]);

                $integration->update(['last_sync_at' => now()]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'] ?? 'Fatura ERP sistemine gönderildi.',
                    'erp_invoice_id' => $invoice->erp_invoice_id,
                    'erp_provider' => $integration->erp_type,
                ]);
            }

            // Update invoice with error
            $invoice->update([
                'erp_provider' => $integration->erp_type,
                'erp_status' => 'error',
                'erp_error' => $result['message'] ?? 'Bilinmeyen hata',
            ]);

            return response()->json([
                'success' => false,
                'error' => $result['message'] ?? 'Fatura gönderilemedi.',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Invoice ERP Sync Error', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            $invoice->update([
                'erp_status' => 'error',
                'erp_error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'ERP senkronizasyon hatası: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * e-Fatura dosyası yükle
     */
    public function uploadInvoiceFile(Order $order, Request $request): JsonResponse
    {
        $user = $request->user();

        // Satıcı kontrolü
        $hasSellerItems = $order->items()->where('seller_id', $user->id)->exists();
        if (! $hasSellerItems && ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        // Sipariş durumu kontrolü
        if ($order->status === 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş henüz onaylanmamış.',
            ], 400);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,xml|max:10240', // max 10MB
            'type' => 'sometimes|string|in:e_invoice,receipt,other',
        ]);

        try {
            $file = $request->file('file');
            $type = $request->input('type', 'e_invoice');

            // Dosya adı oluştur
            $fileName = sprintf(
                '%s_%s_%s.%s',
                $type,
                $order->order_number,
                now()->format('YmdHis'),
                $file->getClientOriginalExtension()
            );

            // Dosyayı kaydet
            $path = $file->storeAs(
                'invoices/'.$user->id,
                $fileName,
                'public'
            );

            // Fatura kaydını kontrol et veya oluştur
            $invoice = Invoice::where('order_id', $order->id)
                ->where('seller_id', $user->id)
                ->where('type', Invoice::TYPE_SELLER)
                ->first();

            if ($invoice) {
                // Mevcut faturayı güncelle
                $invoice->update([
                    'pdf_path' => $path,
                    'notes' => ($invoice->notes ?? '').' | e-Fatura yüklendi: '.now()->format('d.m.Y H:i'),
                ]);
            } else {
                // Yeni fatura kaydı oluştur
                $invoice = $this->invoiceService->createSellerInvoice($order, $user->id);
                $invoice->update([
                    'pdf_path' => $path,
                    'notes' => 'e-Fatura yüklendi: '.now()->format('d.m.Y H:i'),
                ]);
            }

            Log::info('Invoice file uploaded', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'file_path' => $path,
                'type' => $type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'e-Fatura başarıyla yüklendi.',
                'file_path' => $path,
                'invoice_id' => $invoice->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Invoice file upload error', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Dosya yüklenirken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sipariş faturasını PDF olarak indir
     */
    public function downloadPdf(Order $order, Request $request)
    {
        $user = $request->user();

        // Kullanıcı bu siparişin sahibi mi veya satıcısı mı kontrol et
        $isBuyer = $order->user_id === $user->id;
        $isSeller = $order->items()->where('seller_id', $user->id)->exists();
        $isAdmin = $user->isSuperAdmin();

        if (! $isBuyer && ! $isSeller && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'error' => 'Bu faturaya erişim yetkiniz yok.',
            ], 403);
        }

        // Satıcı ise kendi ürünlerini filtrele, alıcı/admin ise tümünü göster
        $sellerId = null;
        if ($request->has('seller_id')) {
            $sellerId = (int) $request->input('seller_id');
        } elseif ($isSeller && ! $isBuyer && ! $isAdmin) {
            $sellerId = $user->id;
        }

        try {
            $pdf = $this->pdfService->generateOrderInvoice($order, $sellerId);

            $filename = "fatura-{$order->order_number}";
            if ($sellerId) {
                $filename .= "-s{$sellerId}";
            }

            return $pdf->download("{$filename}.pdf");
        } catch (\Exception $e) {
            Log::error('Invoice PDF generation error', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'PDF oluşturulurken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Adapt invoice order for ERP providers
     */
    private function adaptInvoiceOrderForErp(Invoice $invoice, Order $order, $user): object
    {
        $adaptedOrder = new \stdClass;

        $adaptedOrder->id = $order->id;
        $adaptedOrder->order_code = $order->order_number;
        $adaptedOrder->prefix = '';
        $adaptedOrder->created_at = $invoice->created_at;
        $adaptedOrder->total_amount = $invoice->total_amount;
        $adaptedOrder->payable_amount = $invoice->total_amount;
        $adaptedOrder->shipping_charge = 0;
        $adaptedOrder->note = $invoice->notes ?? $order->notes;
        $adaptedOrder->order_note = $invoice->notes ?? $order->notes;
        $adaptedOrder->tracking_number = $order->tracking_number;

        // Customer (buyer) info
        $customer = new \stdClass;
        $customer->id = $order->user_id;
        $customer->tax_number = $invoice->buyer_info['tax_number'] ?? '';
        $customer->tax_office = $invoice->buyer_info['tax_office'] ?? '';
        $customer->company_name = $invoice->buyer_info['company_name'] ?? $invoice->buyer_info['name'] ?? '';

        $customerUser = new \stdClass;
        $customerUser->id = $order->user_id;
        $customerUser->name = $invoice->buyer_info['name'] ?? 'Müşteri';
        $customerUser->email = $invoice->buyer_info['email'] ?? '';
        $customerUser->phone = $invoice->buyer_info['phone'] ?? '';

        $customer->user = $customerUser;
        $adaptedOrder->customer = $customer;

        // Address
        $buyerAddress = $invoice->buyer_info['address'] ?? [];
        $address = new \stdClass;
        $address->name = $invoice->buyer_info['name'] ?? '';
        $address->phone = $invoice->buyer_info['phone'] ?? '';
        $address->address = is_array($buyerAddress) ? ($buyerAddress['address'] ?? '') : $buyerAddress;
        $address->address_line = $address->address;
        $address->city = is_array($buyerAddress) ? ($buyerAddress['city'] ?? '') : '';
        $address->district = is_array($buyerAddress) ? ($buyerAddress['district'] ?? '') : '';
        $address->postal_code = is_array($buyerAddress) ? ($buyerAddress['postal_code'] ?? '') : '';

        $adaptedOrder->address = $address;
        $adaptedOrder->deliveryAddress = $address;
        $adaptedOrder->invoiceAddress = $address;
        $adaptedOrder->billing_address = $address;

        // Products from invoice items
        $products = collect();
        $invoiceItems = $invoice->items ?? [];

        foreach ($invoiceItems as $item) {
            $product = new \stdClass;
            $product->id = $item['product_id'] ?? 0;
            $product->name = $item['name'] ?? $item['product_name'] ?? 'Ürün';
            $product->sku = $item['sku'] ?? null;
            $product->barcode = $item['barcode'] ?? null;
            $product->price = $item['unit_price'] ?? $item['price'] ?? 0;
            $product->vat_rate = $item['tax_rate'] ?? $item['vat_rate'] ?? 18;

            $pivot = new \stdClass;
            $pivot->quantity = $item['quantity'] ?? 1;
            $pivot->price = $item['unit_price'] ?? $item['price'] ?? 0;

            $product->pivot = $pivot;

            $vatTax = new \stdClass;
            $vatTax->rate = $item['tax_rate'] ?? $item['vat_rate'] ?? 18;
            $product->vat_tax = $vatTax;

            $products->push($product);
        }

        $adaptedOrder->products = $products;

        return $adaptedOrder;
    }

    /**
     * Build invoice data array for direct createInvoice call
     */
    private function buildInvoiceData(Invoice $invoice): array
    {
        return [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->created_at->format('Y-m-d'),
            'buyer_name' => $invoice->buyer_info['name'] ?? '',
            'buyer_tax_number' => $invoice->buyer_info['tax_number'] ?? '',
            'buyer_tax_office' => $invoice->buyer_info['tax_office'] ?? '',
            'buyer_address' => $invoice->buyer_info['address'] ?? '',
            'buyer_email' => $invoice->buyer_info['email'] ?? '',
            'buyer_phone' => $invoice->buyer_info['phone'] ?? '',
            'subtotal' => $invoice->subtotal,
            'tax_amount' => $invoice->tax_amount,
            'total_amount' => $invoice->total_amount,
            'items' => $invoice->items ?? [],
            'notes' => $invoice->notes,
        ];
    }
}
