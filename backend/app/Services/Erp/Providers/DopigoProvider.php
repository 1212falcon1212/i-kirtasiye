<?php

namespace App\Services\Erp\Providers;

use App\Interfaces\ErpIntegrationInterface;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DopigoProvider implements ErpIntegrationInterface
{
    protected string $baseUrl = 'https://panel.dopigo.com';
    protected UserIntegration $integration;
    protected int $timeout = 30;
    protected ?string $token = null;

    public function __construct(UserIntegration $integration)
    {
        $this->integration = $integration;

        // Check cached token
        $extras = $this->integration->extra_params ?? [];
        $this->token = $extras['token'] ?? null;
    }

    public function getName(): string
    {
        return 'dopigo';
    }

    /**
     * Get authentication token
     */
    protected function getToken(): ?string
    {
        if ($this->token) {
            return $this->token;
        }

        // Check cache
        $cacheKey = 'dopigo_token_' . $this->integration->id;
        $cachedToken = Cache::get($cacheKey);

        if ($cachedToken) {
            $this->token = $cachedToken;
            return $this->token;
        }

        // Get new token
        try {
            $extras = $this->integration->extra_params ?? [];
            $username = $extras['username'] ?? $this->integration->api_key;
            $password = $extras['password'] ?? $this->integration->api_secret;

            $response = Http::asMultipart()
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/users/get_auth_token/', [
                    ['name' => 'username', 'contents' => $username],
                    ['name' => 'password', 'contents' => $password],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->token = $data['token'] ?? null;

                if ($this->token) {
                    // Cache for 30 days
                    Cache::put($cacheKey, $this->token, now()->addDays(30));

                    // Save to integration
                    $extras['token'] = $this->token;
                    $this->integration->update(['extra_params' => $extras]);
                }

                return $this->token;
            }

            Log::error('Dopigo Token Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Dopigo Token Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get authenticated HTTP client
     */
    protected function getHttpClient()
    {
        $token = $this->getToken();

        return Http::withHeaders([
            'Authorization' => 'Token ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout($this->timeout);
    }

    public function testConnection(): bool
    {
        try {
            $token = $this->getToken();

            if (!$token) {
                $this->integration->update([
                    'status' => 'error',
                    'error_message' => 'Token alınamadı. Kullanıcı adı ve şifrenizi kontrol edin.',
                ]);
                return false;
            }

            // Test with products endpoint
            $response = $this->getHttpClient()
                ->get($this->baseUrl . '/api/v1/products/all/', ['limit' => 1]);

            if ($response->successful()) {
                $this->integration->update([
                    'status' => 'active',
                    'error_message' => null,
                ]);
                return true;
            }

            if ($response->status() === 401) {
                Cache::forget('dopigo_token_' . $this->integration->id);
            }

            $this->integration->update([
                'status' => 'error',
                'error_message' => 'Bağlantı başarısız: ' . $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Dopigo connection error: ' . $e->getMessage());
            return false;
        }
    }

    public function getProducts(int $page = 1, int $limit = 100): array
    {
        try {
            // Rate limiting
            usleep(500000); // 500ms

            $response = $this->getHttpClient()
                ->get($this->baseUrl . '/api/v1/products/all/');

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['results'] ?? [];
                $products = [];

                foreach ($results as $meta) {
                    // Skip archived
                    if (!empty($meta['archived'])) {
                        continue;
                    }

                    $metaName = $meta['name'] ?? '';
                    $vat = $meta['vat'] ?? 18;
                    $variants = $meta['products'] ?? [];

                    foreach ($variants as $product) {
                        $products[] = [
                            'id' => $product['id'] ?? null,
                            'sku' => $product['sku'] ?? null,
                            'barcode' => $product['barcode'] ?? null,
                            'name' => $product['invoice_name'] ?? $metaName,
                            'price' => (float) ($product['price'] ?? 0),
                            'stock' => (int) ($product['stock'] ?? 0),
                            'vat' => $vat,
                            'currency' => $product['price_currency'] ?? 'TRY',
                        ];
                    }
                }

                // Only return first page results
                if ($page > 1 && empty($data['next'])) {
                    return [];
                }

                return $products;
            }

            Log::error('Dopigo getProducts failed: ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('Dopigo getProducts exception: ' . $e->getMessage());
            return [];
        }
    }

    public function mapToSystemSchema(array $erpProduct): array
    {
        return [
            'barcode' => $erpProduct['barcode'] ?? null,
            'name' => $erpProduct['name'] ?? null,
            'stock' => (int) ($erpProduct['stock'] ?? 0),
            'price' => (float) ($erpProduct['price'] ?? 0),
            'currency' => $erpProduct['currency'] ?? 'TRY',
            'vat_rate' => (int) ($erpProduct['vat'] ?? 18),
        ];
    }

    /**
     * Sync order from system to Dopigo
     */
    public function syncOrder($order): array
    {
        try {
            // Rate limit: 2 requests per second
            $rateLimitKey = 'dopigo_rate_limit_' . $this->integration->id;
            $lastRequest = Cache::get($rateLimitKey);

            if ($lastRequest && (now()->timestamp - $lastRequest) < 1) {
                usleep(500000); // Wait 500ms
            }

            $customer = $order->customer ?? null;
            $user = $customer->user ?? null;
            $address = $order->address ?? null;
            $billingAddress = $order->billing_address ?? $address;

            // Helper to format phone
            $formatPhone = function($phone) {
                if (!$phone) return '+905555555555';
                $phone = preg_replace('/\D/', '', $phone);

                if (strlen($phone) === 12 && str_starts_with($phone, '90')) {
                    return '+' . $phone;
                }

                if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
                    $phone = substr($phone, 1);
                }

                if (strlen($phone) === 10) {
                    return '+90' . $phone;
                }

                if (strlen($phone) > 12 || strlen($phone) < 10) {
                    return '+905555555555';
                }

                return $phone;
            };

            // Build address object
            $addressData = [
                'full_address' => ($address->address_line ?? $address->address ?? '') . ' ' . ($address->address_line2 ?? ''),
                'contact_full_name' => $address->name ?? ($user->name ?? ''),
                'contact_phone_number' => $formatPhone($address->phone ?? ($user->phone ?? '')),
                'city' => $address->city ?? $address->area ?? '',
                'district' => $address->district ?? $address->area ?? '',
                'zip_code' => $address->postal_code ?? $address->post_code ?? '',
            ];

            $billingAddressData = [
                'full_address' => ($billingAddress->address_line ?? $billingAddress->address ?? ($addressData['full_address'] ?? '')),
                'contact_full_name' => $billingAddress->name ?? $addressData['contact_full_name'],
                'contact_phone_number' => $formatPhone($billingAddress->phone ?? $addressData['contact_phone_number']),
                'city' => $billingAddress->city ?? $billingAddress->area ?? $addressData['city'],
                'district' => $billingAddress->district ?? $billingAddress->area ?? $addressData['district'],
                'zip_code' => $billingAddress->postal_code ?? $billingAddress->post_code ?? $addressData['zip_code'],
            ];

            // Determine account type
            $accountType = 'person';
            $taxId = null;
            $taxOffice = null;
            $companyName = '';

            if ($customer && !empty($customer->tax_number)) {
                $accountType = 'company';
                $taxId = $customer->tax_number;
                $taxOffice = $customer->tax_office ?? null;
                $companyName = $customer->company_name ?? '';
            }

            // Build order items
            $items = [];
            $orderProducts = $order->products ?? [];

            foreach ($orderProducts as $product) {
                $quantity = $product->pivot->quantity ?? 1;
                $unitPrice = $product->pivot->price ?? $product->price ?? 0;
                $totalPrice = $quantity * $unitPrice;
                $vatRate = $product->vat_tax->rate ?? 18;

                $items[] = [
                    'service_item_id' => $order->id . '-' . $product->id . '-' . uniqid(),
                    'service_product_id' => (string) $product->id,
                    'service_shipment_code' => $order->tracking_number ?? null,
                    'sku' => $product->sku ?: ($product->barcode ?: ('PROD-' . $product->id)),
                    'attributes' => '',
                    'name' => $product->name,
                    'amount' => $quantity,
                    'price' => number_format($totalPrice, 2, '.', ''),
                    'unit_price' => number_format($unitPrice, 2, '.', ''),
                    'shipment_campaign_code' => null,
                    'buyer_pays_shipment' => false,
                    'status' => 'shipped',
                    'vat' => $vatRate,
                    'tax_ratio' => $vatRate,
                    'product' => [
                        'sku' => $product->sku ?: ($product->barcode ?: ('PROD-' . $product->id)),
                    ]
                ];
            }

            // Build order payload
            $orderData = [
                'service' => 1,
                'service_name' => 'b2b-kirtasiye',
                'sales_channel' => 'i-kirtasiye.com',
                'service_created' => $order->created_at->format('Y-m-d H:i:s'),
                'service_value' => $order->prefix . $order->order_code,
                'service_order_id' => (string) $order->id,
                'customer' => [
                    'account_type' => $accountType,
                    'full_name' => $user->name ?? 'Müşteri',
                    'address' => $addressData,
                    'email' => $user->email ?? '',
                    'phone_number' => $formatPhone($address->phone ?? ($user->phone ?? '')),
                    'tax_id' => $taxId,
                    'tax_office' => $taxOffice,
                    'company_name' => $companyName,
                ],
                'billing_address' => $billingAddressData,
                'shipping_address' => $addressData,
                'shipped_date' => null,
                'payment_type' => 'undefined',
                'status' => 'shipped',
                'total' => number_format($order->payable_amount ?? 0, 2, '.', ''),
                'service_fee' => number_format($order->shipping_charge ?? 0, 2, '.', ''),
                'discount' => null,
                'archived' => false,
                'notes' => $order->note ?? '',
                'items' => $items,
            ];

            Log::info('Dopigo Order Request', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
            ]);

            $response = $this->getHttpClient()
                ->post($this->baseUrl . '/api/v1/orders/', $orderData);

            // Update rate limit cache
            Cache::put($rateLimitKey, now()->timestamp, 60);

            Log::info('Dopigo Order Response', [
                'status' => $response->status(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Sipariş Dopigo\'ya başarıyla gönderildi.',
                    'order_id' => $responseData['id'] ?? null,
                    'order_number' => $responseData['service_value'] ?? ($order->prefix . $order->order_code),
                    'invoice_number' => $responseData['invoice_number'] ?? null,
                ];
            }

            $errorMessage = 'Sipariş gönderilemedi';
            if ($response->status() === 401) {
                Cache::forget('dopigo_token_' . $this->integration->id);
                $errorMessage = 'Kimlik doğrulama başarısız.';
            } elseif ($response->status() === 400) {
                $errorData = $response->json();
                $errorMessage = 'Geçersiz istek: ' . json_encode($errorData);
            } else {
                $errorMessage .= ': ' . $response->body();
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => $response->json() ?? null
            ];
        } catch (\Exception $e) {
            Log::error('Dopigo Order Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Sipariş gönderme hatası: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create invoice - wrapper for syncOrder
     * Dopigo uses orders as invoices
     */
    public function createInvoice(array $invoiceData): array
    {
        if (isset($invoiceData['order']) && is_object($invoiceData['order'])) {
            return $this->syncOrder($invoiceData['order']);
        }

        return [
            'success' => false,
            'message' => 'Dopigo için sipariş nesnesi gerekli. createInvoice yerine syncOrder kullanın.',
            'data' => null
        ];
    }
}
