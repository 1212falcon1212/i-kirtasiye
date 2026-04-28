<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayTRTransferService
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

    /**
     * Transfer payment to a single seller
     */
    public function transferToSeller(
        Order $order,
        User $seller,
        float $amount,
        string $iban,
        string $accountName,
    ): array {
        $transId = $order->order_number . '-' . $seller->id;
        $submerchantAmount = (int) round($amount * 100);

        if ($this->testMode) {
            Log::info('PayTR transfer (test mode)', [
                'order' => $order->order_number,
                'seller' => $seller->id,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'trans_id' => $transId,
                'amount' => $amount,
                'test_mode' => true,
            ];
        }

        try {
            $hashStr = $this->merchantId . $order->order_number . $transId . $submerchantAmount;
            $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $this->merchantSalt, $this->merchantKey, true));

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post('https://www.paytr.com/odeme/platform/transfer', [
                    'merchant_id' => $this->merchantId,
                    'merchant_oid' => $order->order_number,
                    'trans_id' => $transId,
                    'submerchant_amount' => $submerchantAmount,
                    'transfer_iban' => $iban,
                    'transfer_name' => $accountName,
                    'paytr_token' => $paytrToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    Log::info('PayTR transfer successful', [
                        'order' => $order->order_number,
                        'seller' => $seller->id,
                        'trans_id' => $transId,
                        'amount' => $amount,
                    ]);

                    return [
                        'success' => true,
                        'trans_id' => $transId,
                        'amount' => $amount,
                    ];
                }

                $error = $data['err_msg'] ?? $data['reason'] ?? 'Bilinmeyen hata';
                Log::error('PayTR transfer failed', [
                    'error' => $error,
                    'order' => $order->order_number,
                    'seller' => $seller->id,
                ]);

                return [
                    'success' => false,
                    'trans_id' => $transId,
                    'error' => $error,
                ];
            }

            Log::error('PayTR transfer API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'trans_id' => $transId,
                'error' => 'API istegi basarisiz (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('PayTR transfer exception: ' . $e->getMessage());
            return [
                'success' => false,
                'trans_id' => $transId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Distribute order payments to all sellers
     */
    public function distributeOrderPayments(Order $order): array
    {
        $order->loadMissing(['items.seller', 'items.seller.defaultBankAccount']);

        // Group items by seller and calculate payout amounts
        $sellerPayouts = [];
        foreach ($order->items as $item) {
            $sellerId = $item->seller_id;
            if (!isset($sellerPayouts[$sellerId])) {
                $sellerPayouts[$sellerId] = [
                    'seller' => $item->seller,
                    'amount' => 0,
                ];
            }
            $sellerPayouts[$sellerId]['amount'] += $item->seller_payout_amount;
        }

        $results = [];
        foreach ($sellerPayouts as $sellerId => $payout) {
            $seller = $payout['seller'];
            $amount = $payout['amount'];

            if ($amount <= 0) {
                $results[] = [
                    'seller_id' => $sellerId,
                    'success' => false,
                    'error' => 'Transfer tutari 0 veya negatif.',
                ];
                continue;
            }

            // Get seller's default bank account
            $bankAccount = $seller->defaultBankAccount ?? null;
            if (!$bankAccount) {
                $results[] = [
                    'seller_id' => $sellerId,
                    'success' => false,
                    'error' => 'Saticinin banka hesabi bulunamadi.',
                ];
                continue;
            }

            $results[] = $this->transferToSeller(
                order: $order,
                seller: $seller,
                amount: $amount,
                iban: $bankAccount->iban,
                accountName: $bankAccount->account_holder,
            );
            $results[array_key_last($results)]['seller_id'] = $sellerId;
        }

        return $results;
    }

    /**
     * List returned (failed) payments within a date range
     */
    public function listReturnedPayments(string $startDate, string $endDate): array
    {
        if ($this->testMode) {
            return [
                'success' => true,
                'returns' => [],
                'test_mode' => true,
            ];
        }

        try {
            $hashStr = $this->merchantId . $startDate . $endDate;
            $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $this->merchantSalt, $this->merchantKey, true));

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post('https://www.paytr.com/odeme/platform/geri-donen', [
                    'merchant_id' => $this->merchantId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'paytr_token' => $paytrToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    return [
                        'success' => true,
                        'returns' => $data['data'] ?? [],
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['err_msg'] ?? 'Sorgulama basarisiz',
                ];
            }

            return [
                'success' => false,
                'error' => 'API istegi basarisiz (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('PayTR returned payments query error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle transfer callback from PayTR
     */
    public function handleCallback(Request $request): void
    {
        $status = $request->input('status');
        $transId = $request->input('trans_id');
        $merchantOid = $request->input('merchant_oid');

        Log::info('PayTR transfer callback', [
            'status' => $status,
            'trans_id' => $transId,
            'merchant_oid' => $merchantOid,
        ]);

        if ($status === 'error') {
            Log::warning('PayTR transfer returned/failed', [
                'trans_id' => $transId,
                'merchant_oid' => $merchantOid,
                'reason' => $request->input('failed_reason_msg'),
            ]);
        }
    }
}
