<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncErpProductsJob;
use App\Models\Order;
use App\Models\UserIntegration;
use App\Services\Erp\ErpManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IntegrationController extends Controller
{
    /**
     * List user integrations with masked details
     */
    public function index()
    {
        $integrations = auth()->user()->integrations;

        return response()->json([
            'status' => 'success',
            'data' => $integrations->map(function ($integration) {
                $extras = $integration->extra_params ?? [];
                $credentials = $integration->getMaskedCredentials();

                return [
                    'id' => $integration->id,
                    'erp_type' => $integration->erp_type,
                    'status' => $integration->status,
                    'last_sync_at' => $integration->last_sync_at,
                    'error_message' => $integration->error_message,
                    'is_configured' => !empty($integration->api_key) || !empty($extras['username']),
                    'credentials' => $credentials,
                ];
            })
        ]);
    }

    /**
     * Create or update integration settings
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'erp_type' => 'required|string|in:entegra,bizimhesap,parasut,sentos,stockmount,dopigo,kolaysoft',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'app_id' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'test_mode' => 'nullable|boolean',
            'wsdl_url' => 'nullable|string',
        ]);

        $extraParams = [];
        if (!empty($validated['username']))
            $extraParams['username'] = $validated['username'];
        if (!empty($validated['password']))
            $extraParams['password'] = $validated['password'];
        if (isset($validated['test_mode']))
            $extraParams['test_mode'] = $validated['test_mode'];
        if (!empty($validated['wsdl_url']))
            $extraParams['wsdl_url'] = $validated['wsdl_url'];

        $integration = $request->user()->integrations()->updateOrCreate(
            ['erp_type' => $validated['erp_type']],
            [
                'api_key' => $validated['api_key'] ?? '',
                'api_secret' => $validated['api_secret'] ?? '',
                'app_id' => $validated['app_id'] ?? null,
                'status' => 'pending', // Reset status on update
                'error_message' => null,
                'extra_params' => $extraParams,
            ]
        );

        return response()->json([
            'message' => 'Entegrasyon başarıyla kaydedildi',
            'data' => $integration
        ]);
    }

    /**
     * Trigger manual sync
     */
    public function sync(string $erpType)
    {
        $integration = auth()->user()->integrations()
            ->where('erp_type', $erpType)
            ->firstOrFail();

        // Dispatch job
        SyncErpProductsJob::dispatch($integration);

        return response()->json([
            'status' => 'success',
            'message' => 'Senkronizasyon işlemi kuyruğa alındı. Birkaç dakika sürebilir.',
        ]);
    }

    /**
     * Remove integration
     */
    public function destroy(string $erpType)
    {
        auth()->user()->integrations()
            ->where('erp_type', $erpType)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Entegrasyon kaldırıldı.',
        ]);
    }

    /**
     * Sync order to ERP (create invoice/order)
     */
    public function syncOrder(Request $request, int $orderId)
    {
        $validated = $request->validate([
            'erp_type' => 'nullable|string|in:entegra,bizimhesap,parasut,sentos,stockmount,dopigo,kolaysoft',
        ]);

        $user = auth()->user();

        // Find the order
        $order = Order::with([
            'items.product.vatTax',
            'items.seller',
            'user.customer',
            'address',
            'billingAddress'
        ])->find($orderId);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sipariş bulunamadı.',
            ], 404);
        }

        // Check if user is seller for this order or super admin
        $isSeller = $order->items->contains('seller_id', $user->id);

        if (!$isSeller && !$user->isSuperAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu sipariş için yetkiniz yok.',
            ], 403);
        }

        // Get user's ERP integration
        $query = $user->integrations()->where('status', 'active');

        if (!empty($validated['erp_type'])) {
            $query->where('erp_type', $validated['erp_type']);
        }

        $integration = $query->first();

        if (!$integration) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aktif ERP entegrasyonu bulunamadı.',
            ], 400);
        }

        try {
            $erpManager = new ErpManager();
            $driver = $erpManager->getDriver($integration);

            // Adapt order model for ERP provider
            $adaptedOrder = $this->adaptOrderForErp($order, $user);

            $result = $driver->syncOrder($adaptedOrder);

            // Log sync attempt
            Log::info('ERP Order Sync', [
                'erp_type' => $integration->erp_type,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'success' => $result['success'] ?? false,
            ]);

            if ($result['success'] ?? false) {
                // Update integration last sync time
                $integration->update(['last_sync_at' => now()]);

                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'] ?? 'Sipariş ERP sistemine başarıyla gönderildi.',
                    'data' => [
                        'erp_type' => $integration->erp_type,
                        'order_id' => $result['order_id'] ?? null,
                        'invoice_id' => $result['invoice_id'] ?? null,
                        'order_number' => $result['order_number'] ?? $order->order_number,
                    ]
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Sipariş gönderilemedi.',
                'data' => $result['data'] ?? null,
            ], 400);
        } catch (\Exception $e) {
            Log::error('ERP Sync Error', [
                'erp_type' => $integration->erp_type,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'ERP senkronizasyon hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create invoice via ERP
     */
    public function createInvoice(Request $request, int $orderId)
    {
        $validated = $request->validate([
            'erp_type' => 'nullable|string|in:entegra,bizimhesap,parasut,sentos,stockmount,dopigo,kolaysoft',
        ]);

        $user = auth()->user();

        // Find the order
        $order = Order::with([
            'items.product.vatTax',
            'items.seller',
            'user.customer',
            'address',
            'billingAddress'
        ])->find($orderId);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sipariş bulunamadı.',
            ], 404);
        }

        // Check permissions
        $isSeller = $order->items->contains('seller_id', $user->id);

        if (!$isSeller && !$user->isSuperAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu sipariş için yetkiniz yok.',
            ], 403);
        }

        // Get integration
        $query = $user->integrations()->where('status', 'active');

        if (!empty($validated['erp_type'])) {
            $query->where('erp_type', $validated['erp_type']);
        }

        $integration = $query->first();

        if (!$integration) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aktif ERP entegrasyonu bulunamadı.',
            ], 400);
        }

        try {
            $erpManager = new ErpManager();
            $driver = $erpManager->getDriver($integration);

            $adaptedOrder = $this->adaptOrderForErp($order, $user);

            $result = $driver->createInvoice(['order' => $adaptedOrder]);

            Log::info('ERP Invoice Create', [
                'erp_type' => $integration->erp_type,
                'order_id' => $order->id,
                'success' => $result['success'] ?? false,
            ]);

            if ($result['success'] ?? false) {
                $integration->update(['last_sync_at' => now()]);

                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'] ?? 'Fatura başarıyla oluşturuldu.',
                    'data' => [
                        'erp_type' => $integration->erp_type,
                        'invoice_id' => $result['invoice_id'] ?? null,
                        'invoice_no' => $result['invoice_no'] ?? null,
                    ]
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Fatura oluşturulamadı.',
                'data' => $result['data'] ?? null,
            ], 400);
        } catch (\Exception $e) {
            Log::error('ERP Invoice Error', [
                'erp_type' => $integration->erp_type,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Fatura oluşturma hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Adapt order model for ERP providers
     * Creates a virtual object that matches the expected structure
     */
    private function adaptOrderForErp(Order $order, $user): object
    {
        // Create an adapted order object that works with all ERP providers
        $adaptedOrder = new \stdClass();

        $adaptedOrder->id = $order->id;
        $adaptedOrder->order_code = $order->order_number;
        $adaptedOrder->prefix = '';
        $adaptedOrder->created_at = $order->created_at;
        $adaptedOrder->total_amount = $order->total_amount;
        $adaptedOrder->payable_amount = $order->total_amount;
        $adaptedOrder->shipping_charge = 0;
        $adaptedOrder->note = $order->notes;
        $adaptedOrder->order_note = $order->notes;
        $adaptedOrder->tracking_number = $order->tracking_number;

        // Customer info
        $customer = new \stdClass();
        $customer->id = $order->user_id;
        $customer->tax_number = $order->user->customer->tax_number ?? '';
        $customer->tax_office = $order->user->customer->tax_office ?? '';
        $customer->company_name = $order->user->customer->company_name ?? $order->user->business_name ?? '';

        $customerUser = new \stdClass();
        $customerUser->id = $order->user_id;
        $customerUser->name = $order->user->name ?? $order->user->business_name ?? 'Müşteri';
        $customerUser->email = $order->user->email ?? '';
        $customerUser->phone = $order->user->phone ?? '';

        $customer->user = $customerUser;
        $adaptedOrder->customer = $customer;

        // Address
        $shippingAddress = $order->shipping_address ?? [];
        $address = new \stdClass();
        $address->name = $shippingAddress['name'] ?? '';
        $address->phone = $shippingAddress['phone'] ?? '';
        $address->address = $shippingAddress['address'] ?? '';
        $address->address_line = $shippingAddress['address'] ?? '';
        $address->city = $shippingAddress['city'] ?? '';
        $address->district = $shippingAddress['district'] ?? '';
        $address->postal_code = $shippingAddress['postal_code'] ?? '';
        $address->post_code = $shippingAddress['postal_code'] ?? '';

        $adaptedOrder->address = $address;
        $adaptedOrder->deliveryAddress = $address;
        $adaptedOrder->invoiceAddress = $address;
        $adaptedOrder->billing_address = $address;

        // Products - only include seller's items
        $products = collect();
        foreach ($order->items as $item) {
            // Only include items from current seller
            if ($item->seller_id !== $user->id && !$user->isSuperAdmin()) {
                continue;
            }

            $product = new \stdClass();
            $product->id = $item->product_id;
            $product->name = $item->product->name ?? 'Ürün';
            $product->sku = $item->product->sku ?? null;
            $product->barcode = $item->product->barcode ?? null;
            $product->price = $item->unit_price;
            $product->vat_rate = $item->product->vatTax->rate ?? 18;

            // Create pivot object
            $pivot = new \stdClass();
            $pivot->quantity = $item->quantity;
            $pivot->price = $item->unit_price;

            $product->pivot = $pivot;

            // VAT tax object
            $vatTax = new \stdClass();
            $vatTax->rate = $item->product->vatTax->rate ?? 18;
            $product->vat_tax = $vatTax;

            $products->push($product);
        }

        $adaptedOrder->products = $products;

        return $adaptedOrder;
    }

    /**
     * Test ERP connection
     */
    public function testConnection(string $erpType)
    {
        $integration = auth()->user()->integrations()
            ->where('erp_type', $erpType)
            ->first();

        if (!$integration) {
            return response()->json([
                'status' => 'error',
                'message' => 'Entegrasyon bulunamadı.',
            ], 404);
        }

        try {
            $erpManager = new ErpManager();
            $driver = $erpManager->getDriver($integration);

            $connected = $driver->testConnection();

            return response()->json([
                'status' => $connected ? 'success' : 'error',
                'message' => $connected ? 'Bağlantı başarılı.' : 'Bağlantı başarısız.',
                'data' => [
                    'erp_type' => $erpType,
                    'status' => $integration->fresh()->status,
                    'error_message' => $integration->fresh()->error_message,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bağlantı testi hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * KolaySoft specific: Get prefix list
     */
    public function kolaysoftPrefixList()
    {
        $integration = auth()->user()->integrations()
            ->where('erp_type', 'kolaysoft')
            ->first();

        if (!$integration) {
            return response()->json([
                'status' => 'error',
                'message' => 'KolaySoft entegrasyonu bulunamadı.',
            ], 404);
        }

        try {
            $erpManager = new ErpManager();
            $driver = $erpManager->getDriver($integration);

            if (!method_exists($driver, 'getPrefixList')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bu özellik sadece KolaySoft için geçerlidir.',
                ], 400);
            }

            $result = $driver->getPrefixList();

            Log::info('KolaySoft getPrefixList API Response', [
                'user_id' => auth()->id(),
                'result' => $result,
            ]);

            return response()->json([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'] ?? 'Tamamlandı',
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('KolaySoft getPrefixList API Error', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Hata: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * KolaySoft specific: Get credit count
     */
    public function kolaysoftCreditCount()
    {
        $integration = auth()->user()->integrations()
            ->where('erp_type', 'kolaysoft')
            ->first();

        if (!$integration) {
            return response()->json([
                'status' => 'error',
                'message' => 'KolaySoft entegrasyonu bulunamadı.',
            ], 404);
        }

        try {
            $erpManager = new ErpManager();
            $driver = $erpManager->getDriver($integration);

            if (!method_exists($driver, 'getCreditCount')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bu özellik sadece KolaySoft için geçerlidir.',
                ], 400);
            }

            $result = $driver->getCreditCount();

            Log::info('KolaySoft getCreditCount API Response', [
                'user_id' => auth()->id(),
                'result' => $result,
            ]);

            return response()->json([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'] ?? 'Tamamlandı',
                'data' => [
                    'credit_count' => $result['credit_count'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('KolaySoft getCreditCount API Error', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Hata: ' . $e->getMessage(),
            ], 500);
        }
    }
}
