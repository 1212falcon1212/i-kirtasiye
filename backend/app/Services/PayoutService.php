<?php

namespace App\Services;

use App\Models\PayoutRequest;
use App\Models\SellerBankAccount;
use App\Models\SellerWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Create a payout request
     */
    public function createRequest(
        User $seller,
        float $amount,
        SellerBankAccount $bankAccount,
        ?string $notes = null
    ): PayoutRequest|array {
        $wallet = $this->walletService->getWallet($seller);

        // Validate amount
        if ($amount <= 0) {
            return ['error' => 'Geçersiz tutar.'];
        }

        if ($amount > $wallet->balance) {
            return ['error' => 'Yetersiz bakiye. Mevcut: ' . number_format($wallet->balance, 2) . ' ₺'];
        }

        // Check if there's already a pending request
        $pendingRequest = PayoutRequest::where('seller_id', $seller->id)
            ->whereIn('status', [PayoutRequest::STATUS_PENDING, PayoutRequest::STATUS_APPROVED, PayoutRequest::STATUS_PROCESSING])
            ->first();

        if ($pendingRequest) {
            return ['error' => 'Zaten bekleyen bir ödeme talebiniz var.'];
        }

        // Create the request
        $request = PayoutRequest::create([
            'seller_id' => $seller->id,
            'bank_account_id' => $bankAccount->id,
            'amount' => $amount,
            'status' => PayoutRequest::STATUS_PENDING,
            'notes' => $notes,
        ]);

        Log::info("Payout request created: {$request->id} for seller {$seller->id}, amount: {$amount}");

        return $request;
    }

    /**
     * Approve a payout request
     */
    public function approveRequest(PayoutRequest $request, User $admin, ?string $notes = null): bool
    {
        if (!$request->canBeApproved()) {
            return false;
        }

        $request->approve($admin, $notes);

        Log::info("Payout request {$request->id} approved by admin {$admin->id}");

        return true;
    }

    /**
     * Reject a payout request
     */
    public function rejectRequest(PayoutRequest $request, User $admin, ?string $notes = null): bool
    {
        if (!$request->canBeRejected()) {
            return false;
        }

        $request->reject($admin, $notes);

        Log::info("Payout request {$request->id} rejected by admin {$admin->id}");

        return true;
    }

    /**
     * Complete a payout request (after actual transfer)
     */
    public function completeRequest(PayoutRequest $request, ?string $transactionReference = null): bool
    {
        if (!$request->canBeCompleted()) {
            return false;
        }

        $seller = $request->seller;
        $amount = $request->amount;

        // Process the withdrawal
        $withdrawn = $this->walletService->processWithdrawal(
            $seller,
            $amount,
            "Ödeme talebi #{$request->id}"
        );

        if (!$withdrawn) {
            Log::error("Failed to process withdrawal for payout request {$request->id}");
            $request->update(['status' => PayoutRequest::STATUS_FAILED]);
            return false;
        }

        $request->complete($transactionReference);

        Log::info("Payout request {$request->id} completed, amount: {$amount}");

        return true;
    }

    /**
     * Get seller's payout requests
     */
    public function getSellerRequests(User $seller, int $limit = 20): object
    {
        return PayoutRequest::forSeller($seller->id)
            ->with('bankAccount')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    /**
     * Get all pending requests (for admin)
     */
    public function getPendingRequests(): object
    {
        return PayoutRequest::pending()
            ->with(['seller', 'bankAccount'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get payout statistics for admin dashboard
     */
    public function getStatistics(): array
    {
        return [
            'pending_count' => PayoutRequest::pending()->count(),
            'pending_amount' => PayoutRequest::pending()->sum('amount'),
            'completed_today' => PayoutRequest::where('status', PayoutRequest::STATUS_COMPLETED)
                ->whereDate('processed_at', today())
                ->sum('amount'),
            'completed_this_month' => PayoutRequest::where('status', PayoutRequest::STATUS_COMPLETED)
                ->whereMonth('processed_at', now()->month)
                ->whereYear('processed_at', now()->year)
                ->sum('amount'),
        ];
    }
}
