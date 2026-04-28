<?php

namespace App\Services\Erp\Providers;

use App\Interfaces\ErpIntegrationInterface;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StockMountProvider implements ErpIntegrationInterface
{
    protected string $baseUrl = 'https://out.stockmount.com';
    protected UserIntegration $integration;
    protected int $timeout = 30;
    protected ?string $apiCode = null;
    protected ?int $storeId = null;

    public function __construct(UserIntegration $integration)
    {
        $this->integration = $integration;

        // Check cached token
        $extras = $this->integration->extra_params ?? [];
        $this->apiCode = $extras['api_code'] ?? null;
        $this->storeId = $extras['store_id'] ?? null;
    }

    public function getName(): string
    {
        return 'stockmount';
    }

    /**
     * Login and get API token
     */
    protected function doLogin(): array
    {
        try {
            $extras = $this->integration->extra_params ?? [];
            $username = $extras['username'] ?? null;
            $password = $extras['password'] ?? null;

            // Method 1: Username + Password
            if ($username && $password) {
                $loginData = [
                    'Username' => $username,
                    'Password' => $password,
                ];
            }
            // Method 2: ApiKey + ApiPassword
            elseif ($this->integration->api_key && $this->integration->api_secret) {
                $loginData = [
                    'ApiKey' => $this->integration->api_key,
                    'ApiPassword' => $this->integration->api_secret,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Giriş bilgileri eksik. Username/Password veya ApiKey/ApiPassword gerekli.',
                ];
            }

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/user/dologin', $loginData);

            $data = $response->json();

            Log::info('StockMount DoLogin Response', [
                'status' => $response->status(),
                'result' => $data['Result'] ?? null,
            ]);

            if ($response->successful() && ($data['Result'] ?? false)) {
                $this->apiCode = $data['Response']['ApiCode'] ?? null;

                if ($this->apiCode) {
                    $extras['api_code'] = $this->apiCode;
                    $this->integration->update(['extra_params' => $extras]);
                }

                return [
                    'success' => true,
                    'message' => 'Giriş başarılı.',
                    'data' => $data['Response'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['ErrorMessage'] ?? $data['Message'] ?? 'Giriş başarısız.',
            ];
        } catch (\Exception $e) {
            Log::error('StockMount DoLogin Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Giriş hatası: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Ensure authenticated
     */
    protected function ensureAuthenticated(): bool
    {
        if (!$this->apiCode) {
            $result = $this->doLogin();
            return $result['success'];
        }
        return true;
    }

    public function testConnection(): bool
    {
        try {
            $result = $this->doLogin();

            if ($result['success']) {
                // Update status
                $this->integration->update([
                    'status' => 'active',
                    'error_message' => null,
                ]);
                return true;
            }

            $this->integration->update([
                'status' => 'error',
                'error_message' => $result['message'],
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('StockMount connection error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get product sources
     */
    protected function getProductSources(): array
    {
        if (!$this->ensureAuthenticated()) {
            return [];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/Product/GetProductSources', [
                    'ApiCode' => $this->apiCode
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['Result'] ?? false)) {
                return $data['Response'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('StockMount GetProductSources Error: ' . $e->getMessage());
            return [];
        }
    }

    public function getProducts(int $page = 1, int $limit = 100): array
    {
        if (!$this->ensureAuthenticated()) {
            return [];
        }

        try {
            // Get sources first
            $sources = $this->getProductSources();
            if (empty($sources)) {
                Log::warning('StockMount: No product sources found');
                return [];
            }

            $sourceId = $sources[0]['ProductSourceId'] ?? null;
            if (!$sourceId) {
                return [];
            }

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/Product/GetProducts', [
                    'ApiCode' => $this->apiCode,
                    'ProductSourceId' => $sourceId,
                    'RowsByPage' => $limit,
                    'PageIndex' => $page,
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['Result'] ?? false)) {
                return $data['Response']['Products'] ?? [];
            }

            Log::error('StockMount getProducts failed: ' . ($data['ErrorMessage'] ?? 'Unknown'));
            return [];
        } catch (\Exception $e) {
            Log::error('StockMount getProducts exception: ' . $e->getMessage());
            return [];
        }
    }

    public function mapToSystemSchema(array $erpProduct): array
    {
        return [
            'barcode' => $erpProduct['Barcode'] ?? null,
            'name' => $erpProduct['Name'] ?? null,
            'stock' => (int) ($erpProduct['Quantity'] ?? 0),
            'price' => (float) ($erpProduct['Price'] ?? 0),
            'currency' => 'TRY',
            'vat_rate' => (int) ($erpProduct['TaxRate'] ?? 0),
        ];
    }

    /**
     * Ensure we have a valid store ID
     */
    protected function ensureStoreId(): array
    {
        if ($this->storeId) {
            return ['success' => true];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/Store/GetStores', [
                    'ApiCode' => $this->apiCode
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['Result'] ?? false)) {
                $stores = $data['Response'] ?? [];
                if (!empty($stores)) {
                    $this->storeId = $stores[0]['StoreId'] ?? null;

                    if ($this->storeId) {
                        $extras = $this->integration->extra_params ?? [];
                        $extras['store_id'] = $this->storeId;
                        $this->integration->update(['extra_params' => $extras]);

                        return ['success' => true];
                    }
                }
            }

            return [
                'success' => false,
                'message' => 'Mağaza bilgisi alınamadı.'
            ];
        } catch (\Exception $e) {
            Log::error('StockMount GetStores Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Mağaza hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get authenticated HTTP client
     */
    protected function getHttpClient()
    {
        return Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
    }

    /**
     * Create order in StockMount
     */
    public function createOrder(array $orderData): array
    {
        try {
            if (!$this->ensureAuthenticated()) {
                return [
                    'success' => false,
                    'message' => 'Kimlik doğrulama başarısız.',
                    'data' => null
                ];
            }

            $storeResult = $this->ensureStoreId();
            if (!$storeResult['success']) {
                return $storeResult;
            }

            $criteria = [
                'IntegrationOrderCode' => $orderData['order_code'] ?? null,
                'Nickname' => $orderData['nickname'] ?? ($orderData['name'] ?? '') . ' ' . ($orderData['surname'] ?? ''),
                'Fullname' => $orderData['fullname'] ?? ($orderData['name'] ?? '') . ' ' . ($orderData['surname'] ?? ''),
                'Name' => $orderData['name'] ?? '',
                'Surname' => $orderData['surname'] ?? '',
                'CompanyTitle' => $orderData['company_title'] ?? 'Bireysel',
                'OrderDate' => $orderData['order_date'] ?? now()->toIso8601String(),
                'ListingStatus' => $orderData['order_status'] ?? 'New',
                'OrderStatus' => $orderData['order_status'] ?? 'New',
                'PersonalIdentification' => $orderData['personal_id'] ?? '',
                'TaxNumber' => $orderData['tax_number'] ?? '',
                'TaxAuthority' => $orderData['tax_authority'] ?? '',
                'Telephone' => $orderData['telephone'] ?? '',
                'Address' => $orderData['address'] ?? '',
                'District' => $orderData['district'] ?? '',
                'City' => $orderData['city'] ?? '',
                'ZipCode' => $orderData['zip_code'] ?? '34000',
                'Notes' => $orderData['notes'] ?? '',
                'OrderDetails' => [],
            ];

            // Add order details (products)
            if (isset($orderData['items']) && is_array($orderData['items'])) {
                foreach ($orderData['items'] as $item) {
                    $prodCode = (string)($item['product_code'] ?? $item['sku'] ?? '');

                    $criteria['OrderDetails'][] = [
                        'IntegrationProductCode' => $prodCode,
                        'ProductName' => $item['product_name'] ?? $item['name'] ?? '',
                        'Quantity' => $item['quantity'] ?? 1,
                        'Price' => $item['price'] ?? 0,
                        'Telephone' => $item['telephone'] ?? $orderData['telephone'] ?? '',
                        'Address' => $item['address'] ?? $orderData['address'] ?? '',
                        'District' => $item['district'] ?? $orderData['district'] ?? '',
                        'City' => $item['city'] ?? $orderData['city'] ?? '',
                        'ZipCode' => $item['zip_code'] ?? '34000',
                        'DeliveryTitle' => $item['delivery_title'] ?? ($orderData['name'] ?? '') . ' ' . ($orderData['surname'] ?? ''),
                        'TaxRate' => $item['tax_rate'] ?? 20,
                        'Barcode' => $item['barcode'] ?? '',
                        'ProductCode' => $prodCode,
                        'CargoPayment' => 'Buyer',
                    ];
                }
            }

            $payload = [
                'ApiCode' => $this->apiCode,
                'StoreId' => $this->storeId,
                'Order' => $criteria
            ];

            Log::info('StockMount SetOrder Request', [
                'order_code' => $criteria['IntegrationOrderCode'],
                'items_count' => count($criteria['OrderDetails']),
            ]);

            $response = $this->getHttpClient()
                ->post($this->baseUrl . '/api/Integration/SetOrder', $payload);

            $data = $response->json();

            Log::info('StockMount SetOrder Response', [
                'status' => $response->status(),
                'result' => $data['Result'] ?? null,
            ]);

            // Check for session expired error
            if (($data['ErrorCode'] ?? '') === '00006') {
                $extras = $this->integration->extra_params ?? [];
                unset($extras['api_code']);
                $this->integration->update(['extra_params' => $extras]);
                $this->apiCode = null;

                return $this->createOrder($orderData);
            }

            if ($response->successful() && ($data['Result'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'Sipariş başarıyla oluşturuldu.',
                    'data' => $data['Response'],
                    'order_id' => $data['Response']['OrderId'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $data['ErrorMessage'] ?? $data['Message'] ?? 'Sipariş oluşturulamadı.',
                'error_code' => $data['ErrorCode'] ?? null,
                'data' => $data
            ];
        } catch (\Exception $e) {
            Log::error('StockMount SetOrder Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Sipariş oluşturma hatası: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Sync order from system to StockMount
     */
    public function syncOrder($order): array
    {
        $customer = $order->customer ?? null;
        $user = $customer->user ?? null;
        $address = $order->invoiceAddress ?? $order->deliveryAddress ?? $order->address ?? null;

        $addressText = $address->address ?? $address->address_line_1 ?? '';
        if (empty($addressText) && $address) {
            $parts = [];
            if (!empty($address->street)) $parts[] = $address->street;
            if (!empty($address->building_no)) $parts[] = 'No:' . $address->building_no;
            if (!empty($address->neighborhood)) $parts[] = $address->neighborhood;
            $addressText = implode(' ', $parts);
        }
        if (empty($addressText)) {
            $addressText = 'Adres Belirtilmemiş';
        }

        $city = $address->city ?? $address->province ?? '';
        $district = $address->district ?? $address->area ?? '';

        if (empty($city) && !empty($district)) {
            $city = $district;
            $district = 'Merkez';
        }
        if (empty($city)) {
            $city = 'Istanbul';
        }

        $fullName = $user->name ?? 'Müşteri';
        $nameParts = explode(' ', trim($fullName));
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : $firstName;

        $orderData = [
            'order_code' => $order->prefix . $order->order_code,
            'nickname' => $fullName,
            'name' => $firstName,
            'surname' => $lastName,
            'fullname' => $fullName,
            'company_title' => $address->company_name ?? $fullName,
            'order_status' => 'New',
            'order_date' => $order->created_at->toIso8601String(),
            'telephone' => $address->phone ?? $address->mobile ?? $user->phone ?? '5550000000',
            'address' => $addressText . ' ' . $district . ' ' . $city,
            'district' => $district,
            'city' => $city,
            'tax_number' => $customer->tax_number ?? '',
            'tax_authority' => $customer->tax_office ?? '',
            'notes' => $order->order_note ?? '',
            'items' => [],
        ];

        foreach ($order->products as $product) {
            $orderData['items'][] = [
                'product_code' => $product->sku ?? $product->id,
                'product_name' => $product->name,
                'quantity' => $product->pivot->quantity,
                'price' => $product->pivot->price,
                'tax_rate' => $product->vat_rate ?? $product->tax ?? 20,
                'barcode' => $product->barcode ?? '',
            ];
        }

        return $this->createOrder($orderData);
    }

    /**
     * Create invoice (delegates to createOrder for StockMount)
     */
    public function createInvoice(array $invoiceData): array
    {
        return $this->createOrder($invoiceData);
    }
}
