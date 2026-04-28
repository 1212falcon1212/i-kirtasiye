<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\OrderPaid;
use App\Events\PaymentFailed;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PayTRProvider;
use App\Services\Payments\PayTRTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Get payment configuration for frontend
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'enabled' => $this->paymentManager->isEnabled(),
            'gateway' => $this->paymentManager->getActiveGateway(),
            'test_mode' => $this->paymentManager->isTestMode(),
        ]);
    }

    /**
     * Initialize payment for an order
     */
    public function initialize(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::with(['items.product', 'items.seller', 'user'])->find($request->order_id);

        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        if ($order->payment_status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Bu sipariş için ödeme beklenmiyor.',
            ], 400);
        }

        $provider = $this->paymentManager->getProvider();

        if (! $provider) {
            return response()->json([
                'success' => false,
                'error' => 'Ödeme sistemi aktif değil.',
            ], 400);
        }

        $result = $provider->initialize($order);

        if (! $result->success) {
            return response()->json([
                'success' => false,
                'error' => $result->error,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'payment_url' => $result->paymentUrl,
            'checkout_html' => $result->checkoutHtml,
            'transaction_id' => $result->transactionId,
            'gateway' => $provider->getName(),
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
            ],
        ]);
    }

    /**
     * Get checkout form HTML for an order
     */
    public function checkout(Order $order, Request $request): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        $provider = $this->paymentManager->getProvider();

        if (! $provider) {
            return response()->json([
                'success' => false,
                'error' => 'Ödeme sistemi aktif değil.',
            ], 400);
        }

        $html = $provider->getCheckoutHtml($order);

        return response()->json([
            'success' => true,
            'checkout_html' => $html,
            'gateway' => $provider->getName(),
        ]);
    }

    /**
     * Handle payment gateway callback
     * PayTR sends POST to this URL and expects plain text "OK" response.
     */
    public function callback(string $gateway, Request $request): Response
    {
        Log::info("Payment callback received for gateway: {$gateway}", $request->all());

        try {
            $provider = $this->paymentManager->getProviderByName($gateway);
            $result = $provider->handleCallback($request);

            // Find order by merchant_oid (order_number) or order_id
            $orderId = $request->input('merchant_oid') ?? $request->input('order_id');
            $order = null;

            if ($orderId) {
                if (is_numeric($orderId)) {
                    $order = Order::find($orderId);
                } else {
                    $order = Order::where('order_number', $orderId)->first();
                }
            }

            if (! $order) {
                Log::error('Order not found for callback', ['order_id' => $orderId]);

                return response('OK', 200)->header('Content-Type', 'text/plain');
            }

            // Idempotent: skip if already paid
            if ($order->payment_status === 'paid') {
                Log::info("Order already paid, skipping update: {$order->order_number}");

                return response('OK', 200)->header('Content-Type', 'text/plain');
            }

            if ($result->success && $result->status === 'completed') {
                $order->update([
                    'payment_status' => 'paid',
                ]);

                // Load relations for notifications
                $order->load('items.product', 'items.seller', 'subOrders', 'user');

                // NOW notify buyer + sellers
                app(\App\Services\NotificationService::class)->notifyOrderCreated($order);

                // Odeme basarili event'i tetikle
                event(new OrderPaid($order));

                Log::info("Payment successful for order: {$order->order_number}");
            } else {
                // Only mark failed if not already in a final state
                if (! in_array($order->payment_status, ['paid', 'refunded'])) {
                    $order->update([
                        'payment_status' => 'failed',
                    ]);
                }

                // Odeme basarisiz event'i tetikle
                event(new PaymentFailed($order, $result->error ?? 'Odeme islemi basarisiz'));

                Log::warning("Payment failed for order: {$order->order_number}", [
                    'error' => $result->error,
                ]);
            }

            // PayTR requires plain text "OK" response
            return response('OK', 200)->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            Log::error('Payment callback error: '.$e->getMessage());

            // Still return OK to prevent PayTR from retrying endlessly
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }
    }

    /**
     * Query payment status from gateway
     */
    public function statusQuery(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::find($request->order_id);

        // Verify: owner or admin
        if ($order->user_id !== $request->user()->id && ! $request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        $provider = $this->paymentManager->getProvider();

        if (! $provider || ! ($provider instanceof PayTRProvider)) {
            return response()->json([
                'success' => false,
                'error' => 'Ödeme durumu sorgulanamadı.',
            ], 400);
        }

        $statusData = $provider->queryStatus($order);

        return response()->json($statusData);
    }

    /**
     * Process refund for an order
     */
    public function refund(Order $order, Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0',
        ]);

        if ($order->user_id !== $request->user()->id && ! $request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparişe erişim yetkiniz yok.',
            ], 403);
        }

        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'Bu sipariş için iade yapılamaz.',
            ], 400);
        }

        $provider = $this->paymentManager->getProvider();

        if (! $provider) {
            return response()->json([
                'success' => false,
                'error' => 'Ödeme sistemi aktif değil.',
            ], 400);
        }

        $amount = $request->amount ?? $order->total_amount;
        $result = $provider->refund($order, $amount);

        if (! $result->success) {
            return response()->json([
                'success' => false,
                'error' => $result->error,
            ], 400);
        }

        // Full refund → mark as refunded, partial → keep as paid
        if ($amount >= $order->total_amount) {
            $order->update([
                'payment_status' => 'refunded',
                'status' => 'cancelled',
            ]);
        }

        return response()->json([
            'success' => true,
            'refund_id' => $result->refundId,
            'refunded_amount' => $result->refundedAmount,
        ]);
    }

    /**
     * Transfer payments to sellers for an order (admin only)
     */
    public function transferToSellers(Order $order, Request $request): JsonResponse
    {
        if (! $request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'Yetkiniz yok.',
            ], 403);
        }

        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'Sipariş ödemesi tamamlanmamış.',
            ], 400);
        }

        $transferService = app(PayTRTransferService::class);
        $results = $transferService->distributeOrderPayments($order);

        return response()->json([
            'success' => true,
            'transfers' => $results,
        ]);
    }

    /**
     * List returned (failed) transfers
     */
    public function returnedPayments(Request $request): JsonResponse
    {
        if (! $request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'error' => 'Yetkiniz yok.',
            ], 403);
        }

        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $transferService = app(PayTRTransferService::class);
        $result = $transferService->listReturnedPayments(
            $request->start_date,
            $request->end_date,
        );

        return response()->json($result);
    }

    /**
     * Handle transfer callback from PayTR
     */
    public function transferCallback(Request $request): Response
    {
        Log::info('PayTR transfer callback received', $request->all());

        // PayTR transfer callback processing
        $transferService = app(PayTRTransferService::class);
        $transferService->handleCallback($request);

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Kart bilgileriyle dogrudan odeme islemi baslatir (3D Secure yonlendirmesi)
     */
    public function processDirectPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'card_number' => 'required_without:ctoken|string',
            'expiry_month' => 'required_without:ctoken|string',
            'expiry_year' => 'required_without:ctoken|string',
            'cvv' => 'required|string',
            'cc_owner' => 'required_without:ctoken|string',
            'installment_count' => 'nullable|integer|min:0',
            'store_card' => 'nullable|boolean',
            'ctoken' => 'nullable|string',
        ]);

        $order = Order::with(['items.product', 'items.seller', 'user'])->find($request->order_id);

        if ($request->user()->id !== $order->user_id) {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparise erisim yetkiniz yok.',
            ], 403);
        }

        if ($order->payment_status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Bu siparis icin odeme beklenmiyor.',
            ], 400);
        }

        $provider = $this->paymentManager->getProvider();

        if (! $provider || ! ($provider instanceof PayTRProvider)) {
            return response()->json([
                'success' => false,
                'error' => 'Odeme sistemi aktif degil.',
            ], 400);
        }

        $cardData = [
            'card_number' => $request->input('card_number'),
            'expiry_month' => $request->input('expiry_month'),
            'expiry_year' => $request->input('expiry_year'),
            'cvv' => $request->input('cvv'),
            'cc_owner' => $request->input('cc_owner'),
            'installment_count' => $request->input('installment_count', 0),
            'store_card' => $request->boolean('store_card', false),
            'ctoken' => $request->input('ctoken'),
            'utoken' => $request->user()->paytr_utoken,
        ];

        try {
            $result = $provider->processPayment($order, $cardData);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Odeme islenemedi.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'status' => $result['status'] ?? 'redirect',
                'html' => $result['html'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Direct payment error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Odeme islemi sirasinda bir hata olustu.',
            ], 500);
        }
    }

    /**
     * Kart BIN numarasi sorgular (kart markasi, tipi vb.)
     */
    public function binQuery(Request $request): JsonResponse
    {
        $request->validate([
            'bin_number' => 'required|string|min:6|max:8',
        ]);

        $provider = $this->paymentManager->getProvider();

        if (! $provider || ! ($provider instanceof PayTRProvider)) {
            return response()->json([
                'success' => false,
                'error' => 'Odeme sistemi aktif degil.',
            ], 400);
        }

        try {
            $result = $provider->queryBin($request->input('bin_number'));

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('BIN query error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'BIN sorgulama sirasinda bir hata olustu.',
            ], 500);
        }
    }

    /**
     * Taksit oranlarini getirir (1 saat cache'lenir)
     */
    public function installmentRates(): JsonResponse
    {
        $provider = $this->paymentManager->getProvider();

        if (! $provider || ! ($provider instanceof PayTRProvider)) {
            return response()->json([
                'success' => false,
                'error' => 'Odeme sistemi aktif degil.',
            ], 400);
        }

        try {
            $rates = Cache::remember('paytr_installment_rates', 3600, function () use ($provider) {
                return $provider->getInstallmentRates();
            });

            return response()->json([
                'success' => true,
                'data' => $rates,
            ]);
        } catch (\Exception $e) {
            Log::error('Installment rates error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Taksit oranlari alinamadi.',
            ], 500);
        }
    }

    /**
     * Kullanicinin kayitli kartlarini listeler
     */
    public function savedCards(Request $request): JsonResponse
    {
        $utoken = $request->user()->paytr_utoken;

        if (empty($utoken)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $provider = $this->paymentManager->getProvider();

        if (! $provider || ! ($provider instanceof PayTRProvider)) {
            return response()->json([
                'success' => false,
                'error' => 'Odeme sistemi aktif degil.',
            ], 400);
        }

        try {
            $cards = $provider->listSavedCards($utoken);

            return response()->json([
                'success' => true,
                'data' => $cards,
            ]);
        } catch (\Exception $e) {
            Log::error('Saved cards list error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Kayitli kartlar alinamadi.',
            ], 500);
        }
    }

    /**
     * Kayitli karti siler
     */
    public function deleteSavedCard(string $ctoken, Request $request): JsonResponse
    {
        $utoken = $request->user()->paytr_utoken;

        if (empty($utoken)) {
            return response()->json([
                'success' => false,
                'error' => 'Kayitli kart bulunamadi.',
            ], 400);
        }

        $provider = $this->paymentManager->getProvider();

        if (! $provider || ! ($provider instanceof PayTRProvider)) {
            return response()->json([
                'success' => false,
                'error' => 'Odeme sistemi aktif degil.',
            ], 400);
        }

        try {
            $deleted = $provider->deleteSavedCard($utoken, $ctoken);

            if (! $deleted) {
                return response()->json([
                    'success' => false,
                    'error' => 'Kart silinemedi.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Kart basariyla silindi.',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete saved card error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Kart silinirken bir hata olustu.',
            ], 500);
        }
    }
}
