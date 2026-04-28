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

class PayTRProvider implements PaymentGatewayInterface
{
    protected string $merchantId;
    protected string $merchantKey;
    protected string $merchantSalt;
    protected bool $testMode;

    protected int $timeout = 30;

    public function __construct()
    {
        $this->merchantId = Setting::getValue('payment.paytr_merchant_id', '');
        $this->merchantKey = Setting::getValue('payment.paytr_merchant_key', '');
        $this->merchantSalt = Setting::getValue('payment.paytr_merchant_salt', '');
        $this->testMode = Setting::getValue('payment.test_mode', true);
    }

    public function getName(): string
    {
        return 'paytr';
    }

    /**
     * Initialize payment - signals frontend to render card form (Direct API mode).
     * No iframe/HTML is generated; frontend collects card data and calls processPayment.
     */
    public function initialize(Order $order): PaymentInitResult
    {
        try {
            if (empty($this->merchantId) || empty($this->merchantKey) || empty($this->merchantSalt)) {
                return PaymentInitResult::failure('PayTR API bilgileri eksik. Admin panelinden ayarlari kontrol edin.');
            }

            return PaymentInitResult::success(
                paymentUrl: null,
                checkoutHtml: null,
                transactionId: 'PTR-' . $order->order_number,
            );
        } catch (\Exception $e) {
            Log::error('PayTR initialize error: ' . $e->getMessage());
            return PaymentInitResult::failure('Odeme baslatilamadi: ' . $e->getMessage());
        }
    }

    /**
     * Kept for interface compatibility. Returns empty string in Direct API mode.
     */
    public function getCheckoutHtml(Order $order): string
    {
        return '';
    }

    /**
     * Process a payment via PayTR Direct API (non-iframe).
     *
     * @param Order $order   The order to pay for
     * @param array $cardData Keys: card_number, expiry_month, expiry_year, cvv, cc_owner,
     *                        installment_count, store_card, utoken, ctoken
     * @return array ['status' => '3d_redirect'|'failed', 'html' => '...', 'error' => '...']
     */
    public function processPayment(Order $order, array $cardData): array
    {
        try {
            if (empty($this->merchantId) || empty($this->merchantKey) || empty($this->merchantSalt)) {
                return ['success' => false, 'status' => 'failed', 'error' => 'PayTR API bilgileri eksik.'];
            }

            $user = $order->user;
            $basketItems = $this->buildBasketItems($order);
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $paymentAmount = $this->amountToKurus($order->total_amount);
            $installmentCount = (int) ($cardData['installment_count'] ?? 0);
            $noInstallment = $installmentCount === 0 ? 1 : 0;
            $currency = 'TL';
            $testModeVal = $this->testMode ? 1 : 0;
            $non3d = 0;
            $paymentType = 'card';

            // Build hash string (PayTR Direct API requires no_installment in hash)
            $hashStr = $this->merchantId
                . request()->ip()
                . $order->order_number
                . ($user->email ?? '')
                . $paymentAmount
                . $paymentType
                . $installmentCount
                . $currency
                . $testModeVal
                . $non3d
                . $this->merchantSalt;

            $paytrToken = base64_encode(hash_hmac('sha256', $hashStr, $this->merchantKey, true));

            // PayTR Direct API parameters (ref: dev.paytr.com/direkt-api/direkt-api-1-adim)
            $postData = [
                'merchant_id' => $this->merchantId,
                'paytr_token' => $paytrToken,
                'user_ip' => request()->ip(),
                'merchant_oid' => $order->order_number,
                'email' => $user->email ?? '',
                'payment_type' => $paymentType,
                'payment_amount' => $paymentAmount,
                'currency' => $currency,
                'test_mode' => $testModeVal,
                'non_3d' => $non3d,
                'installment_count' => $installmentCount,
                'client_lang' => 'tr',
                'merchant_ok_url' => $frontendUrl . '/market/odeme/sonuc?status=success&order=' . $order->order_number,
                'merchant_fail_url' => $frontendUrl . '/market/odeme/sonuc?status=failed&order=' . $order->order_number,
                'user_name' => $order->shipping_address['name'] ?? $user->business_name ?? '',
                'user_address' => $order->shipping_address['address'] ?? '',
                'user_phone' => $this->formatPhone($order->shipping_address['phone'] ?? $user->phone ?? ''),
                'user_basket' => base64_encode(json_encode($basketItems)),
                'debug_on' => 1,
            ];

            // Determine if this is a saved card payment or new card payment
            $utoken = $cardData['utoken'] ?? null;
            $ctoken = $cardData['ctoken'] ?? null;

            if ($utoken && $ctoken) {
                // Saved card payment
                $postData['utoken'] = $utoken;
                $postData['ctoken'] = $ctoken;

                // Some saved cards require CVV re-entry
                if (!empty($cardData['cvv'])) {
                    $postData['cvv'] = $cardData['cvv'];
                }
            } else {
                // New card payment
                $postData['cc_owner'] = $cardData['cc_owner'] ?? '';
                $postData['card_number'] = $cardData['card_number'] ?? '';
                $postData['expiry_month'] = $cardData['expiry_month'] ?? '';
                $postData['expiry_year'] = $cardData['expiry_year'] ?? '';
                $postData['cvv'] = $cardData['cvv'] ?? '';

                // Store card for future use
                $storeCard = (int) ($cardData['store_card'] ?? 0);
                if ($storeCard === 1) {
                    $postData['store_card'] = 1;

                    // If user already has a utoken, include it so the card is added to existing wallet
                    $existingUtoken = $cardData['utoken'] ?? $user->paytr_utoken ?? null;
                    if ($existingUtoken) {
                        $postData['utoken'] = $existingUtoken;
                    }
                }
            }

            Log::info('PayTR processPayment request', [
                'order' => $order->order_number,
                'payment_amount' => $paymentAmount,
                'installment_count' => $installmentCount,
                'is_saved_card' => !empty($ctoken),
                'store_card' => $postData['store_card'] ?? 0,
            ]);

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post('https://www.paytr.com/odeme', $postData);

            if ($response->successful()) {
                $body = $response->body();

                // PayTR returns HTML for 3D Secure redirect on success
                // If response contains a form/script for 3D, it's a success
                if (str_contains($body, '<form') || str_contains($body, '<html') || str_contains($body, '<script')) {
                    Log::info('PayTR processPayment 3D redirect received', [
                        'order' => $order->order_number,
                    ]);

                    return [
                        'success' => true,
                        'status' => '3d_redirect',
                        'html' => $body,
                    ];
                }

                // Try to parse as JSON error response
                $data = json_decode($body, true);
                if ($data !== null) {
                    $errorMsg = $data['reason'] ?? $data['err_msg'] ?? 'Bilinmeyen hata';
                    Log::error('PayTR processPayment failed', [
                        'order' => $order->order_number,
                        'error' => $errorMsg,
                        'response' => $data,
                    ]);

                    return ['success' => false, 'status' => 'failed', 'error' => $errorMsg];
                }

                // Unknown response format
                Log::error('PayTR processPayment unexpected response', [
                    'order' => $order->order_number,
                    'body' => mb_substr($body, 0, 500),
                ]);

                return ['success' => false, 'status' => 'failed', 'error' => 'Beklenmeyen PayTR yaniti.'];
            }

            Log::error('PayTR processPayment HTTP error', [
                'order' => $order->order_number,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return ['success' => false, 'status' => 'failed', 'error' => 'PayTR API istegi basarisiz (HTTP ' . $response->status() . ').'];
        } catch (\Exception $e) {
            Log::error('PayTR processPayment exception', [
                'order' => $order->order_number,
                'exception' => $e->getMessage(),
            ]);

            return ['success' => false, 'status' => 'failed', 'error' => 'Odeme islemi sirasinda hata: ' . $e->getMessage()];
        }
    }

    /**
     * Query BIN details from PayTR.
     *
     * @param string $binNumber First 6-8 digits of the card
     * @return array Keys: cardType, bank, brand, schema, businessCard, allow_non3d (on success)
     *               or: success => false, error => string (on failure)
     */
    public function queryBin(string $binNumber): array
    {
        try {
            $hashStr = $binNumber . $this->merchantId . $this->merchantSalt;
            $paytrToken = base64_encode(hash_hmac('sha256', $hashStr, $this->merchantKey, true));

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post('https://www.paytr.com/odeme/api/bin-detail', [
                    'merchant_id' => $this->merchantId,
                    'bin_number' => $binNumber,
                    'paytr_token' => $paytrToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    return [
                        'success' => true,
                        'cardType' => $data['cardType'] ?? '',
                        'bank' => $data['bank'] ?? '',
                        'brand' => $data['brand'] ?? '',
                        'schema' => $data['schema'] ?? '',
                        'businessCard' => $data['businessCard'] ?? '',
                        'allow_non3d' => $data['allow_non3d'] ?? false,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['err_msg'] ?? $data['reason'] ?? 'BIN sorgulanamadi',
                ];
            }

            return [
                'success' => false,
                'error' => 'PayTR BIN API istegi basarisiz (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('PayTR queryBin exception', [
                'bin' => $binNumber,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get installment rates from PayTR.
     *
     * @return array Installment rates by card brand, or error array
     */
    public function getInstallmentRates(): array
    {
        try {
            $requestId = uniqid('paytr_', true);

            $hashStr = $this->merchantId . $requestId . $this->merchantSalt;
            $paytrToken = base64_encode(hash_hmac('sha256', $hashStr, $this->merchantKey, true));

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post('https://www.paytr.com/odeme/taksit-oranlari', [
                    'merchant_id' => $this->merchantId,
                    'request_id' => $requestId,
                    'paytr_token' => $paytrToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    return [
                        'success' => true,
                        'rates' => $data['rates'] ?? $data,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['err_msg'] ?? $data['reason'] ?? 'Taksit oranlari alinamadi',
                ];
            }

            return [
                'success' => false,
                'error' => 'PayTR taksit oranlari API istegi basarisiz (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('PayTR getInstallmentRates exception', ['exception' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List saved cards for a user via PayTR Card Storage API.
     *
     * @param string $utoken User's unique token from PayTR
     * @return array List of saved cards or error
     */
    public function listSavedCards(string $utoken): array
    {
        try {
            $hashStr = $utoken . $this->merchantSalt;
            $paytrToken = base64_encode(hash_hmac('sha256', $hashStr, $this->merchantKey, true));

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post('https://www.paytr.com/odeme/capi/list', [
                    'merchant_id' => $this->merchantId,
                    'utoken' => $utoken,
                    'paytr_token' => $paytrToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    $cards = [];
                    foreach ($data['cards'] ?? [] as $card) {
                        $cards[] = [
                            'ctoken' => $card['ctoken'] ?? '',
                            'last_4' => $card['last_4'] ?? '',
                            'require_cvv' => $card['require_cvv'] ?? false,
                            'month' => $card['month'] ?? '',
                            'year' => $card['year'] ?? '',
                            'c_bank' => $card['c_bank'] ?? '',
                            'c_name' => $card['c_name'] ?? '',
                            'c_brand' => $card['c_brand'] ?? '',
                            'c_type' => $card['c_type'] ?? '',
                            'schema' => $card['schema'] ?? '',
                            'businessCard' => $card['businessCard'] ?? '',
                        ];
                    }

                    return [
                        'success' => true,
                        'cards' => $cards,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['err_msg'] ?? $data['reason'] ?? 'Kartlar listelenemedi',
                    'cards' => [],
                ];
            }

            return [
                'success' => false,
                'error' => 'PayTR kart listeleme API istegi basarisiz (HTTP ' . $response->status() . ')',
                'cards' => [],
            ];
        } catch (\Exception $e) {
            Log::error('PayTR listSavedCards exception', ['exception' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'cards' => [],
            ];
        }
    }

    /**
     * Delete a saved card from PayTR Card Storage.
     *
     * @param string $utoken User's unique token
     * @param string $ctoken Card token to delete
     * @return bool True if deletion was successful
     */
    public function deleteSavedCard(string $utoken, string $ctoken): bool
    {
        try {
            $hashStr = $ctoken . $utoken . $this->merchantSalt;
            $paytrToken = base64_encode(hash_hmac('sha256', $hashStr, $this->merchantKey, true));

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post('https://www.paytr.com/odeme/capi/delete', [
                    'merchant_id' => $this->merchantId,
                    'utoken' => $utoken,
                    'ctoken' => $ctoken,
                    'paytr_token' => $paytrToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    Log::info('PayTR saved card deleted', [
                        'ctoken' => $ctoken,
                    ]);
                    return true;
                }

                Log::error('PayTR deleteSavedCard failed', [
                    'error' => $data['err_msg'] ?? $data['reason'] ?? 'Unknown',
                    'ctoken' => $ctoken,
                ]);
            } else {
                Log::error('PayTR deleteSavedCard HTTP error', [
                    'status' => $response->status(),
                    'ctoken' => $ctoken,
                ]);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('PayTR deleteSavedCard exception', [
                'ctoken' => $ctoken,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function handleCallback(Request $request): PaymentResult
    {
        try {
            // In test mode, still process utoken but skip hash validation
            $isTestMode = $request->input('test_mode') === '1';

            // Validate PayTR callback
            $hash = $request->input('hash');
            $merchantOid = $request->input('merchant_oid');
            $status = $request->input('status');
            $totalAmount = $request->input('total_amount');

            // Verify hash
            $expectedHash = base64_encode(hash_hmac(
                'sha256',
                $merchantOid . $this->merchantSalt . $status . $totalAmount,
                $this->merchantKey,
                true
            ));

            if (!$isTestMode && $hash !== $expectedHash) {
                return PaymentResult::failed('Hash dogrulama hatasi', $request->all());
            }

            if ($status === 'success') {
                // Store utoken if present (for saved card feature)
                $utoken = $request->input('utoken');
                if ($utoken) {
                    $order = Order::where('order_number', $merchantOid)->first();
                    if ($order && $order->user) {
                        $order->user->update(['paytr_utoken' => $utoken]);

                        Log::info('PayTR utoken stored for user', [
                            'user_id' => $order->user->id,
                            'order' => $merchantOid,
                        ]);
                    }
                }

                return PaymentResult::completed(
                    transactionId: $merchantOid,
                    paidAmount: $totalAmount / 100,
                    rawData: $request->all(),
                );
            }

            $failReason = $request->input('failed_reason_msg', 'Odeme basarisiz');
            return PaymentResult::failed($failReason, $request->all());
        } catch (\Exception $e) {
            Log::error('PayTR callback error: ' . $e->getMessage());
            return PaymentResult::failed($e->getMessage());
        }
    }

    public function refund(Order $order, float $amount): RefundResult
    {
        try {
            if ($this->testMode) {
                return RefundResult::success(
                    refundId: 'REFUND-TEST-' . uniqid(),
                    refundedAmount: $amount,
                );
            }

            if (empty($this->merchantId) || empty($this->merchantKey) || empty($this->merchantSalt)) {
                return RefundResult::failure('PayTR API bilgileri eksik.');
            }

            $returnAmount = $this->amountToKurus($amount);

            $hashStr = $this->merchantId . $order->order_number . $returnAmount;
            $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $this->merchantSalt, $this->merchantKey, true));

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post('https://www.paytr.com/odeme/iade', [
                    'merchant_id' => $this->merchantId,
                    'merchant_oid' => $order->order_number,
                    'return_amount' => $returnAmount,
                    'paytr_token' => $paytrToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    Log::info('PayTR refund successful', [
                        'order' => $order->order_number,
                        'amount' => $amount,
                    ]);

                    return RefundResult::success(
                        refundId: 'REFUND-' . $order->order_number . '-' . time(),
                        refundedAmount: $amount,
                    );
                }

                $reason = $data['err_msg'] ?? $data['reason'] ?? 'Bilinmeyen hata';
                Log::error('PayTR refund failed', ['reason' => $reason, 'order' => $order->order_number]);
                return RefundResult::failure('Iade basarisiz: ' . $reason);
            }

            Log::error('PayTR refund API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return RefundResult::failure('PayTR iade API istegi basarisiz.');
        } catch (\Exception $e) {
            Log::error('PayTR refund error: ' . $e->getMessage());
            return RefundResult::failure($e->getMessage());
        }
    }

    /**
     * Query payment status from PayTR
     */
    public function queryStatus(Order $order): array
    {
        if ($this->testMode) {
            return [
                'success' => true,
                'payment_amount' => (string) $this->amountToKurus($order->total_amount),
                'payment_total' => (string) $this->amountToKurus($order->total_amount),
                'payment_date' => $order->updated_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
                'currency' => 'TL',
                'taksit' => '0',
                'kart_marka' => 'Test',
                'masked_pan' => '4111****1111',
                'net_tutar' => (string) $this->amountToKurus($order->total_amount),
                'returns' => [],
            ];
        }

        try {
            $hashStr = $this->merchantId . $order->order_number;
            $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $this->merchantSalt, $this->merchantKey, true));

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post('https://www.paytr.com/odeme/durum-sorgu', [
                    'merchant_id' => $this->merchantId,
                    'merchant_oid' => $order->order_number,
                    'paytr_token' => $paytrToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    return [
                        'success' => true,
                        'payment_amount' => $data['payment_amount'] ?? '',
                        'payment_total' => $data['payment_total'] ?? '',
                        'payment_date' => $data['payment_date'] ?? '',
                        'currency' => $data['currency'] ?? 'TL',
                        'taksit' => $data['taksit'] ?? '0',
                        'kart_marka' => $data['kart_marka'] ?? '',
                        'masked_pan' => $data['masked_pan'] ?? '',
                        'net_tutar' => $data['net_tutar'] ?? '',
                        'returns' => $data['returns'] ?? [],
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['err_msg'] ?? 'Durum sorgulanamadi',
                ];
            }

            return [
                'success' => false,
                'error' => 'PayTR API istegi basarisiz (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('PayTR status query error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone for PayTR: 10 digits without leading 0 or +90
     * e.g. "5205000000" or fallback "5000000000"
     */
    protected function formatPhone(string $phone): string
    {
        // Strip everything except digits
        $digits = preg_replace('/\D/', '', $phone);

        // Remove leading 90 (country code)
        if (str_starts_with($digits, '90') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        // Remove leading 0
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        // Must be 10 digits starting with 5
        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return $digits;
        }

        // Fallback - PayTR requires a valid phone, use placeholder
        return '5000000000';
    }

    protected function buildBasketItems(Order $order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                $item->product->name ?? 'Urun',
                number_format($item->unit_price, 2, '.', ''),
                $item->quantity,
            ];
        }

        return $items;
    }

    /**
     * Convert TL amount to kurus (cents)
     */
    protected function amountToKurus(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
