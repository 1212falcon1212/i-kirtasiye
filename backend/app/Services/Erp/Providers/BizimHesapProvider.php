<?php

namespace App\Services\Erp\Providers;

use App\Interfaces\ErpIntegrationInterface;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BizimHesapProvider implements ErpIntegrationInterface
{
    protected string $baseUrl = 'https://bizimhesap.com/api/b2b';
    protected UserIntegration $integration;
    protected string $apiKey;
    protected string $token;

    protected int $timeout = 30;

    public function __construct(UserIntegration $integration)
    {
        $this->integration = $integration;
        // api_key holds the Token, api_secret holds the Key
        $this->token = $integration->api_key ?? '';
        $this->apiKey = $integration->api_secret ?? 'BZMHB2B724018943908D0B82491F203F'; // Default key from docs
    }

    public function getName(): string
    {
        return 'bizimhesap';
    }

    /**
     * Get authenticated HTTP client
     */
    private function getHttpClient()
    {
        return Http::timeout($this->timeout)->withHeaders([
            'Key' => $this->apiKey,
            'Token' => $this->token,
            'Content-Type' => 'application/json',
        ]);
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->getHttpClient()->get($this->baseUrl . '/products', [
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $this->integration->update([
                    'status' => 'active',
                    'error_message' => null,
                ]);
                return true;
            }

            $errorMessage = 'Connection failed';
            if ($response->status() === 401 || $response->status() === 403) {
                $errorMessage = 'Authentication failed. Check your API Key and Token.';
            } elseif ($response->status() === 404) {
                $errorMessage = 'Endpoint not found. Check API URL.';
            } else {
                $errorMessage .= ': ' . ($response->json()['message'] ?? $response->body());
            }

            $this->integration->update([
                'status' => 'error',
                'error_message' => $errorMessage,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('BizimHesap Connection Error: ' . $e->getMessage());
            $this->integration->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getProducts(int $page = 1, int $limit = 100): array
    {
        if ($page > 1) {
            return []; // BizimHesap may not support pagination
        }

        try {
            $response = $this->getHttpClient()->get($this->baseUrl . '/products');

            if ($response->successful()) {
                $data = $response->json();

                if (is_array($data)) {
                    if (isset($data[0]) && is_array($data[0])) {
                        return $data;
                    }
                    return $data['data'] ?? $data['list'] ?? $data['products'] ?? $data;
                }

                return $data['data'] ?? $data['result'] ?? [];
            }

            Log::error('BizimHesap getProducts failed: ' . $response->body());
            return [];
        } catch (\Throwable $e) {
            Log::error('BizimHesap getProducts exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sync products with pagination and normalization
     */
    public function syncProducts(int $page = 1, int $perPage = 100): array
    {
        try {
            $queryParams = [
                'page' => $page,
                'limit' => $perPage,
            ];

            $response = $this->getHttpClient()->get($this->baseUrl . '/products', $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                $products = [];

                if (isset($data['data']['products']) && is_array($data['data']['products'])) {
                    $products = $data['data']['products'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    if (isset($data['data'][0]) && is_array($data['data'][0])) {
                        $products = $data['data'];
                    } else {
                        $products = $data['data'];
                    }
                } elseif (is_array($data)) {
                    $products = $data;
                }

                $normalizedProducts = [];
                foreach ($products as $product) {
                    $productArray = is_object($product) ? (array) $product : $product;

                    // Decode photo JSON string if present
                    $images = [];
                    if (!empty($productArray['photo']) && is_string($productArray['photo'])) {
                        $decodedPhotos = json_decode($productArray['photo'], true);
                        if (is_array($decodedPhotos)) {
                            foreach ($decodedPhotos as $photo) {
                                if (!empty($photo['PhotoUrl'])) {
                                    $images[] = $photo['PhotoUrl'];
                                }
                            }
                        }
                    }

                    $normalizedProducts[] = [
                        'id' => $productArray['id'] ?? null,
                        'sku' => $productArray['code'] ?? $productArray['sku'] ?? null,
                        'name' => $productArray['title'] ?? $productArray['name'] ?? '',
                        'description' => $productArray['description'] ?? '',
                        'price' => $productArray['price'] ?? 0,
                        'cost' => $productArray['buyingPrice'] ?? $productArray['cost'] ?? 0,
                        'stock' => $productArray['quantity'] ?? $productArray['stock'] ?? 0,
                        'vat_rate' => $productArray['tax'] ?? $productArray['vat_rate'] ?? 20,
                        'barcode' => $productArray['barcode'] ?? null,
                        'category' => $productArray['category'] ?? null,
                        'brand' => $productArray['brand'] ?? null,
                        'images' => $images,
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
                        'total' => isset($data['total']) ? $data['total'] : count($normalizedProducts),
                        'has_more' => count($normalizedProducts) >= $perPage,
                    ]
                ];
            }

            $errorMessage = 'Products could not be fetched';
            if ($response->status() === 401 || $response->status() === 403) {
                $errorMessage = 'Authentication failed. Check your API Key and Token.';
            } else {
                $errorMessage .= ': ' . ($response->json()['message'] ?? $response->body());
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => $response->json() ?? null
            ];
        } catch (\Throwable $e) {
            Log::error('BizimHesap Sync Products Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Product fetch error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create invoice on BizimHesap
     */
    public function createInvoice(array $invoiceData): array
    {
        try {
            $response = $this->getHttpClient()->post($this->baseUrl . '/addinvoice', $invoiceData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Invoice created successfully.'
                ];
            }

            return [
                'success' => false,
                'message' => 'Invoice could not be created: ' . ($response->json()['message'] ?? $response->body()),
                'data' => $response->json()
            ];
        } catch (\Throwable $e) {
            Log::error('BizimHesap Invoice Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'API error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create commission invoice for shop
     */
    public function createCommissionInvoice($shop, string $period, array $commissionData): array
    {
        $details = [];
        $totalGross = 0;
        $totalTax = 0;
        $taxRate = 20;

        // Shipping Fee
        if (isset($commissionData['shipping_fee']) && $commissionData['shipping_fee'] > 0) {
            $amount = $commissionData['shipping_fee'];
            $tax = $amount * ($taxRate / 100);
            $details[] = $this->createDetailLine('KARGO', 'Kargo Bedeli', $amount, $taxRate);
            $totalGross += $amount;
            $totalTax += $tax;
        }

        // Platform Commission
        if (isset($commissionData['platform_commission']) && $commissionData['platform_commission'] > 0) {
            $amount = $commissionData['platform_commission'];
            $tax = $amount * ($taxRate / 100);
            $details[] = $this->createDetailLine('KOMISYON', 'Platform Komisyonu', $amount, $taxRate);
            $totalGross += $amount;
            $totalTax += $tax;
        }

        // POS Commission
        if (isset($commissionData['pos_commission']) && $commissionData['pos_commission'] > 0) {
            $amount = $commissionData['pos_commission'];
            $tax = $amount * ($taxRate / 100);
            $details[] = $this->createDetailLine('POS_KOM', 'POS Komisyonu', $amount, $taxRate);
            $totalGross += $amount;
            $totalTax += $tax;
        }

        // Marketing Fee
        if (isset($commissionData['marketing_fee']) && $commissionData['marketing_fee'] > 0) {
            $amount = $commissionData['marketing_fee'];
            $tax = $amount * ($taxRate / 100);
            $details[] = $this->createDetailLine('PAZARLAMA', 'Pazarlama Bedeli', $amount, $taxRate);
            $totalGross += $amount;
            $totalTax += $tax;
        }

        if (empty($details)) {
            return [
                'success' => false,
                'message' => 'No items to invoice.',
                'data' => null
            ];
        }

        $totalNet = $totalGross;
        $total = $totalNet + $totalTax;

        $extras = $this->integration->extra_params ?? [];

        $invoiceData = [
            'firmId' => $extras['firm_id'] ?? null,
            'invoiceNo' => 'KOM-' . $shop->id . '-' . date('Ymd'),
            'invoiceType' => 3, // Sales invoice
            'note' => $period . ' period commission invoice',
            'dates' => [
                'invoiceDate' => now()->toIso8601String(),
                'dueDate' => now()->addDays(30)->toIso8601String(),
            ],
            'customer' => [
                'customerId' => (string) $shop->id,
                'title' => $shop->name,
                'taxOffice' => $shop->tax_office ?? '',
                'taxNo' => $shop->tax_number ?? '',
                'email' => $shop->user->email ?? '',
                'phone' => $shop->phone ?? '',
                'address' => $shop->address ?? '',
            ],
            'amounts' => [
                'currency' => 'TL',
                'gross' => number_format($totalGross, 2, '.', ''),
                'discount' => '0.00',
                'net' => number_format($totalNet, 2, '.', ''),
                'tax' => number_format($totalTax, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
            ],
            'details' => $details,
        ];

        return $this->createInvoice($invoiceData);
    }

    /**
     * Create detail line for invoice
     */
    private function createDetailLine(string $productId, string $productName, float $amount, int $taxRate): array
    {
        $tax = $amount * ($taxRate / 100);
        $total = $amount + $tax;

        return [
            'productId' => $productId,
            'productName' => $productName,
            'note' => '',
            'barcode' => '',
            'taxRate' => number_format($taxRate, 2, '.', ''),
            'quantity' => 1,
            'unitPrice' => number_format($amount, 2, '.', ''),
            'grossPrice' => number_format($amount, 2, '.', ''),
            'discount' => '0.00',
            'net' => number_format($amount, 2, '.', ''),
            'tax' => number_format($tax, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
        ];
    }

    /**
     * Sync order - create invoice from order
     */
    public function syncOrder($order): array
    {
        try {
            if (is_object($order)) {
                $customer = $order->customer ?? null;
                $address = $order->address ?? null;
                $taxRate = 20;

                $details = [];
                $totalGross = 0;
                $totalTax = 0;

                foreach ($order->products as $product) {
                    $quantity = $product->pivot->quantity ?? 1;
                    $unitPrice = $product->pivot->price ?? 0;
                    $gross = $quantity * $unitPrice;
                    $tax = $gross * ($taxRate / 100);
                    $total = $gross + $tax;

                    $details[] = [
                        'productId' => (string) ($product->id ?? ''),
                        'productName' => $product->name ?? '',
                        'note' => '',
                        'barcode' => $product->barcode ?? '',
                        'taxRate' => number_format($taxRate, 2, '.', ''),
                        'quantity' => $quantity,
                        'unitPrice' => number_format($unitPrice, 2, '.', ''),
                        'grossPrice' => number_format($gross, 2, '.', ''),
                        'discount' => '0.00',
                        'net' => number_format($gross, 2, '.', ''),
                        'tax' => number_format($tax, 2, '.', ''),
                        'total' => number_format($total, 2, '.', ''),
                    ];

                    $totalGross += $gross;
                    $totalTax += $tax;
                }

                $totalNet = $totalGross;
                $total = $totalNet + $totalTax;
                $extras = $this->integration->extra_params ?? [];

                $invoiceData = [
                    'firmId' => $extras['firm_id'] ?? null,
                    'invoiceNo' => ($order->prefix ?? '') . ($order->order_code ?? ''),
                    'invoiceType' => 3,
                    'note' => 'Order #' . ($order->order_code ?? ''),
                    'dates' => [
                        'invoiceDate' => now()->toIso8601String(),
                        'dueDate' => now()->addDays(30)->toIso8601String(),
                    ],
                    'customer' => [
                        'customerId' => (string) ($customer->id ?? 0),
                        'title' => ($customer && isset($customer->user)) ? $customer->user->name : 'Customer',
                        'taxOffice' => $customer->tax_office ?? '',
                        'taxNo' => $customer->tax_number ?? '',
                        'email' => ($customer && isset($customer->user)) ? $customer->user->email : '',
                        'phone' => $address->phone ?? ($customer && isset($customer->user) ? $customer->user->phone : ''),
                        'address' => $address->address ?? '',
                    ],
                    'amounts' => [
                        'currency' => 'TL',
                        'gross' => number_format($totalGross, 2, '.', ''),
                        'discount' => '0.00',
                        'net' => number_format($totalNet, 2, '.', ''),
                        'tax' => number_format($totalTax, 2, '.', ''),
                        'total' => number_format($total, 2, '.', ''),
                    ],
                    'details' => $details,
                ];
            } else {
                $invoiceData = $order;
            }

            return $this->createInvoice($invoiceData);
        } catch (\Throwable $e) {
            Log::error('BizimHesap Sync Order Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Order sync error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function mapToSystemSchema(array $erpProduct): array
    {
        $price = (float)($erpProduct['salePrice'] ?? $erpProduct['unitPrice'] ?? $erpProduct['price'] ?? 0);
        $stock = (int)($erpProduct['quantity'] ?? $erpProduct['stock'] ?? $erpProduct['amount'] ?? 0);

        return [
            'barcode' => $erpProduct['barcode'] ?? $erpProduct['Barcode'] ?? null,
            'name' => $erpProduct['productName'] ?? $erpProduct['ProductName'] ?? $erpProduct['name'] ?? null,
            'stock' => $stock,
            'price' => $price,
            'currency' => $erpProduct['currency'] ?? 'TRY',
            'vat_rate' => (int)($erpProduct['taxRate'] ?? $erpProduct['TaxRate'] ?? 0),
        ];
    }
}
