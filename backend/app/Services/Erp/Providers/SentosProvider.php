<?php

namespace App\Services\Erp\Providers;

use App\Interfaces\ErpIntegrationInterface;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SentosProvider implements ErpIntegrationInterface
{
    protected UserIntegration $integration;
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $rateLimitKey;

    protected int $timeout = 30;

    public function __construct(UserIntegration $integration)
    {
        $this->integration = $integration;
        $this->username = $integration->api_key ?? '';
        $this->password = $integration->api_secret ?? '';
        $this->rateLimitKey = 'sentos_rate_limit_' . $integration->id;

        // Build base URL from app_id (subdomain or full URL)
        $panelUrl = $integration->app_id ?? '';
        if ($panelUrl) {
            $panelUrl = rtrim($panelUrl, '/');
            if (preg_match('/^https?:\/\//i', $panelUrl)) {
                $this->baseUrl = $panelUrl . '/api';
            } else {
                $this->baseUrl = 'https://' . $panelUrl . '.sentos.com.tr/api';
            }
        } else {
            $this->baseUrl = 'https://api.sentos.com.tr/api';
        }
    }

    public function getName(): string
    {
        return 'sentos';
    }

    /**
     * Get authenticated HTTP client with Basic Auth
     */
    private function getHttpClient()
    {
        $auth = base64_encode($this->username . ':' . $this->password);

        return Http::timeout($this->timeout)->withHeaders([
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Check GET rate limit (2/min)
     */
    private function checkGetRateLimit(): bool
    {
        $key = $this->rateLimitKey . '_get';
        $lastRequest = Cache::get($key);

        if ($lastRequest && (now()->timestamp - $lastRequest) < 30) {
            Log::warning('Sentos GET Rate Limit', ['integration_id' => $this->integration->id]);
            return false;
        }

        Cache::put($key, now()->timestamp, 60);
        return true;
    }

    /**
     * Check POST rate limit (12/min)
     */
    private function checkPostRateLimit(): bool
    {
        $key = $this->rateLimitKey . '_post';
        $lastRequest = Cache::get($key);

        if ($lastRequest && (now()->timestamp - $lastRequest) < 5) {
            Log::warning('Sentos POST Rate Limit', ['integration_id' => $this->integration->id]);
            return false;
        }

        Cache::put($key, now()->timestamp, 60);
        return true;
    }

    public function testConnection(): bool
    {
        try {
            if (!$this->checkGetRateLimit()) {
                $this->integration->update([
                    'status' => 'error',
                    'error_message' => 'Rate limit: Please wait before retrying.',
                ]);
                return false;
            }

            $response = $this->getHttpClient()->get($this->baseUrl . '/warehouses');

            Log::info('Sentos API Test', [
                'url' => $this->baseUrl . '/warehouses',
                'status' => $response->status(),
            ]);

            if ($response->successful()) {
                $this->integration->update([
                    'status' => 'active',
                    'error_message' => null,
                ]);
                return true;
            }

            $errorMessage = 'Connection failed';
            if ($response->status() === 401) {
                $errorMessage = 'Authentication failed. Check your username and password.';
            } elseif ($response->status() === 404) {
                $errorMessage = 'Subdomain not found. Check your firm name.';
            } else {
                $errorMessage .= ': ' . ($response->json()['message'] ?? $response->body());
            }

            $this->integration->update([
                'status' => 'error',
                'error_message' => $errorMessage,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Sentos Connection Error: ' . $e->getMessage());
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
            if (!$this->checkGetRateLimit()) {
                return [];
            }

            $response = $this->getHttpClient()->get($this->baseUrl . '/products', [
                'page' => $page,
                'size' => $limit,
            ]);

            if ($response->successful()) {
                $items = $response->json();

                if (isset($items['id'])) {
                    $items = [$items];
                }

                $flattened = [];
                foreach ($items as $item) {
                    if (!empty($item['variants']) && is_array($item['variants'])) {
                        foreach ($item['variants'] as $variant) {
                            $variantItem = $variant;
                            $variantItem['_parent_name'] = $item['name'] ?? '';
                            $variantItem['_parent_price'] = $item['sale_price'] ?? 0;
                            $variantItem['_parent_currency'] = $item['currency'] ?? 'TL';
                            $variantItem['_parent_vat'] = $item['vat_rate'] ?? 0;
                            $variantItem['is_variant'] = true;
                            $flattened[] = $variantItem;
                        }
                    } else {
                        $item['is_variant'] = false;
                        $flattened[] = $item;
                    }
                }

                return $flattened;
            }

            Log::error('Sentos getProducts failed: ' . $response->body());
            return [];
        } catch (\Throwable $e) {
            Log::error('Sentos getProducts exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync products with normalization
     */
    public function syncProducts(int $page = 1, int $perPage = 100): array
    {
        try {
            if (!$this->checkGetRateLimit()) {
                return [
                    'success' => false,
                    'message' => 'Rate limit: Please wait before retrying.',
                    'data' => null
                ];
            }

            $queryParams = [
                'page' => $page,
                'size' => $perPage,
                'include' => 'category',
            ];

            $response = $this->getHttpClient()->get($this->baseUrl . '/products', $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                $products = [];
                $categoryCache = [];

                $items = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;

                foreach ($items as $product) {
                    $categoryId = $product['category_id'] ?? null;
                    $categoryName = null;

                    if ($categoryId) {
                        if (isset($categoryCache[$categoryId])) {
                            $categoryName = $categoryCache[$categoryId];
                        } else {
                            $catResult = $this->getCategory($categoryId);
                            $catData = $catResult['data'][0] ?? $catResult['data'] ?? null;
                            if ($catResult['success'] && isset($catData['name'])) {
                                $categoryName = $catData['name'];
                                $categoryCache[$categoryId] = $categoryName;
                            }
                        }
                    }

                    // Calculate total stock from stocks array
                    $stock = 0;
                    if (isset($product['stocks']) && is_array($product['stocks'])) {
                        $stock = array_sum(array_column($product['stocks'], 'stock'));
                    }

                    $products[] = [
                        'id' => $product['id'] ?? null,
                        'sku' => $product['sku'] ?? null,
                        'name' => is_array($product['name'] ?? null) ? json_encode($product['name']) : ($product['name'] ?? null),
                        'invoice_name' => $product['invoice_name'] ?? null,
                        'brand' => $product['brand'] ?? null,
                        'description' => is_array($product['description'] ?? null) ? json_encode($product['description']) : ($product['description'] ?? null),
                        'price' => isset($product['sale_price']) ? (float)str_replace(',', '.', str_replace('.', '', $product['sale_price'])) : 0,
                        'cost' => isset($product['purchase_price']) ? (float)str_replace(',', '.', str_replace('.', '', $product['purchase_price'])) : 0,
                        'currency' => $product['currency'] ?? 'TL',
                        'vat_rate' => $product['vat_rate'] ?? 20,
                        'barcode' => $product['barcode'] ?? null,
                        'stock' => $stock,
                        'stocks' => $product['stocks'] ?? [],
                        'variants' => $product['variants'] ?? [],
                        'images' => $product['images'] ?? [],
                        'category_id' => $categoryId,
                        'category_name' => $categoryName,
                    ];
                }

                return [
                    'success' => true,
                    'data' => $products,
                    'raw_data' => $data,
                    'message' => 'Products fetched successfully.',
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => count($products),
                        'has_more' => count($products) >= $perPage,
                    ]
                ];
            }

            $errorMessage = 'Products could not be fetched';
            if ($response->status() === 401) {
                $errorMessage = 'Authentication failed.';
            } elseif ($response->status() === 404) {
                $errorMessage = 'Endpoint not found.';
            } else {
                $errorMessage .= ': ' . ($response->json()['message'] ?? $response->body());
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Sentos Products Sync Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Product fetch error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create invoice in Sentos
     */
    public function createInvoice(array $invoiceData): array
    {
        try {
            $orderId = $invoiceData['order_id'] ?? $invoiceData['id'] ?? null;
            if (!$orderId) {
                return [
                    'success' => false,
                    'message' => 'Order ID required.',
                    'data' => null
                ];
            }

            if (!$this->checkPostRateLimit()) {
                return [
                    'success' => false,
                    'message' => 'Rate limit: Please wait before retrying.',
                    'data' => null
                ];
            }

            $payload = [
                'invoice_type' => $invoiceData['invoice_type'] ?? 'EARSIV',
                'invoice_number' => $invoiceData['invoice_number'] ?? $invoiceData['invoice_no'] ?? null,
                'invoice_url' => $invoiceData['invoice_url'] ?? null,
            ];

            if (!$payload['invoice_number']) {
                return [
                    'success' => false,
                    'message' => 'Invoice number required.',
                    'data' => null
                ];
            }

            $response = $this->getHttpClient()->post($this->baseUrl . '/orders/invoice/' . $orderId, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Invoice created successfully.',
                    'invoice_id' => $responseData['id'] ?? null,
                    'invoice_number' => $responseData['invoice_number'] ?? null,
                    'invoice_url' => $responseData['invoice_url'] ?? null,
                ];
            }

            $errorMessage = 'Invoice could not be created';
            if ($response->status() === 401) {
                $errorMessage = 'Authentication failed.';
            } elseif ($response->status() === 400) {
                $errorMessage = 'Invalid request: ' . ($response->json()['message'] ?? 'Invoice data missing or invalid.');
            } elseif ($response->status() === 404) {
                $errorMessage = 'Order not found.';
            } else {
                $errorMessage .= ': ' . ($response->json()['message'] ?? $response->body());
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => $response->json() ?? null
            ];
        } catch (\Throwable $e) {
            Log::error('Sentos Invoice Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Invoice creation error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get categories
     */
    public function getCategories(): array
    {
        try {
            if (!$this->checkGetRateLimit()) {
                return [
                    'success' => false,
                    'message' => 'Rate limit: Please wait before retrying.',
                    'data' => null
                ];
            }

            $response = $this->getHttpClient()->get($this->baseUrl . '/categories');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Categories fetched successfully.'
                ];
            }

            return [
                'success' => false,
                'message' => 'Categories could not be fetched: ' . ($response->json()['message'] ?? $response->body()),
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Sentos Categories Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Category fetch error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get single category by ID
     */
    public function getCategory(int $categoryId): array
    {
        try {
            $response = $this->getHttpClient()->get($this->baseUrl . '/categories/' . $categoryId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Category fetched successfully.'
                ];
            }

            return [
                'success' => false,
                'message' => 'Category could not be fetched: ' . ($response->json()['message'] ?? $response->body()),
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Sentos Get Category Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Category fetch error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get warehouses
     */
    public function getWarehouses(): array
    {
        try {
            if (!$this->checkGetRateLimit()) {
                return [
                    'success' => false,
                    'message' => 'Rate limit: Please wait before retrying.',
                    'data' => null
                ];
            }

            $response = $this->getHttpClient()->get($this->baseUrl . '/warehouses');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Warehouses fetched successfully.'
                ];
            }

            return [
                'success' => false,
                'message' => 'Warehouses could not be fetched: ' . ($response->json()['message'] ?? $response->body()),
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Sentos Warehouses Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Warehouse fetch error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Sync order - get order info
     */
    public function syncOrder($order): array
    {
        try {
            if (!$this->checkGetRateLimit()) {
                return [
                    'success' => false,
                    'message' => 'Rate limit: Please wait before retrying.',
                    'data' => null
                ];
            }

            $orderCode = null;
            $orderId = null;

            if (is_object($order) && isset($order->order_code)) {
                $orderCode = $order->prefix . $order->order_code;
            } elseif (is_string($order)) {
                $orderCode = $order;
            } elseif (is_numeric($order)) {
                $orderId = $order;
            }

            $queryParams = [];
            if ($orderCode) {
                $queryParams['order_code'] = $orderCode;
            }
            if ($orderId) {
                $queryParams['order_id'] = $orderId;
            }

            if (empty($queryParams)) {
                return [
                    'success' => false,
                    'message' => 'Order code or ID required.',
                    'data' => null
                ];
            }

            $response = $this->getHttpClient()->get($this->baseUrl . '/orders', $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                $orders = is_array($data) && isset($data[0]) ? $data : [$data];

                if (empty($orders) || (isset($orders[0]) && empty($orders[0]))) {
                    return [
                        'success' => false,
                        'message' => 'Order not found.',
                        'data' => null
                    ];
                }

                $orderData = $orders[0];

                return [
                    'success' => true,
                    'data' => $orderData,
                    'message' => 'Order fetched successfully.',
                    'order_id' => $orderData['id'] ?? null,
                    'order_code' => $orderData['order_code'] ?? null,
                    'status' => $orderData['status'] ?? null,
                    'total' => $orderData['total'] ?? null,
                ];
            }

            $errorMessage = 'Order could not be fetched';
            if ($response->status() === 401) {
                $errorMessage = 'Authentication failed.';
            } elseif ($response->status() === 404) {
                $errorMessage = 'Order not found.';
            } else {
                $errorMessage .= ': ' . ($response->json()['message'] ?? $response->body());
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => null
            ];
        } catch (\Throwable $e) {
            Log::error('Sentos Sync Order Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Order sync error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function mapToSystemSchema(array $erpProduct): array
    {
        $stocks = $erpProduct['stocks'] ?? [];
        $totalStock = 0;
        if (is_array($stocks)) {
            $totalStock = array_sum(array_column($stocks, 'stock'));
        }

        $rawPrice = $erpProduct['sale_price'] ?? $erpProduct['_parent_price'] ?? 0;
        if (is_string($rawPrice)) {
            $rawPrice = str_replace('.', '', $rawPrice);
            $rawPrice = str_replace(',', '.', $rawPrice);
        }
        $price = (float)$rawPrice;

        $name = $erpProduct['name'] ?? '';
        if (!empty($erpProduct['is_variant'])) {
            $parentName = $erpProduct['_parent_name'] ?? '';
            $variantSuffix = '';
            if (!empty($erpProduct['color'])) {
                $variantSuffix .= ' ' . $erpProduct['color'];
            }
            if (!empty($erpProduct['model']['value'])) {
                $variantSuffix .= ' ' . $erpProduct['model']['value'];
            }
            $name = trim($parentName . $variantSuffix);
        }

        return [
            'barcode' => $erpProduct['barcode'] ?? null,
            'name' => $name,
            'stock' => (int)$totalStock,
            'price' => $price,
            'currency' => $erpProduct['currency'] ?? $erpProduct['_parent_currency'] ?? 'TRY',
            'vat_rate' => (int)($erpProduct['vat_rate'] ?? $erpProduct['_parent_vat'] ?? 0),
        ];
    }
}
