<?php

namespace App\Services\Erp\Providers;

use App\Interfaces\ErpIntegrationInterface;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ParasutProvider implements ErpIntegrationInterface
{
    protected string $baseUrl = 'https://api.parasut.com';
    protected string $authUrl = 'https://api.parasut.com/oauth/token';
    protected UserIntegration $integration;
    protected ?string $accessToken = null;
    protected ?string $refreshToken = null;
    protected string $cacheKey;
    protected string $companyId;

    protected int $timeout = 30;

    // Cargo Companies Mapping (VKN and Official Titles)
    private const CARGO_COMPANIES = [
        'yurtici' => ['title' => 'Yurtiçi Kargo Servisi A.Ş.', 'vkn' => '9860008925'],
        'aras' => ['title' => 'Aras Kargo Yurt İçi Yurt Dışı Taşımacılık A.Ş.', 'vkn' => '0720039666'],
        'mng' => ['title' => 'MNG Kargo Yurtiçi ve Yurtdışı Taşımacılık A.Ş.', 'vkn' => '6080712084'],
        'surat' => ['title' => 'Sürat Kargo Lojistik ve Dağıtım A.Ş.', 'vkn' => '7321640262'],
        'ptt' => ['title' => 'Posta ve Telgraf Teşkilatı A.Ş.', 'vkn' => '7320068060'],
        'trendyol_express' => ['title' => 'Trendyol Lojistik A.Ş.', 'vkn' => '8590921777'],
        'hepsijet' => ['title' => 'D Fast Dağıtım Hizmetleri ve Lojistik A.Ş.', 'vkn' => '2650701090'],
        'kolay_gelsin' => ['title' => 'Kolay Gelsin Dağıtım Hizmetleri A.Ş.', 'vkn' => '2910804196'],
    ];

    public function __construct(UserIntegration $integration)
    {
        $this->integration = $integration;
        $this->companyId = $integration->app_id ?? '';
        $this->cacheKey = 'parasut_token_' . $integration->id;
        $this->loadCachedToken();
    }

    public function getName(): string
    {
        return 'parasut';
    }

    /**
     * Get API URL with company ID
     */
    private function getApiUrl(string $endpoint): string
    {
        return $this->baseUrl . '/v4/' . $this->companyId . '/' . ltrim($endpoint, '/');
    }

    /**
     * Load cached access token
     */
    private function loadCachedToken(): void
    {
        $cached = Cache::get($this->cacheKey);
        if ($cached) {
            $this->accessToken = $cached['access_token'] ?? null;
            $this->refreshToken = $cached['refresh_token'] ?? null;
        }
    }

    /**
     * Save token to cache
     */
    private function cacheToken(array $tokenData, int $expiresIn = 7200): void
    {
        Cache::put($this->cacheKey, $tokenData, now()->addSeconds($expiresIn - 60));
        $this->accessToken = $tokenData['access_token'];
        $this->refreshToken = $tokenData['refresh_token'];
    }

    /**
     * Authenticate with password grant
     */
    protected function authenticate(): array
    {
        try {
            $extras = $this->integration->extra_params ?? [];

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->authUrl, [
                    'grant_type' => 'password',
                    'client_id' => $this->integration->api_key,
                    'client_secret' => $this->integration->api_secret,
                    'username' => $extras['username'] ?? '',
                    'password' => $extras['password'] ?? '',
                    'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->cacheToken($data, $data['expires_in'] ?? 7200);

                return [
                    'success' => true,
                    'message' => 'Authentication successful.',
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Authentication failed: ' . ($response->json()['error_description'] ?? $response->body()),
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Parasut Auth Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Authentication error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Refresh access token
     */
    private function refreshAccessToken(): array
    {
        if (!$this->refreshToken) {
            return $this->authenticate();
        }

        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->authUrl, [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->integration->api_key,
                    'client_secret' => $this->integration->api_secret,
                    'refresh_token' => $this->refreshToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->cacheToken($data, $data['expires_in'] ?? 7200);

                return [
                    'success' => true,
                    'message' => 'Token refreshed.',
                    'data' => $data
                ];
            }

            return $this->authenticate();
        } catch (\Throwable $e) {
            Log::error('Parasut Token Refresh Error: ' . $e->getMessage());
            return $this->authenticate();
        }
    }

    /**
     * Ensure authenticated
     */
    private function ensureAuthenticated(): array
    {
        if (!$this->accessToken) {
            return $this->authenticate();
        }
        return ['success' => true];
    }

    /**
     * Get authenticated HTTP client
     */
    private function getHttpClient()
    {
        $this->ensureAuthenticated();

        return Http::timeout($this->timeout)->withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Make API request with automatic token refresh
     */
    protected function apiRequest(string $method, string $endpoint, ?array $data = null): array
    {
        $authResult = $this->ensureAuthenticated();
        if (!$authResult['success']) {
            return $authResult;
        }

        try {
            $client = $this->getHttpClient();
            $url = $this->getApiUrl($endpoint);

            if ($method === 'GET') {
                $response = $client->get($url, $data);
            } elseif ($method === 'POST') {
                $response = $client->post($url, $data);
            } elseif ($method === 'PUT') {
                $response = $client->put($url, $data);
            } elseif ($method === 'DELETE') {
                $response = $client->delete($url);
            }

            // Handle 401 - Token expired
            if ($response->status() === 401) {
                $refreshResult = $this->refreshAccessToken();
                if (!$refreshResult['success']) {
                    return $refreshResult;
                }
                return $this->apiRequest($method, $endpoint, $data);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Request successful.'
                ];
            }

            Log::error('Parasut API Request Failed', [
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'API Error: ' . $response->body(),
                'data' => $response->json()
            ];
        } catch (\Throwable $e) {
            Log::error('Parasut API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'API error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function testConnection(): bool
    {
        $authResult = $this->authenticate();
        if (!$authResult['success']) {
            $this->integration->update([
                'status' => 'error',
                'error_message' => $authResult['message'],
            ]);
            return false;
        }

        $result = $this->apiRequest('GET', 'me');
        if ($result['success']) {
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
    }

    public function getProducts(int $page = 1, int $limit = 25): array
    {
        try {
            $result = $this->apiRequest('GET', 'products', [
                'page' => [
                    'number' => $page,
                    'size' => $limit
                ],
            ]);

            if ($result['success']) {
                return $result['data']['data'] ?? [];
            }

            Log::error('Parasut getProducts failed: ' . $result['message']);
            return [];
        } catch (\Throwable $e) {
            Log::error('Parasut getProducts exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync products with pagination
     */
    public function syncProducts(int $page = 1, int $perPage = 25): array
    {
        $result = $this->apiRequest('GET', 'products', [
            'page' => [
                'number' => $page,
                'size' => $perPage,
            ]
        ]);

        if (!$result['success']) {
            return $result;
        }

        $products = $result['data']['data'] ?? [];
        $meta = $result['data']['meta'] ?? [];

        $normalizedProducts = [];
        foreach ($products as $product) {
            $attributes = $product['attributes'] ?? [];
            $normalizedProducts[] = [
                'id' => $product['id'] ?? null,
                'sku' => $attributes['code'] ?? null,
                'name' => $attributes['name'] ?? '',
                'description' => $attributes['description'] ?? '',
                'price' => $attributes['list_price'] ?? 0,
                'cost' => $attributes['buying_price'] ?? 0,
                'stock' => $attributes['stock_count'] ?? 0,
                'vat_rate' => $attributes['vat_rate'] ?? 20,
                'barcode' => $attributes['barcode'] ?? null,
                'category' => $attributes['category'] ?? null,
                'brand' => $attributes['brand'] ?? null,
            ];
        }

        return [
            'success' => true,
            'data' => $normalizedProducts,
            'raw_data' => $result['data'],
            'message' => 'Products fetched successfully.',
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $meta['total_count'] ?? count($normalizedProducts),
                'has_more' => count($normalizedProducts) >= $perPage,
            ]
        ];
    }

    /**
     * Find or create contact
     */
    public function findOrCreateContact(array $customerData): array
    {
        // Search by email first
        if (!empty($customerData['email'])) {
            $searchResult = $this->apiRequest('GET', 'contacts', [
                'filter[email]' => $customerData['email']
            ]);

            if ($searchResult['success'] && !empty($searchResult['data']['data'])) {
                return [
                    'success' => true,
                    'data' => $searchResult['data']['data'][0],
                    'message' => 'Existing customer found.'
                ];
            }
        }

        // Search by tax number
        if (!empty($customerData['tax_number']) && $customerData['tax_number'] !== '11111111111') {
            $searchResult = $this->apiRequest('GET', 'contacts', [
                'filter[tax_number]' => $customerData['tax_number']
            ]);

            if ($searchResult['success'] && !empty($searchResult['data']['data'])) {
                return [
                    'success' => true,
                    'data' => $searchResult['data']['data'][0],
                    'message' => 'Existing customer found (by tax number).'
                ];
            }
        }

        // Create new contact
        $contactPayload = [
            'data' => [
                'type' => 'contacts',
                'attributes' => [
                    'name' => $customerData['name'],
                    'email' => $customerData['email'] ?? '',
                    'phone' => $customerData['phone'] ?? '',
                    'tax_number' => $customerData['tax_number'] ?? '',
                    'tax_office' => $customerData['tax_office'] ?? '',
                    'contact_type' => $customerData['contact_type'] ?? 'customer',
                    'account_type' => $customerData['account_type'] ?? 'customer',
                    'address' => $customerData['address'] ?? '',
                    'city' => $customerData['city'] ?? '',
                    'district' => $customerData['district'] ?? '',
                    'country' => $customerData['country'] ?? 'Türkiye',
                    'postal_code' => $customerData['postal_code'] ?? '',
                ]
            ]
        ];

        return $this->apiRequest('POST', 'contacts', $contactPayload);
    }

    /**
     * Create sales invoice
     */
    public function createSalesInvoice(array $orderData, string $contactId): array
    {
        $invoiceItems = [];

        foreach ($orderData['items'] as $item) {
            $detailItem = [
                'type' => 'sales_invoice_details',
                'attributes' => [
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['vat_rate'] ?? 20,
                    'description' => $item['name'],
                ]
            ];

            $sku = $item['sku'] ?? null;
            if (empty($sku)) {
                $sku = 'GEN-' . strtoupper(substr(md5($item['name']), 0, 8));
            }

            if ($sku) {
                $product = $this->findProductByCode($sku);

                if ($product) {
                    $productId = $product['id'];
                } else {
                    $newProductData = [
                        'name' => $item['name'],
                        'code' => $sku,
                        'vat_rate' => $item['vat_rate'] ?? 20,
                        'unit' => 'Adet',
                        'list_price' => $item['unit_price'],
                        'buying_price' => 0,
                        'inventory_tracking' => true,
                    ];
                    $createResult = $this->createProduct($newProductData);
                    if ($createResult['success']) {
                        $productId = $createResult['data']['data']['id'];
                    }
                }

                if (isset($productId)) {
                    $detailItem['relationships']['product'] = [
                        'data' => [
                            'id' => $productId,
                            'type' => 'products'
                        ]
                    ];
                }
            }

            $invoiceItems[] = $detailItem;
        }

        $attributes = [
            'item_type' => 'invoice',
            'description' => $orderData['description'] ?? 'Order Invoice',
            'issue_date' => $orderData['issue_date'] ?? now()->format('Y-m-d'),
            'due_date' => $orderData['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
            'currency' => $orderData['currency'] ?? 'TRL',
            'exchange_rate' => $orderData['exchange_rate'] ?? 1,
            'billing_address' => $orderData['customer_address'] ?? '',
            'billing_phone' => $orderData['customer_phone'] ?? '',
            'city' => $orderData['customer_city'] ?? '',
            'district' => $orderData['customer_district'] ?? '',
            'country' => $orderData['customer_country'] ?? 'Türkiye',
            'tax_number' => $orderData['customer_tax_number'] ?? '',
            'tax_office' => $orderData['customer_tax_office'] ?? '',
            'shipment_included' => true,
        ];

        if (!empty($orderData['series'])) {
            $attributes['invoice_series'] = $orderData['series'];
        }
        if (!empty($orderData['invoice_no'])) {
            $attributes['invoice_id'] = $orderData['invoice_no'];
        }

        $invoicePayload = [
            'data' => [
                'type' => 'sales_invoices',
                'attributes' => $attributes,
                'relationships' => [
                    'contact' => [
                        'data' => [
                            'id' => $contactId,
                            'type' => 'contacts'
                        ]
                    ],
                    'details' => [
                        'data' => $invoiceItems
                    ]
                ]
            ]
        ];

        return $this->apiRequest('POST', 'sales_invoices', $invoicePayload);
    }

    /**
     * Find product by code (SKU)
     */
    public function findProductByCode(string $sku)
    {
        $response = $this->apiRequest('GET', 'products', [
            'filter[code]' => $sku
        ]);

        if ($response['success'] && !empty($response['data']['data'])) {
            return $response['data']['data'][0];
        }

        return null;
    }

    /**
     * Create product in Parasut
     */
    public function createProduct(array $productData): array
    {
        $payload = [
            'data' => [
                'type' => 'products',
                'attributes' => [
                    'name' => $productData['name'],
                    'code' => $productData['code'] ?? null,
                    'vat_rate' => $productData['vat_rate'] ?? 20,
                    'unit' => $productData['unit'] ?? 'Adet',
                    'list_price' => $productData['list_price'] ?? 0,
                    'buying_price' => $productData['buying_price'] ?? 0,
                    'inventory_tracking' => $productData['inventory_tracking'] ?? false,
                    'initial_stock_count' => $productData['initial_stock_count'] ?? 0,
                ]
            ]
        ];

        if (isset($productData['category_id'])) {
            $payload['data']['relationships']['category'] = [
                'data' => [
                    'id' => $productData['category_id'],
                    'type' => 'item_categories'
                ]
            ];
        }

        return $this->apiRequest('POST', 'products', $payload);
    }

    /**
     * Add payment to invoice
     */
    public function addPayment(string $invoiceId, float $amount, ?string $paymentDate = null, ?string $accountId = null): array
    {
        $paymentPayload = [
            'data' => [
                'type' => 'payments',
                'attributes' => [
                    'date' => $paymentDate ?? now()->format('Y-m-d'),
                    'amount' => $amount,
                    'notes' => 'Order auto payment',
                ],
                'relationships' => [
                    'sales_invoice' => [
                        'data' => [
                            'id' => $invoiceId,
                            'type' => 'sales_invoices'
                        ]
                    ]
                ]
            ]
        ];

        if ($accountId) {
            $paymentPayload['data']['attributes']['account_id'] = $accountId;
        }

        return $this->apiRequest('POST', "sales_invoices/{$invoiceId}/payments", $paymentPayload);
    }

    /**
     * Create E-Archive invoice
     */
    public function createEArchive(string $invoiceId, $order, ?string $cargoProvider = null): array
    {
        $paymentType = $this->getPaymentType($order->payment_method ?? 'Online Payment');
        $cargoInfo = $this->getCargoInfo($cargoProvider);
        $date = now()->format('Y-m-d');

        $paymentPlatform = 'Sanal Pos';

        $payload = [
            'data' => [
                'type' => 'e_archives',
                'attributes' => [
                    'internet_sale' => [
                        'url' => config('app.url', 'b2b-kirtasiye.com'),
                        'payment_type' => $paymentType,
                        'payment_platform' => $paymentPlatform,
                        'payment_date' => $order->created_at->format('Y-m-d'),
                    ],
                ],
                'relationships' => [
                    'sales_invoice' => [
                        'data' => ['id' => (string)$invoiceId, 'type' => 'sales_invoices']
                    ]
                ]
            ]
        ];

        if ($cargoInfo) {
            $payload['data']['attributes']['shipment'] = [
                'title' => $cargoInfo['title'],
                'vkn' => $cargoInfo['vkn'],
                'name' => 'Kargo Gönderimi',
                'date' => $date,
            ];
        }

        return $this->apiRequest('POST', 'e_archives', $payload);
    }

    /**
     * Create E-Invoice
     */
    public function createEInvoice(string $invoiceId, string $scenario = 'commercial'): array
    {
        return $this->apiRequest('POST', "sales_invoices/{$invoiceId}/e_invoices", [
            'data' => [
                'type' => 'e_invoices',
                'attributes' => [
                    'scenario' => $scenario
                ],
                'relationships' => [
                    'sales_invoice' => [
                        'data' => ['id' => (string)$invoiceId, 'type' => 'sales_invoices']
                    ]
                ]
            ]
        ]);
    }

    private function getPaymentType($method): string
    {
        if (in_array($method, ['Online Payment', 'online', 'stripe'])) {
            return 'KREDIKARTI/BANKAKARTI';
        }
        if (in_array($method, ['Cash Payment', 'cash'])) {
            return 'KAPIDAODEME';
        }
        return 'EFT/HAVALE';
    }

    private function getCargoInfo(?string $providerKey): ?array
    {
        if (!$providerKey) {
            return null;
        }

        $key = mb_strtolower((string)$providerKey, 'UTF-8');

        if (str_contains($key, 'yurtici') || str_contains($key, 'yurtiçi')) $key = 'yurtici';
        if (str_contains($key, 'aras')) $key = 'aras';
        if (str_contains($key, 'mng')) $key = 'mng';
        if (str_contains($key, 'surat') || str_contains($key, 'sürat')) $key = 'surat';
        if (str_contains($key, 'ptt')) $key = 'ptt';

        return self::CARGO_COMPANIES[$key] ?? null;
    }

    /**
     * Full invoice creation flow
     */
    public function createInvoice(array $invoiceData): array
    {
        $contactData = [
            'name' => $invoiceData['customer_name'],
            'email' => $invoiceData['customer_email'] ?? '',
            'phone' => $invoiceData['customer_phone'] ?? '',
            'address' => $invoiceData['customer_address'] ?? '',
            'city' => $invoiceData['customer_city'] ?? '',
            'district' => $invoiceData['customer_district'] ?? '',
            'country' => $invoiceData['customer_country'] ?? 'Türkiye',
            'postal_code' => $invoiceData['customer_postal_code'] ?? '',
            'tax_number' => $invoiceData['customer_tax_number'] ?? '',
            'tax_office' => $invoiceData['customer_tax_office'] ?? '',
            'contact_type' => $invoiceData['contact_type'] ?? 'person',
            'account_type' => 'customer',
        ];

        if (empty($contactData['tax_number']) && empty($contactData['tax_office'])) {
            if (!isset($invoiceData['is_corporate']) || !$invoiceData['is_corporate']) {
                $contactData['tax_number'] = '11111111111';
            }
        }

        $contactResult = $this->findOrCreateContact($contactData);

        if (!$contactResult['success']) {
            return $contactResult;
        }

        $contactId = $contactResult['data']['id'] ?? $contactResult['data']['data']['id'] ?? null;

        $invoiceResult = $this->createSalesInvoice($invoiceData, $contactId);

        if (!$invoiceResult['success']) {
            return $invoiceResult;
        }

        $invoiceId = $invoiceResult['data']['data']['id'] ?? null;

        $payableAmount = $invoiceResult['data']['data']['attributes']['remaining']
            ?? $invoiceResult['data']['data']['attributes']['net_total']
            ?? $invoiceResult['data']['data']['attributes']['gross_total']
            ?? $invoiceData['total_amount'];

        if (isset($invoiceData['paid']) && $invoiceData['paid'] && $payableAmount > 0) {
            $accountId = $invoiceData['account_id'] ?? null;
            $this->addPayment($invoiceId, $payableAmount, null, $accountId);
        }

        return [
            'success' => true,
            'data' => $invoiceResult['data'],
            'invoice_id' => $invoiceId,
            'contact_id' => $contactId,
            'message' => 'Invoice created successfully.'
        ];
    }

    /**
     * Sync order - create invoice from order
     */
    public function syncOrder($order): array
    {
        $customer = $order->customer;
        $address = $order->address;

        $items = [];
        foreach ($order->products as $product) {
            $items[] = [
                'name' => $product->name,
                'quantity' => $product->pivot->quantity,
                'unit_price' => $product->pivot->price,
                'vat_rate' => $product->vat_tax->rate ?? 20,
                'sku' => $product->sku ?? null,
            ];
        }

        $customerAddress = '';
        if ($address) {
            $addressParts = array_filter([
                $address->address ?? '',
                $address->district ?? '',
                $address->city ?? '',
                $address->country ?? ''
            ]);
            $customerAddress = implode(', ', $addressParts);
        }

        $invoiceData = [
            'customer_name' => $customer->user->name ?? 'Customer',
            'customer_email' => $customer->user->email ?? '',
            'customer_phone' => $address->phone ?? $customer->user->phone ?? '',
            'customer_address' => $customerAddress,
            'customer_city' => $address->city ?? '',
            'customer_district' => $address->district ?? '',
            'customer_tax_number' => $customer->tax_number ?? '',
            'customer_tax_office' => $customer->tax_office ?? '',
            'description' => 'Order #' . $order->order_code,
            'invoice_no' => $order->prefix . $order->order_code,
            'series' => 'A',
            'items' => $items,
            'total_amount' => $order->payable_amount,
            'paid' => ($order->payment_status->value ?? '') === 'Paid',
            'finalize' => false,
        ];

        return $this->createInvoice($invoiceData);
    }

    /**
     * Get contacts
     */
    public function getContacts(array $filters = []): array
    {
        return $this->apiRequest('GET', 'contacts', $filters);
    }

    /**
     * Get accounts (Kasa/Banka)
     */
    public function getAccounts(): array
    {
        return $this->apiRequest('GET', 'accounts');
    }

    /**
     * Get invoices
     */
    public function getInvoices(array $filters = []): array
    {
        return $this->apiRequest('GET', 'sales_invoices', $filters);
    }

    /**
     * Get single invoice
     */
    public function getInvoice(string $invoiceId): array
    {
        return $this->apiRequest('GET', "sales_invoices/{$invoiceId}");
    }

    /**
     * Cancel invoice
     */
    public function cancelInvoice(string $invoiceId): array
    {
        return $this->apiRequest('POST', "sales_invoices/{$invoiceId}/cancel");
    }

    public function mapToSystemSchema(array $erpProduct): array
    {
        $attr = $erpProduct['attributes'] ?? [];
        $stock = (int)($attr['stock_count'] ?? 0);

        return [
            'barcode' => $attr['barcode'] ?? null,
            'name' => $attr['name'] ?? null,
            'stock' => $stock,
            'price' => (float)($attr['list_price'] ?? 0),
            'currency' => $attr['currency'] ?? 'TRY',
            'vat_rate' => (int)($attr['vat_rate'] ?? 0),
        ];
    }
}
