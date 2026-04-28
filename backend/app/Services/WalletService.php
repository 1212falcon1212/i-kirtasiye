<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\SellerWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * Get or create wallet for a seller
     */
    public function getWallet(User $seller): SellerWallet
    {
        return SellerWallet::getOrCreate($seller);
    }

    /**
     * Add earnings from a completed order item
     */
    public function addOrderEarnings(
        User $seller,
        Order $order,
        float $saleAmount,
        float $commission,
        ?float $shippingCost = null,
        ?int $orderItemId = null,
        ?int $subOrderId = null
    ): void {
        $wallet = $this->getWallet($seller);

        DB::transaction(function () use ($wallet, $order, $saleAmount, $commission, $shippingCost, $orderItemId, $subOrderId) {
            // Add sale amount to pending balance
            $netAmount = $saleAmount - $commission - ($shippingCost ?? 0);

            $wallet->addPendingBalance($netAmount);
            $wallet->addCommission($commission);

            // Record sale transaction
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransaction::TYPE_SALE,
                'amount' => $saleAmount,
                'direction' => WalletTransaction::DIRECTION_CREDIT,
                'balance_type' => WalletTransaction::BALANCE_PENDING,
                'description' => "Sipariş #{$order->order_number} - Satış",
                'order_id' => $order->id,
                'sub_order_id' => $subOrderId,
                'order_item_id' => $orderItemId,
            ]);

            // Record commission deduction
            if ($commission > 0) {
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => WalletTransaction::TYPE_COMMISSION,
                    'amount' => $commission,
                    'direction' => WalletTransaction::DIRECTION_DEBIT,
                    'balance_type' => WalletTransaction::BALANCE_PENDING,
                    'description' => "Sipariş #{$order->order_number} - Hizmet Bedeli",
                    'order_id' => $order->id,
                    'sub_order_id' => $subOrderId,
                    'order_item_id' => $orderItemId,
                ]);
            }

            // Record shipping cost if applicable
            if ($shippingCost && $shippingCost > 0) {
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => WalletTransaction::TYPE_SHIPPING,
                    'amount' => $shippingCost,
                    'direction' => WalletTransaction::DIRECTION_DEBIT,
                    'balance_type' => WalletTransaction::BALANCE_PENDING,
                    'description' => "Sipariş #{$order->order_number} - Kargo",
                    'order_id' => $order->id,
                    'sub_order_id' => $subOrderId,
                    'order_item_id' => $orderItemId,
                ]);
            }
        });

        Log::info("Wallet earnings added for seller {$seller->id}: sale={$saleAmount}, commission={$commission}, subOrder={$subOrderId}");
    }

    /**
     * Release pending balance to available (after delivery confirmation + hold period)
     */
    public function releasePendingBalance(User $seller, Order $order, ?int $subOrderId = null): bool
    {
        $wallet = $this->getWallet($seller);

        // Duplicate release guard
        $releaseQuery = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_RELEASE);

        if ($subOrderId) {
            $releaseQuery->where('sub_order_id', $subOrderId);
        } else {
            $releaseQuery->where('order_id', $order->id);
        }

        if ($releaseQuery->exists()) {
            Log::info("Skipping duplicate release for wallet {$wallet->id}, order {$order->order_number}, subOrder {$subOrderId}");
            return false;
        }

        // Calculate total net amount from pending transactions
        $txQuery = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('balance_type', WalletTransaction::BALANCE_PENDING);

        if ($subOrderId) {
            $txQuery->where('sub_order_id', $subOrderId);
        } else {
            $txQuery->where('order_id', $order->id);
        }

        $transactions = $txQuery->get();

        $netAmount = $transactions->reduce(function ($carry, $tx) {
            return $carry + $tx->signed_amount;
        }, 0);

        if ($netAmount <= 0) {
            return false;
        }

        DB::transaction(function () use ($wallet, $netAmount, $order, $subOrderId) {
            $wallet->releasePendingToAvailable($netAmount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransaction::TYPE_RELEASE,
                'amount' => $netAmount,
                'direction' => WalletTransaction::DIRECTION_CREDIT,
                'balance_type' => WalletTransaction::BALANCE_AVAILABLE,
                'description' => "Sipariş #{$order->order_number} - Bakiye Serbest Bırakma",
                'order_id' => $order->id,
                'sub_order_id' => $subOrderId,
            ]);

            Log::info("Released pending balance for wallet {$wallet->id}: {$netAmount} for order {$order->order_number}, subOrder {$subOrderId}");
        });

        return true;
    }

    /**
     * Reverse earnings for a cancelled/refunded sub-order
     */
    public function reverseSubOrderEarnings(User $seller, Order $order, int $subOrderId): void
    {
        $wallet = $this->getWallet($seller);

        // Find all pending transactions for this sub-order
        $pendingTxs = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('sub_order_id', $subOrderId)
            ->where('balance_type', WalletTransaction::BALANCE_PENDING)
            ->get();

        // Net pending amount (sale credit - commission debit - shipping debit)
        $netPending = $pendingTxs->reduce(fn($carry, $tx) => $carry + $tx->signed_amount, 0);

        // Check if balance was already released to available
        $releaseTx = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('sub_order_id', $subOrderId)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->first();

        // Check if already reversed (idempotency guard)
        $alreadyReversed = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('sub_order_id', $subOrderId)
            ->where('type', WalletTransaction::TYPE_REFUND)
            ->exists();

        if ($alreadyReversed) {
            Log::info("Skipping duplicate wallet reversal for seller {$seller->id}, subOrder {$subOrderId}");
            return;
        }

        DB::transaction(function () use ($wallet, $order, $subOrderId, $netPending, $releaseTx, $pendingTxs) {
            if ($releaseTx) {
                // Balance already moved to available — deduct from available
                $refundAmount = (float) $releaseTx->amount;
                $wallet->decrement('balance', $refundAmount);
            } elseif ($netPending > 0) {
                // Still in pending — deduct from pending
                $wallet->decrement('pending_balance', $netPending);
            }

            // Reverse commission tracking
            $commissionTx = $pendingTxs->firstWhere('type', WalletTransaction::TYPE_COMMISSION);
            if ($commissionTx) {
                $wallet->decrement('total_commission', $commissionTx->amount);
            }

            // Reverse total earned tracking
            $saleTx = $pendingTxs->firstWhere('type', WalletTransaction::TYPE_SALE);
            if ($saleTx) {
                $wallet->decrement('total_earned', $saleTx->amount);
            }

            // Record refund transaction
            $refundTotal = $releaseTx ? (float) $releaseTx->amount : max($netPending, 0);
            if ($refundTotal > 0) {
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => WalletTransaction::TYPE_REFUND,
                    'amount' => $refundTotal,
                    'direction' => WalletTransaction::DIRECTION_DEBIT,
                    'balance_type' => $releaseTx
                        ? WalletTransaction::BALANCE_AVAILABLE
                        : WalletTransaction::BALANCE_PENDING,
                    'description' => "Sipariş #{$order->order_number} - İade",
                    'order_id' => $order->id,
                    'sub_order_id' => $subOrderId,
                ]);
            }
        });

        Log::info("Wallet earnings reversed for seller {$seller->id}, subOrder {$subOrderId}");
    }

    /**
     * Reverse earnings for a specific item/partial quantity return
     */
    public function reverseItemEarnings(
        User $seller,
        Order $order,
        ReturnRequest $returnRequest
    ): void {
        $wallet = $this->getWallet($seller);
        $refundAmount = (float) $returnRequest->refund_amount;

        // Idempotency guard — already refunded for this return request?
        $alreadyReversed = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_REFUND)
            ->where('description', 'LIKE', "%ReturnRequest#{$returnRequest->id}%")
            ->exists();

        if ($alreadyReversed) {
            Log::info("Skipping duplicate item wallet reversal for return request {$returnRequest->id}");
            return;
        }

        // Calculate commission rate from existing sale transaction
        $subOrderId = $order->subOrders->firstWhere('seller_id', $seller->id)?->id;
        $saleTx = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('sub_order_id', $subOrderId)
            ->where('type', WalletTransaction::TYPE_SALE)
            ->first();
        $commTx = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('sub_order_id', $subOrderId)
            ->where('type', WalletTransaction::TYPE_COMMISSION)
            ->first();

        $commissionRate = ($saleTx && $commTx && $saleTx->amount > 0)
            ? $commTx->amount / $saleTx->amount
            : 0;

        $commissionRefund = round($refundAmount * $commissionRate, 2);
        $netRefund = $refundAmount - $commissionRefund;

        // Check if balance was already released to available
        $releaseTx = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('sub_order_id', $subOrderId)
            ->where('type', WalletTransaction::TYPE_RELEASE)
            ->first();

        DB::transaction(function () use ($wallet, $order, $returnRequest, $netRefund, $commissionRefund, $refundAmount, $releaseTx, $subOrderId) {
            if ($releaseTx) {
                $wallet->decrement('balance', $netRefund);
            } else {
                $wallet->decrement('pending_balance', $netRefund);
            }

            if ($commissionRefund > 0) {
                $wallet->decrement('total_commission', $commissionRefund);
            }
            $wallet->decrement('total_earned', $refundAmount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransaction::TYPE_REFUND,
                'amount' => $netRefund,
                'direction' => WalletTransaction::DIRECTION_DEBIT,
                'balance_type' => $releaseTx
                    ? WalletTransaction::BALANCE_AVAILABLE
                    : WalletTransaction::BALANCE_PENDING,
                'description' => "Siparis #{$order->order_number} - Kismi Iade (ReturnRequest#{$returnRequest->id})",
                'order_id' => $order->id,
                'sub_order_id' => $subOrderId,
                'order_item_id' => $returnRequest->order_item_id,
            ]);
        });

        Log::info("Item wallet reversal for seller {$seller->id}, returnRequest {$returnRequest->id}, amount {$netRefund}");
    }

    /**
     * Process withdrawal from wallet
     */
    public function processWithdrawal(User $seller, float $amount, string $description = 'Para çekme'): bool
    {
        $wallet = $this->getWallet($seller);

        if (!$wallet->canWithdraw($amount)) {
            return false;
        }

        DB::transaction(function () use ($wallet, $amount, $description) {
            $wallet->withdraw($amount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransaction::TYPE_WITHDRAWAL,
                'amount' => $amount,
                'direction' => WalletTransaction::DIRECTION_DEBIT,
                'balance_type' => WalletTransaction::BALANCE_AVAILABLE,
                'description' => $description,
            ]);
        });

        return true;
    }

    /**
     * Get wallet summary for a seller
     */
    public function getWalletSummary(User $seller): array
    {
        $wallet = $this->getWallet($seller);

        return [
            'balance' => $wallet->balance,
            'pending_balance' => $wallet->pending_balance,
            'total_balance' => $wallet->total_balance,
            'withdrawn_balance' => $wallet->withdrawn_balance,
            'total_earned' => $wallet->total_earned,
            'total_commission' => $wallet->total_commission,
        ];
    }

    /**
     * Get recent transactions
     */
    public function getTransactions(User $seller, int $limit = 20): object
    {
        $wallet = $this->getWallet($seller);

        return $wallet->transactions()->take($limit)->get();
    }
}
