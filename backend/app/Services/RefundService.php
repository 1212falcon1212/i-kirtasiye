<?php

namespace App\Services;

use App\Interfaces\RefundResult;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\SubOrder;
use App\Models\User;
use App\Services\Payments\PaymentManager;
use Illuminate\Support\Facades\Log;

class RefundService
{
    public function __construct(
        private PaymentManager $paymentManager,
        private WalletService $walletService,
    ) {}

    /**
     * Process refund for a specific sub-order (PayTR + wallet reversal)
     */
    public function processSubOrderRefund(
        Order $order,
        SubOrder $subOrder,
        string $reason = 'cancel'
    ): RefundResult {
        // Calculate refund amount from sub-order items
        $refundAmount = (float) $order->items
            ->where('sub_order_id', $subOrder->id)
            ->sum('total_price');

        if ($refundAmount <= 0) {
            return RefundResult::failure('İade tutarı hesaplanamadı.');
        }

        // PayTR refund (only for paid orders)
        $paytrResult = null;
        if ($order->payment_status === 'paid') {
            $provider = $this->paymentManager->getProvider();
            if ($provider) {
                $paytrResult = $provider->refund($order, $refundAmount);
                if (!$paytrResult->success) {
                    Log::error('PayTR refund failed', [
                        'sub_order_id' => $subOrder->id,
                        'error' => $paytrResult->error,
                    ]);
                    return $paytrResult;
                }
            }
        }

        // Reverse seller wallet earnings
        $seller = User::find($subOrder->seller_id);
        if ($seller) {
            $this->walletService->reverseSubOrderEarnings($seller, $order, $subOrder->id);
        }

        // If all sub-orders are in terminal state, mark parent order as refunded
        $order->load('subOrders');
        $allTerminal = $order->subOrders->every(
            fn($so) => in_array($so->status, ['cancelled', 'returned'])
        );
        if ($allTerminal && $order->payment_status === 'paid') {
            $order->update(['payment_status' => 'refunded']);
        }

        Log::info("Sub-order refund processed", [
            'order' => $order->order_number,
            'sub_order_id' => $subOrder->id,
            'amount' => $refundAmount,
            'reason' => $reason,
        ]);

        return RefundResult::success(
            refundId: $paytrResult?->refundId ?? ('WALLET-' . $subOrder->id),
            refundedAmount: $refundAmount,
        );
    }

    /**
     * Process refund for a specific item/partial quantity (PayTR + wallet reversal)
     */
    public function processItemRefund(
        Order $order,
        ReturnRequest $returnRequest,
    ): RefundResult {
        $refundAmount = (float) $returnRequest->refund_amount;

        if ($refundAmount <= 0) {
            return RefundResult::failure('Iade tutari hesaplanamadi.');
        }

        // PayTR partial refund
        $paytrResult = null;
        if ($order->payment_status === 'paid') {
            $provider = $this->paymentManager->getProvider();
            if ($provider) {
                $paytrResult = $provider->refund($order, $refundAmount);
                if (!$paytrResult->success) {
                    Log::error('PayTR item refund failed', [
                        'return_request_id' => $returnRequest->id,
                        'error' => $paytrResult->error,
                    ]);
                    return $paytrResult;
                }
            }
        }

        // Reverse seller wallet earnings for partial amount
        $seller = User::find($returnRequest->seller_id);
        if ($seller) {
            $this->walletService->reverseItemEarnings(
                $seller, $order, $returnRequest
            );
        }

        Log::info("Item refund processed", [
            'order' => $order->order_number,
            'return_request_id' => $returnRequest->id,
            'amount' => $refundAmount,
        ]);

        return RefundResult::success(
            refundId: $paytrResult?->refundId ?? ('ITEM-REFUND-' . $returnRequest->id),
            refundedAmount: $refundAmount,
        );
    }
}
