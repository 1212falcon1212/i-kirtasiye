<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Interfaces\PaymentInitResult;
use App\Interfaces\PaymentResult;
use App\Interfaces\RefundResult;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IyzicoProvider implements PaymentGatewayInterface
{
    protected string $apiKey;
    protected string $secretKey;
    protected string $baseUrl;
    protected bool $testMode;

    /**
     * HTTP request timeout in seconds
     */
    protected int $timeout = 30;

    public function __construct()
    {
        $this->apiKey = Setting::getValue('payment.iyzico_api_key', '');
        $this->secretKey = Setting::getValue('payment.iyzico_secret_key', '');

        // Get test mode from server config, not client-side bypass
        $this->testMode = (bool) Setting::getValue('payment.iyzico_test_mode', false);

        // Set base URL based on test mode
        $this->baseUrl = $this->testMode
            ? 'https://sandbox-api.iyzipay.com'
            : 'https://api.iyzipay.com';
    }

    public function getName(): string
    {
        return 'iyzico';
    }

    public function initialize(Order $order): PaymentInitResult
    {
        try {
            // Validate credentials
            if (empty($this->apiKey) || empty($this->secretKey)) {
                return PaymentInitResult::failure('Iyzico API bilgileri eksik.');
            }

            // Build checkout form request
            $checkoutHtml = $this->buildCheckoutForm($order);

            if (!$checkoutHtml) {
                return PaymentInitResult::failure('Ödeme formu oluşturulamadı.');
            }

            return PaymentInitResult::success(
                checkoutHtml: $checkoutHtml,
                transactionId: 'IYZ-' . $order->order_number,
            );
        } catch (\Exception $e) {
            Log::error('Iyzico initialize error: ' . $e->getMessage());
            return PaymentInitResult::failure('Ödeme başlatılamadı: ' . $e->getMessage());
        }
    }

    public function getCheckoutHtml(Order $order): string
    {
        return $this->buildCheckoutForm($order);
    }

    protected function buildCheckoutForm(Order $order): string
    {
        $callbackUrl = url('/api/payments/callback/iyzico');

        // Production implementation using Iyzico API
        try {
            $user = $order->user;
            $basketItems = $this->buildBasketItems($order);

            // Generate PKI request data
            $request = [
                'locale' => 'tr',
                'conversationId' => $order->order_number,
                'price' => number_format($order->subtotal_amount, 2, '.', ''),
                'paidPrice' => number_format($order->total_amount, 2, '.', ''),
                'currency' => 'TRY',
                'basketId' => $order->order_number,
                'paymentGroup' => 'PRODUCT',
                'callbackUrl' => $callbackUrl,
                'enabledInstallments' => [1, 2, 3, 6, 9],
                'buyer' => [
                    'id' => (string) $user->id,
                    'name' => $user->name ?? 'Müşteri',
                    'surname' => $user->surname ?? '',
                    'gsmNumber' => $user->phone ?? '',
                    'email' => $user->email,
                    'identityNumber' => $user->identity_number ?? '11111111111',
                    'registrationAddress' => $order->billing_address['address'] ?? $order->shipping_address['address'] ?? 'Adres',
                    'ip' => request()->ip(),
                    'city' => $order->billing_address['city'] ?? 'Istanbul',
                    'country' => 'Turkey',
                ],
                'shippingAddress' => [
                    'contactName' => $order->shipping_address['name'] ?? $user->name,
                    'city' => $order->shipping_address['city'] ?? 'Istanbul',
                    'country' => 'Turkey',
                    'address' => $order->shipping_address['address'] ?? 'Adres',
                ],
                'billingAddress' => [
                    'contactName' => $order->billing_address['name'] ?? $user->name,
                    'city' => $order->billing_address['city'] ?? 'Istanbul',
                    'country' => 'Turkey',
                    'address' => $order->billing_address['address'] ?? 'Adres',
                ],
                'basketItems' => $basketItems,
            ];

            // Make API request to get checkout form
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->buildAuthHeaders($request))
                ->post($this->baseUrl . '/payment/iyzipos/checkoutform/initialize/auth/ecom', $request);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success' && !empty($data['checkoutFormContent'])) {
                    return $data['checkoutFormContent'];
                }

                Log::error('Iyzico checkout form error', [
                    'error_code' => $data['errorCode'] ?? null,
                    'error_message' => $data['errorMessage'] ?? null,
                ]);

                return $this->buildErrorHtml($data['errorMessage'] ?? 'Ödeme formu oluşturulamadı');
            }

            Log::error('Iyzico API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->buildErrorHtml('Ödeme servisi bağlantı hatası');

        } catch (\Exception $e) {
            Log::error('Iyzico buildCheckoutForm exception: ' . $e->getMessage());
            return $this->buildErrorHtml('Ödeme formu oluşturulurken hata oluştu');
        }
    }

    /**
     * Build error HTML for display
     */
    protected function buildErrorHtml(string $message): string
    {
        return <<<HTML
        <div class="iyzico-error p-6 bg-red-50 rounded-lg border-2 border-red-200">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <h3 class="text-lg font-bold text-red-800">Ödeme Hatası</h3>
            </div>
            <p class="text-red-700">{$message}</p>
        </div>
        HTML;
    }

    /**
     * Build basket items for Iyzico API
     */
    protected function buildBasketItems(Order $order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                'id' => (string) $item->id,
                'name' => $item->product->name ?? 'Ürün',
                'category1' => $item->product->category->name ?? 'Genel',
                'itemType' => 'PHYSICAL',
                'price' => number_format($item->total_price, 2, '.', ''),
                'subMerchantKey' => $item->seller->iyzico_submerchant_key ?? null,
                'subMerchantPrice' => $item->seller_payout_amount
                    ? number_format($item->seller_payout_amount, 2, '.', '')
                    : null,
            ];
        }

        return $items;
    }

    /**
     * Build authentication headers for Iyzico API
     */
    protected function buildAuthHeaders(array $request): array
    {
        $randomKey = uniqid('', true);
        $pkiString = $this->generatePkiString($request);
        $authorizationString = $this->apiKey . $randomKey . $this->secretKey . $pkiString;
        $hash = base64_encode(sha1($authorizationString, true));

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'IYZWS ' . $this->apiKey . ':' . $hash,
            'x-iyzi-rnd' => $randomKey,
        ];
    }

    /**
     * Generate PKI string for signature
     */
    protected function generatePkiString(array $data, string $prefix = ''): string
    {
        $pki = '';

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isAssociativeArray($value)) {
                    $pki .= $this->generatePkiString($value, $prefix . $key . '.');
                } else {
                    $pki .= $prefix . $key . '=[';
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $pki .= $this->generatePkiString($item) . ', ';
                        } else {
                            $pki .= $item . ', ';
                        }
                    }
                    $pki = rtrim($pki, ', ') . '],';
                }
            } elseif ($value !== null) {
                $pki .= $prefix . $key . '=' . $value . ',';
            }
        }

        return rtrim($pki, ',');
    }

    /**
     * Check if array is associative
     */
    protected function isAssociativeArray(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function handleCallback(Request $request): PaymentResult
    {
        try {
            $token = $request->input('token');

            if (empty($token)) {
                Log::warning('Iyzico callback: Token missing');
                return PaymentResult::failed('Token eksik', $request->all());
            }

            // Verify callback signature first
            if (!$this->verifyCallbackSignature($request)) {
                Log::warning('Iyzico callback: Invalid signature', [
                    'token' => $token,
                    'ip' => $request->ip(),
                ]);
                return PaymentResult::failed('Geçersiz imza', $request->all());
            }

            // Retrieve payment result from Iyzico
            $checkoutRequest = [
                'locale' => 'tr',
                'conversationId' => $request->input('conversationId', ''),
                'token' => $token,
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders($this->buildAuthHeaders($checkoutRequest))
                ->post($this->baseUrl . '/payment/iyzipos/checkoutform/auth/ecom/detail', $checkoutRequest);

            if (!$response->successful()) {
                Log::error('Iyzico checkout detail API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return PaymentResult::failed('Ödeme doğrulama hatası', $request->all());
            }

            $data = $response->json();

            if ($data['status'] !== 'success') {
                Log::warning('Iyzico payment not successful', [
                    'error_code' => $data['errorCode'] ?? null,
                    'error_message' => $data['errorMessage'] ?? null,
                ]);
                return PaymentResult::failed(
                    $data['errorMessage'] ?? 'Ödeme başarısız',
                    $request->all()
                );
            }

            // Payment successful
            Log::info('Iyzico payment completed', [
                'payment_id' => $data['paymentId'] ?? null,
                'conversation_id' => $data['conversationId'] ?? null,
                'paid_price' => $data['paidPrice'] ?? null,
            ]);

            return PaymentResult::completed(
                transactionId: $data['paymentId'] ?? $token,
                paidAmount: (float) ($data['paidPrice'] ?? 0),
                rawData: $data,
            );

        } catch (\Exception $e) {
            Log::error('Iyzico callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return PaymentResult::failed($e->getMessage());
        }
    }

    /**
     * Verify Iyzico callback signature
     */
    protected function verifyCallbackSignature(Request $request): bool
    {
        // Get signature from header
        $signature = $request->header('X-IYZ-Signature');

        // If no signature header, verify via token existence (legacy callbacks)
        if (!$signature) {
            $token = $request->input('token');
            return !empty($token);
        }

        // Verify HMAC signature
        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $this->secretKey, true));

        return hash_equals($expectedSignature, $signature);
    }

    public function refund(Order $order, float $amount): RefundResult
    {
        try {
            if (empty($order->payment_reference)) {
                return RefundResult::failure('Ödeme referansı bulunamadı.');
            }

            $refundRequest = [
                'locale' => 'tr',
                'conversationId' => $order->order_number,
                'paymentTransactionId' => $order->payment_reference,
                'price' => number_format($amount, 2, '.', ''),
                'currency' => 'TRY',
                'ip' => request()->ip(),
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders($this->buildAuthHeaders($refundRequest))
                ->post($this->baseUrl . '/payment/refund', $refundRequest);

            if (!$response->successful()) {
                Log::error('Iyzico refund API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return RefundResult::failure('İade API hatası');
            }

            $data = $response->json();

            if ($data['status'] !== 'success') {
                Log::warning('Iyzico refund not successful', [
                    'error_code' => $data['errorCode'] ?? null,
                    'error_message' => $data['errorMessage'] ?? null,
                ]);
                return RefundResult::failure($data['errorMessage'] ?? 'İade başarısız');
            }

            Log::info('Iyzico refund completed', [
                'payment_id' => $data['paymentId'] ?? null,
                'refund_amount' => $amount,
            ]);

            return RefundResult::success(
                refundId: $data['paymentTransactionId'] ?? uniqid('REFUND-'),
                refundedAmount: $amount,
            );

        } catch (\Exception $e) {
            Log::error('Iyzico refund error: ' . $e->getMessage());
            return RefundResult::failure($e->getMessage());
        }
    }

    /**
     * Build sub-merchant payout items for marketplace model
     * Uses order_items.seller_payout_amount from Phase 3
     */
    protected function buildSubMerchantPayouts(Order $order): array
    {
        $payouts = [];

        foreach ($order->items as $item) {
            if (!empty($item->seller->iyzico_submerchant_key)) {
                $payouts[] = [
                    'sub_merchant_key' => $item->seller->iyzico_submerchant_key,
                    'amount' => $item->seller_payout_amount,
                    'commission' => $item->commission_amount,
                ];
            }
        }

        return $payouts;
    }
}
