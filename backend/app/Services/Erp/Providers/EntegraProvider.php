<?php

namespace App\Services\Erp\Providers;

use App\Interfaces\ErpIntegrationInterface;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EntegraProvider implements ErpIntegrationInterface
{
    protected string $baseUrl = 'https://apiv2.entegrabilisim.com';
    protected UserIntegration $integration;
    protected ?string $accessToken = null;
    protected ?string $refreshToken = null;
    protected string $cacheKey;
    protected string $rateLimitKey;

    protected int $timeout = 30;

    public function __construct(UserIntegration $integration)
    {
        $this->integration = $integration;
        $this->cacheKey = 'entegra_token_' . $integration->id;
        $this->rateLimitKey = 'entegra_rate_limit_' . $integration->id;
        $this->loadCachedToken();
    }

    public function getName(): string
    {
        return 'entegra';
    }

    /**
     * Load cached access token
     */
    private function loadCachedToken(): void
    {
        $cached = Cache::get($this->cacheKey);
        if ($cached) {
            $this->accessToken = $cached['access'] ?? null;
            $this->refreshToken = $cached['refresh'] ?? null;
        }
    }

    /**
     * Save token to cache
     * Access token: 1 week (604800 seconds)
     */
    private function cacheToken(array $tokenData): void
    {
        $tokenCache = [
            'access' => $tokenData['access'] ?? null,
            'refresh' => $tokenData['refresh'] ?? null,
        ];

        Cache::put($this->cacheKey, $tokenCache, now()->addSeconds(604800 - 60));

        $this->accessToken = $tokenCache['access'];
        $this->refreshToken = $tokenCache['refresh'];
    }

    /**
     * Check rate limit (7200 requests/hour)
     */
    private function checkRateLimit(): bool
    {
        $hourKey = $this->rateLimitKey . '_' . date('Y-m-d-H');
        $requestCount = Cache::get($hourKey, 0);

        if ($requestCount >= 7200) {
            Log::warning('Entegra Rate Limit: 7200/hour limit exceeded', [
                'integration_id' => $this->integration->id,
                'hour' => date('Y-m-d-H')
            ]);
            return false;
        }

        Cache::put($hourKey, $requestCount + 1, 3600);
        return true;
    }

    /**
     * Authenticate and get token
     */
    protected function authenticate(): ?string
    {
        try {
            if (!$this->checkRateLimit()) {
                return null;
            }

            $extras = $this->integration->extra_params ?? [];
            $email = $extras['username'] ?? $this->integration->api_key;
            $password = $extras['password'] ?? $this->integration->api_secret;

            $response = Http::timeout($this->timeout)
                ->asJson()
                ->post($this->baseUrl . '/api/user/token/obtain/', [
                    'email' => $email,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['access']) && isset($responseData['refresh'])) {
                    $this->cacheToken($responseData);
                    return $responseData['access'];
                }
            }

            Log::error('Entegra Auth Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Entegra Auth Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Refresh access token
     */
    private function refreshAccessToken(): ?string
    {
        try {
            if (!$this->refreshToken) {
                Log::warning('Entegra: Refresh token not found, re-authenticating');
                return $this->authenticate();
            }

            if (!$this->checkRateLimit()) {
                return null;
            }

            $response = Http::timeout($this->timeout)
                ->asJson()
                ->post($this->baseUrl . '/api/user/token/refresh/', [
                    'refresh' => $this->refreshToken,
                ]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['access']) && isset($responseData['refresh'])) {
                    $this->cacheToken($responseData);
                    return $responseData['access'];
                }
            }

            Log::warning('Entegra: Refresh token invalid, re-authenticating');
            Cache::forget($this->cacheKey);
            $this->accessToken = null;
            $this->refreshToken = null;
            return $this->authenticate();
        } catch (\Throwable $e) {
            Log::error('Entegra Refresh Token Error: ' . $e->getMessage());
            return $this->authenticate();
        }
    }

    /**
     * Ensure authenticated
     */
    private function ensureAuthenticated(): ?string
    {
        if (!$this->accessToken) {
            return $this->authenticate();
        }

        $cached = Cache::get($this->cacheKey);
        if (!$cached || !isset($cached['access'])) {
            return $this->authenticate();
        }

        return $this->accessToken;
    }

    /**
     * Get authenticated HTTP client
     */
    private function getHttpClient()
    {
        $token = $this->ensureAuthenticated();

        if (!$token) {
            throw new \Exception('Entegra API token could not be obtained');
        }

        return Http::timeout($this->timeout)->withHeaders([
            'Authorization' => 'JWT ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Make HTTP request with retry on 401
     */
    private function makeRequest(string $method, string $url, ?array $data = null)
    {
        if (!$this->checkRateLimit()) {
            return null;
        }

        try {
            $client = $this->getHttpClient();

            if ($method === 'GET') {
                $response = $client->get($url, $data);
            } else {
                $response = $client->post($url, $data);
            }

            // Handle 401 - refresh token and retry
            if ($response->status() === 401) {
                Log::info('Entegra: 401 error, refreshing token');
                $newToken = $this->refreshAccessToken();

                if ($newToken) {
                    $client = Http::timeout($this->timeout)->withHeaders([
                        'Authorization' => 'JWT ' . $newToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ]);

                    if ($method === 'GET') {
                        $response = $client->get($url, $data);
                    } else {
                        $response = $client->post($url, $data);
                    }
                }
            }

            return $response;
        } catch (\Throwable $e) {
            Log::error('Entegra Request Error: ' . $e->getMessage(), [
                'url' => $url,
                'method' => $method,
            ]);
            return null;
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('GET', $this->baseUrl . '/store/getMarketplaceQuantitySettings');

            if ($response && $response->successful()) {
                $this->integration->update([
                    'status' => 'active',
                    'error_message' => null,
                ]);
                return true;
            }

            // Try alternative endpoint
            $response = $this->makeRequest('GET', $this->baseUrl . '/store/getStores');

            if ($response && $response->successful()) {
                $this->integration->update([
                    'status' => 'active',
                    'error_message' => null,
                ]);
                return true;
            }

            $errorMessage = 'Connection failed';
            if ($response) {
                if ($response->status() === 401) {
                    $errorMessage = 'Authentication failed. Check your email and password.';
                } else {
                    $errorMessage .= ': ' . ($response->json()['message'] ?? $response->body());
                }
            }

            $this->integration->update([
                'status' => 'error',
                'error_message' => $errorMessage,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Entegra Connection Error: ' . $e->getMessage());
            $this->integration->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getProducts(int $page = 1, int $limit = 100): array
    {
        try {
            $url = $this->baseUrl . '/product/page=' . $page . '/';
            $response = $this->makeRequest('GET', $url);

            if ($response && $response->successful()) {
                $data = $response->json();
                return $data['porductList'] ?? $data['productList'] ?? [];
            }

            Log::error('Entegra getProducts failed: ' . ($response ? $response->body() : 'No response'));
            return [];
        } catch (\Throwable $e) {
            Log::error('Entegra getProducts exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync products with normalized data
     */
    public function syncProducts(int $page = 1, int $perPage = 100): array
    {
        try {
            $url = $this->baseUrl . '/product/page=' . $page . '/';
            $response = $this->makeRequest('GET', $url);

            if ($response && $response->successful()) {
                $data = $response->json();
                $products = $data['porductList'] ?? [];
                $totalProduct = $data['totalProduct'] ?? count($products);

                $normalizedProducts = [];
                foreach ($products as $product) {
                    $normalizedProducts[] = [
                        'id' => $product['id'] ?? null,
                        'sku' => $product['productCode'] ?? null,
                        'name' => $product['name'] ?? '',
                        'description' => $product['description'] ?? '',
                        'price' => $product['price2'] ?? $product['price1'] ?? 0,
                        'cost' => $product['buying_price'] ?? 0,
                        'stock' => $product['quantity'] ?? 0,
                        'vat_rate' => $this->getKdvRate($product['kdv_id'] ?? 18),
                        'barcode' => $product['barcode'] ?? null,
                        'category' => $product['group'] ?? null,
                        'brand' => $product['brand'] ?? null,
                        'variations' => $product['variatios'] ?? [],
                    ];
                }

                return [
                    'success' => true,
                    'data' => $normalizedProducts,
                    'raw_data' => $data,
                    'message' => 'Products fetched successfully.',
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $totalProduct,
                        'has_more' => count($products) >= $perPage,
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Products could not be fetched',
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Entegra Products Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Product fetch error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create product in Entegra
     */
    public function createProduct(array $productData): array
    {
        try {
            $url = $this->baseUrl . '/product/';

            $requiredFields = ['status', 'quantity', 'group', 'productName', 'productCode', 'barcode', 'price1', 'kdv_id', 'currencyType'];
            foreach ($requiredFields as $field) {
                if (!isset($productData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Required field missing: {$field}",
                        'data' => null
                    ];
                }
            }

            $productPayload = [
                'status' => (int)($productData['status'] ?? 1),
                'quantity' => (int)($productData['quantity'] ?? 0),
                'group' => (int)($productData['group'] ?? 1),
                'productCode' => $productData['productCode'] ?? '',
                'productName' => $productData['productName'] ?? '',
                'barcode' => $productData['barcode'] ?? '',
                'price1' => (float)($productData['price1'] ?? 0),
                'kdv_id' => (int)($productData['kdv_id'] ?? 18),
                'currencyType' => $productData['currencyType'] ?? 'TRL',
                'description' => $productData['description'] ?? '',
                'brand' => $productData['brand'] ?? '',
                'supplier' => $productData['supplier'] ?? 'Manual',
                'supplier_id' => $productData['supplier_id'] ?? $productData['productCode'] ?? '',
            ];

            $payload = ['list' => [$productPayload]];

            $response = $this->makeRequest('POST', $url, $payload);

            if ($response && $response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Product created successfully.',
                    'product_id' => $responseData['id'] ?? ($responseData['list'][0]['id'] ?? null),
                ];
            }

            return [
                'success' => false,
                'message' => 'Product could not be created: ' . ($response ? $response->body() : 'No response'),
                'data' => $response ? $response->json() : null
            ];
        } catch (\Throwable $e) {
            Log::error('Entegra Create Product Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Product creation error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create order in Entegra
     */
    public function createOrder(array $orderData): array
    {
        try {
            $url = $this->baseUrl . '/order/';

            $payload = [
                'customer_id' => $orderData['customer_id'] ?? null,
                'firstname' => $orderData['firstname'] ?? '',
                'lastname' => $orderData['lastname'] ?? '',
                'email' => $orderData['email'] ?? '',
                'mobil_phone' => $orderData['mobil_phone'] ?? $orderData['phone'] ?? '',
                'telephone' => $orderData['telephone'] ?? '',
                'invoice_address' => $orderData['invoice_address'] ?? '',
                'invoice_city' => $orderData['invoice_city'] ?? '',
                'invoice_district' => $orderData['invoice_district'] ?? '',
                'invoice_postcode' => $orderData['invoice_postcode'] ?? '',
                'ship_address' => $orderData['ship_address'] ?? $orderData['invoice_address'] ?? '',
                'ship_city' => $orderData['ship_city'] ?? $orderData['invoice_city'] ?? '',
                'ship_district' => $orderData['ship_district'] ?? $orderData['invoice_district'] ?? '',
                'ship_postcode' => $orderData['ship_postcode'] ?? $orderData['invoice_postcode'] ?? '',
                'total' => $orderData['total'] ?? 0,
                'grand_total' => $orderData['grand_total'] ?? $orderData['total'] ?? 0,
                'order_product' => $orderData['order_product'] ?? [],
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            if ($response && $response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Order created successfully.',
                    'order_id' => $responseData['id'] ?? null,
                    'order_number' => $responseData['no'] ?? $responseData['order_number'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Order could not be created: ' . ($response ? $response->body() : 'No response'),
                'data' => $response ? $response->json() : null
            ];
        } catch (\Throwable $e) {
            Log::error('Entegra Create Order Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Order creation error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get categories
     */
    public function getCategories(int $page = 1): array
    {
        try {
            $url = $this->baseUrl . '/category/page=' . $page . '/';
            $response = $this->makeRequest('GET', $url);

            if ($response && $response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['categories'] ?? [],
                    'message' => 'Categories fetched successfully.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Categories could not be fetched',
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Entegra Get Categories Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Category fetch error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get stores
     */
    public function getStores(): array
    {
        try {
            $url = $this->baseUrl . '/store/getStores';
            $response = $this->makeRequest('GET', $url);

            if ($response && $response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['stores'] ?? [],
                    'message' => 'Stores fetched successfully.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Stores could not be fetched',
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Entegra Get Stores Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Store fetch error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get orders
     */
    public function getOrders(int $page = 1, array $filters = []): array
    {
        try {
            $url = $this->baseUrl . '/order/page=' . $page . '/';

            $queryParams = [];
            if (isset($filters['limit'])) {
                $queryParams['limit'] = min($filters['limit'], 200);
            }
            if (isset($filters['id'])) {
                $queryParams['id'] = $filters['id'];
            }
            if (isset($filters['order_number'])) {
                $queryParams['order_number'] = $filters['order_number'];
            }
            if (isset($filters['status'])) {
                $queryParams['status'] = $filters['status'];
            }
            if (isset($filters['start_date'])) {
                $queryParams['start_date'] = $filters['start_date'];
            }
            if (isset($filters['end_date'])) {
                $queryParams['end_date'] = $filters['end_date'];
            }

            $response = $this->makeRequest('GET', $url, $queryParams);

            if ($response && $response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['orders'] ?? [],
                    'total' => $data['totalOrder'] ?? 0,
                    'message' => 'Orders fetched successfully.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Orders could not be fetched',
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Entegra Get Orders Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Order fetch error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function mapToSystemSchema(array $erpProduct): array
    {
        $price = (float)($erpProduct['price2'] ?? $erpProduct['price1'] ?? 0);
        $stock = (int)($erpProduct['quantity'] ?? 0);

        return [
            'barcode' => $erpProduct['barcode'] ?? null,
            'name' => $erpProduct['name'] ?? null,
            'stock' => $stock,
            'price' => $price > 0 ? $price : (float)($erpProduct['price1'] ?? 0),
            'currency' => $erpProduct['currencyType'] ?? 'TRY',
            'vat_rate' => $this->getKdvRate($erpProduct['kdv_id'] ?? 18),
        ];
    }

    /**
     * Get KDV rate from kdv_id
     */
    private function getKdvRate(int $kdvId): int
    {
        $rates = [
            0 => 0,
            8 => 8,
            10 => 10,
            18 => 18,
            20 => 20,
        ];

        return $rates[$kdvId] ?? 18;
    }

    /**
     * Sync order from system to Entegra
     */
    public function syncOrder($order): array
    {
        try {
            $customer = $order->customer ?? null;
            $user = $customer->user ?? null;
            $address = $order->address ?? $order->deliveryAddress ?? null;
            $billingAddress = $order->billing_address ?? $order->invoiceAddress ?? $address;

            // Parse name
            $fullName = $user->name ?? 'Müşteri';
            $nameParts = explode(' ', trim($fullName));
            $firstName = $nameParts[0] ?? '';
            $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : $firstName;

            // Build address strings
            $invoiceAddressText = $billingAddress->address ?? $billingAddress->address_line ?? '';
            if (empty($invoiceAddressText) && $billingAddress) {
                $parts = [];
                if (!empty($billingAddress->street)) $parts[] = $billingAddress->street;
                if (!empty($billingAddress->building_no)) $parts[] = 'No:' . $billingAddress->building_no;
                if (!empty($billingAddress->neighborhood)) $parts[] = $billingAddress->neighborhood;
                $invoiceAddressText = implode(' ', $parts);
            }

            $shipAddressText = $address->address ?? $address->address_line ?? $invoiceAddressText;
            if (empty($shipAddressText) && $address) {
                $parts = [];
                if (!empty($address->street)) $parts[] = $address->street;
                if (!empty($address->building_no)) $parts[] = 'No:' . $address->building_no;
                if (!empty($address->neighborhood)) $parts[] = $address->neighborhood;
                $shipAddressText = implode(' ', $parts);
            }

            // Build order products
            $orderProducts = [];
            foreach ($order->products as $product) {
                $quantity = $product->pivot->quantity ?? 1;
                $unitPrice = $product->pivot->price ?? $product->price ?? 0;
                $vatRate = $product->vat_tax->rate ?? $product->vat_rate ?? 18;

                $orderProducts[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->sku ?? $product->barcode ?? ('PROD-' . $product->id),
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                    'kdv_id' => $vatRate,
                ];
            }

            $orderData = [
                'customer_id' => $customer->id ?? null,
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $user->email ?? '',
                'mobil_phone' => $address->phone ?? $user->phone ?? '',
                'telephone' => $address->phone ?? $user->phone ?? '',
                'invoice_address' => $invoiceAddressText,
                'invoice_city' => $billingAddress->city ?? $billingAddress->province ?? '',
                'invoice_district' => $billingAddress->district ?? $billingAddress->area ?? '',
                'invoice_postcode' => $billingAddress->postal_code ?? $billingAddress->post_code ?? '',
                'ship_address' => $shipAddressText,
                'ship_city' => $address->city ?? $address->province ?? '',
                'ship_district' => $address->district ?? $address->area ?? '',
                'ship_postcode' => $address->postal_code ?? $address->post_code ?? '',
                'total' => $order->total_amount ?? $order->payable_amount ?? 0,
                'grand_total' => $order->payable_amount ?? $order->total_amount ?? 0,
                'order_product' => $orderProducts,
                'order_number' => $order->prefix . $order->order_code,
                'note' => $order->note ?? $order->order_note ?? '',
            ];

            // Add tax info if company
            if ($customer && !empty($customer->tax_number)) {
                $orderData['tax_number'] = $customer->tax_number;
                $orderData['tax_office'] = $customer->tax_office ?? '';
                $orderData['company_name'] = $customer->company_name ?? '';
            }

            Log::info('Entegra syncOrder Request', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
            ]);

            $result = $this->createOrder($orderData);

            if ($result['success']) {
                $result['order_number'] = $order->prefix . $order->order_code;
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Entegra syncOrder Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Sipariş gönderme hatası: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create invoice - wrapper for syncOrder
     * Entegra uses orders as invoices
     */
    public function createInvoice(array $invoiceData): array
    {
        if (isset($invoiceData['order']) && is_object($invoiceData['order'])) {
            return $this->syncOrder($invoiceData['order']);
        }

        // If raw order data provided, use createOrder directly
        if (isset($invoiceData['order_product']) || isset($invoiceData['firstname'])) {
            return $this->createOrder($invoiceData);
        }

        return [
            'success' => false,
            'message' => 'Entegra için sipariş nesnesi veya sipariş verisi gerekli.',
            'data' => null
        ];
    }
}
