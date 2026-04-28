<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSingleProductJob;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhooks from various providers
     */
    public function handle(string $provider, Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $webhookId = uniqid('webhook_', true);

        // Log incoming webhook with full details
        Log::channel('webhooks')->info("Webhook received", [
            'webhook_id' => $webhookId,
            'provider' => $provider,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->userAgent(),
            'payload_size' => strlen($request->getContent()),
        ]);

        try {
            // Route to appropriate handler based on provider
            $result = match ($provider) {
                'iyzico' => $this->handleIyzicoWebhook($request, $webhookId),
                'paytr' => $this->handlePaytrWebhook($request, $webhookId),
                'entegra', 'bizimhesap', 'sentos', 'parasut' => $this->handleErpWebhook($provider, $request, $webhookId),
                default => $this->handleGenericWebhook($provider, $request, $webhookId),
            };

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('webhooks')->info("Webhook processed successfully", [
                'webhook_id' => $webhookId,
                'provider' => $provider,
                'duration_ms' => $duration,
                'result' => $result['status'] ?? 'processed',
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('webhooks')->error("Webhook processing failed", [
                'webhook_id' => $webhookId,
                'provider' => $provider,
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed',
                'webhook_id' => $webhookId,
            ], 500);
        }
    }

    /**
     * Handle Iyzico payment webhooks
     */
    protected function handleIyzicoWebhook(Request $request, string $webhookId): array
    {
        $eventType = $request->header('X-IYZ-Event-Type');
        $token = $request->input('token');
        $conversationId = $request->input('conversationId');
        $paymentId = $request->input('paymentId');

        Log::channel('webhooks')->info("Iyzico webhook details", [
            'webhook_id' => $webhookId,
            'event_type' => $eventType,
            'token' => $token,
            'conversation_id' => $conversationId,
            'payment_id' => $paymentId,
        ]);

        // Handle based on event type
        switch ($eventType) {
            case 'PAYMENT':
            case 'CHECKOUT_FORM_AUTH':
                return $this->processIyzicoPayment($request, $webhookId);

            case 'REFUND':
                return $this->processIyzicoRefund($request, $webhookId);

            case 'BKM_AUTH':
            case 'THREE_DS_AUTH':
                return $this->processIyzico3DSAuth($request, $webhookId);

            default:
                Log::channel('webhooks')->warning("Unknown Iyzico event type", [
                    'webhook_id' => $webhookId,
                    'event_type' => $eventType,
                ]);
                return ['status' => 'received', 'event_type' => $eventType];
        }
    }

    /**
     * Process Iyzico payment notification
     */
    protected function processIyzicoPayment(Request $request, string $webhookId): array
    {
        $conversationId = $request->input('conversationId');
        $paymentId = $request->input('paymentId');
        $status = $request->input('status');
        $paidPrice = $request->input('paidPrice');

        // Find order by conversation ID (order number)
        $order = Order::where('order_number', $conversationId)->first();

        if (!$order) {
            Log::channel('webhooks')->warning("Order not found for Iyzico payment", [
                'webhook_id' => $webhookId,
                'conversation_id' => $conversationId,
            ]);
            return ['status' => 'error', 'message' => 'Order not found'];
        }

        // Update order status based on payment status
        if ($status === 'success' || $status === 'SUCCESS') {
            DB::transaction(function () use ($order, $paymentId, $paidPrice) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'payment_reference' => $paymentId,
                    'paid_amount' => $paidPrice,
                    'paid_at' => now(),
                ]);
            });

            Log::channel('webhooks')->info("Iyzico payment completed", [
                'webhook_id' => $webhookId,
                'order_id' => $order->id,
                'payment_id' => $paymentId,
                'paid_price' => $paidPrice,
            ]);

            return ['status' => 'processed', 'order_id' => $order->id];
        }

        // Payment failed
        $order->update([
            'payment_status' => 'failed',
            'payment_reference' => $paymentId,
        ]);

        return ['status' => 'processed', 'order_id' => $order->id, 'payment_status' => 'failed'];
    }

    /**
     * Process Iyzico refund notification
     */
    protected function processIyzicoRefund(Request $request, string $webhookId): array
    {
        $paymentId = $request->input('paymentId');
        $refundPrice = $request->input('refundPrice');

        Log::channel('webhooks')->info("Iyzico refund received", [
            'webhook_id' => $webhookId,
            'payment_id' => $paymentId,
            'refund_price' => $refundPrice,
        ]);

        // Find order by payment reference
        $order = Order::where('payment_reference', $paymentId)->first();

        if ($order) {
            $order->update([
                'refund_amount' => $refundPrice,
                'refunded_at' => now(),
            ]);
        }

        return ['status' => 'processed'];
    }

    /**
     * Process Iyzico 3DS authentication
     */
    protected function processIyzico3DSAuth(Request $request, string $webhookId): array
    {
        Log::channel('webhooks')->info("Iyzico 3DS auth webhook", [
            'webhook_id' => $webhookId,
            'payload' => $request->all(),
        ]);

        return ['status' => 'received'];
    }

    /**
     * Handle PayTR payment webhooks
     */
    protected function handlePaytrWebhook(Request $request, string $webhookId): array
    {
        $merchantOid = $request->input('merchant_oid');
        $status = $request->input('status');
        $totalAmount = $request->input('total_amount');
        $paymentType = $request->input('payment_type');
        $failedReasonCode = $request->input('failed_reason_code');
        $failedReasonMsg = $request->input('failed_reason_msg');

        Log::channel('webhooks')->info("PayTR webhook details", [
            'webhook_id' => $webhookId,
            'merchant_oid' => $merchantOid,
            'status' => $status,
            'total_amount' => $totalAmount,
            'payment_type' => $paymentType,
        ]);

        // Find order by merchant_oid (order number)
        $order = Order::where('order_number', $merchantOid)->first();

        if (!$order) {
            Log::channel('webhooks')->warning("Order not found for PayTR payment", [
                'webhook_id' => $webhookId,
                'merchant_oid' => $merchantOid,
            ]);
            // PayTR expects "OK" response
            return ['status' => 'OK'];
        }

        if ($status === 'success') {
            DB::transaction(function () use ($order, $totalAmount) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'paid_amount' => $totalAmount / 100, // Convert kuruş to TL
                    'paid_at' => now(),
                ]);
            });

            Log::channel('webhooks')->info("PayTR payment completed", [
                'webhook_id' => $webhookId,
                'order_id' => $order->id,
                'total_amount' => $totalAmount,
            ]);
        } else {
            $order->update([
                'payment_status' => 'failed',
                'payment_notes' => "Failed: {$failedReasonCode} - {$failedReasonMsg}",
            ]);

            Log::channel('webhooks')->warning("PayTR payment failed", [
                'webhook_id' => $webhookId,
                'order_id' => $order->id,
                'reason_code' => $failedReasonCode,
                'reason_msg' => $failedReasonMsg,
            ]);
        }

        // PayTR expects "OK" response
        return ['status' => 'OK'];
    }

    /**
     * Handle ERP provider webhooks (stock updates, etc.)
     */
    protected function handleErpWebhook(string $provider, Request $request, string $webhookId): array
    {
        $eventType = $request->input('event_type')
            ?? $request->input('eventType')
            ?? $request->input('action')
            ?? 'unknown';

        Log::channel('webhooks')->info("ERP webhook received", [
            'webhook_id' => $webhookId,
            'provider' => $provider,
            'event_type' => $eventType,
        ]);

        switch ($eventType) {
            case 'stock_update':
            case 'product_update':
            case 'inventory_change':
                return $this->processErpStockUpdate($provider, $request, $webhookId);

            case 'product_create':
                return $this->processErpProductCreate($provider, $request, $webhookId);

            case 'product_delete':
                return $this->processErpProductDelete($provider, $request, $webhookId);

            default:
                Log::channel('webhooks')->info("ERP webhook event logged", [
                    'webhook_id' => $webhookId,
                    'provider' => $provider,
                    'event_type' => $eventType,
                    'payload' => $request->all(),
                ]);
                return ['status' => 'received', 'event_type' => $eventType];
        }
    }

    /**
     * Process ERP stock update webhook
     */
    protected function processErpStockUpdate(string $provider, Request $request, string $webhookId): array
    {
        $products = $request->input('products')
            ?? $request->input('items')
            ?? [$request->all()];

        $processed = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $barcode = $product['barcode'] ?? $product['sku'] ?? null;

                if ($barcode) {
                    // Dispatch job to sync single product
                    SyncSingleProductJob::dispatch($provider, $barcode, $product);
                    $processed++;
                } else {
                    $failed++;
                    Log::channel('webhooks')->warning("ERP product missing barcode", [
                        'webhook_id' => $webhookId,
                        'provider' => $provider,
                        'product' => $product,
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::channel('webhooks')->error("ERP stock update failed", [
                    'webhook_id' => $webhookId,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'status' => 'processed',
            'processed' => $processed,
            'failed' => $failed,
        ];
    }

    /**
     * Process ERP product create webhook
     */
    protected function processErpProductCreate(string $provider, Request $request, string $webhookId): array
    {
        $barcode = $request->input('barcode') ?? $request->input('sku');

        if ($barcode) {
            SyncSingleProductJob::dispatch($provider, $barcode, $request->all());
        }

        return ['status' => 'queued'];
    }

    /**
     * Process ERP product delete webhook
     */
    protected function processErpProductDelete(string $provider, Request $request, string $webhookId): array
    {
        $barcode = $request->input('barcode') ?? $request->input('sku');

        Log::channel('webhooks')->info("ERP product delete requested", [
            'webhook_id' => $webhookId,
            'provider' => $provider,
            'barcode' => $barcode,
        ]);

        // Product deletion is typically just marking as inactive, not actual deletion
        // This would be handled by the product management service

        return ['status' => 'received'];
    }

    /**
     * Handle generic/unknown provider webhooks
     */
    protected function handleGenericWebhook(string $provider, Request $request, string $webhookId): array
    {
        Log::channel('webhooks')->info("Generic webhook received", [
            'webhook_id' => $webhookId,
            'provider' => $provider,
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        return ['status' => 'received'];
    }
}
