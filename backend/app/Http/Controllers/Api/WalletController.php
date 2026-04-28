<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\SellerBankAccount;
use App\Models\Setting;
use App\Services\PayoutService;
use App\Services\PdfService;
use App\Services\SettlementService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    protected WalletService $walletService;

    protected PayoutService $payoutService;

    protected SettlementService $settlementService;

    public function __construct(WalletService $walletService, PayoutService $payoutService, SettlementService $settlementService)
    {
        $this->walletService = $walletService;
        $this->payoutService = $payoutService;
        $this->settlementService = $settlementService;
    }

    /**
     * Get wallet summary with payout estimate
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $summary = $this->walletService->getWalletSummary($user);

        // Calculate next payout estimate
        $payoutDay = (int) Setting::getValue('payment.payout_day', 15);
        $payoutMinAmount = (float) Setting::getValue('payment.payout_min_amount', 100);
        $withholdingRate = (float) Setting::getValue('payment.withholding_rate', 1);

        $now = Carbon::now();
        $nextPayoutDate = $now->copy()->day($payoutDay);
        if ($nextPayoutDate->isPast()) {
            $nextPayoutDate->addMonth();
        }

        $availableBalance = $summary['balance'] ?? 0;
        $withholdingAmount = $availableBalance * ($withholdingRate / 100);
        $netPayoutAmount = $availableBalance - $withholdingAmount;
        $isEligible = $netPayoutAmount >= $payoutMinAmount;

        return response()->json([
            'wallet' => $summary,
            'payout_estimate' => [
                'next_payout_date' => $nextPayoutDate->format('Y-m-d'),
                'next_payout_formatted' => $nextPayoutDate->format('d.m.Y'),
                'available_balance' => $availableBalance,
                'withholding_rate' => $withholdingRate,
                'withholding_amount' => round($withholdingAmount, 2),
                'net_payout_amount' => round($netPayoutAmount, 2),
                'min_amount' => $payoutMinAmount,
                'is_eligible' => $isEligible,
            ],
        ]);
    }

    /**
     * Get wallet transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->input('limit', 20);

        $transactions = $this->walletService->getTransactions($user, $limit);

        return response()->json([
            'transactions' => $transactions,
        ]);
    }

    /**
     * Get bank accounts
     */
    public function bankAccounts(Request $request): JsonResponse
    {
        $accounts = SellerBankAccount::where('seller_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->get();

        return response()->json([
            'bank_accounts' => $accounts,
        ]);
    }

    /**
     * Add bank account
     */
    public function addBankAccount(Request $request): JsonResponse
    {
        // Clean IBAN before validation (remove spaces and make uppercase)
        if ($request->has('iban')) {
            $request->merge([
                'iban' => strtoupper(preg_replace('/\s+/', '', $request->iban)),
            ]);
        }

        $request->validate([
            'bank_name' => 'required|string|max:100',
            'iban' => 'required|string|size:26', // Turkish IBAN is exactly 26 characters
            'account_holder' => 'required|string|max:255',
            'swift_code' => 'nullable|string|max:11',
            'tax_id' => 'nullable|string|max:11',
            'tax_office' => 'nullable|string|max:100',
            'kep_address' => 'nullable|string|max:255',
            'mersis_number' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
        ]);

        $iban = $request->iban;

        // Check if IBAN already exists
        $exists = SellerBankAccount::where('seller_id', $request->user()->id)
            ->where('iban', $iban)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => 'Bu IBAN zaten kayıtlı.',
            ], 400);
        }

        $account = SellerBankAccount::create([
            'seller_id' => $request->user()->id,
            'bank_name' => $request->bank_name,
            'iban' => $iban,
            'account_holder' => $request->account_holder,
            'swift_code' => $request->swift_code,
            'tax_id' => $request->tax_id,
            'tax_office' => $request->tax_office,
            'kep_address' => $request->kep_address,
            'mersis_number' => $request->mersis_number,
            'phone' => $request->phone,
            'is_default' => SellerBankAccount::where('seller_id', $request->user()->id)->count() === 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Banka hesabı eklendi.',
            'bank_account' => $account,
        ]);
    }

    /**
     * Delete bank account
     */
    public function deleteBankAccount(SellerBankAccount $bankAccount, Request $request): JsonResponse
    {
        if ($bankAccount->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'Bu hesabı silme yetkiniz yok.',
            ], 403);
        }

        // Check if there are pending payouts with this account
        $hasPendingPayouts = PayoutRequest::where('bank_account_id', $bankAccount->id)
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->exists();

        if ($hasPendingPayouts) {
            return response()->json([
                'success' => false,
                'error' => 'Bu hesapla bekleyen ödeme taleplerini bulunuyor.',
            ], 400);
        }

        $bankAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banka hesabı silindi.',
        ]);
    }

    /**
     * Update bank account
     */
    public function updateBankAccount(SellerBankAccount $bankAccount, Request $request): JsonResponse
    {
        if ($bankAccount->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'Bu hesabı düzenleme yetkiniz yok.',
            ], 403);
        }

        // Clean IBAN before validation
        if ($request->has('iban')) {
            $request->merge([
                'iban' => strtoupper(preg_replace('/\s+/', '', $request->iban)),
            ]);
        }

        $request->validate([
            'bank_name' => 'sometimes|required|string|max:100',
            'iban' => 'sometimes|required|string|size:26',
            'account_holder' => 'sometimes|required|string|max:255',
            'swift_code' => 'nullable|string|max:11',
            'tax_id' => 'nullable|string|max:11',
            'tax_office' => 'nullable|string|max:100',
            'kep_address' => 'nullable|string|max:255',
            'mersis_number' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
        ]);

        // Check if new IBAN already exists (if IBAN is being changed)
        if ($request->has('iban') && $request->iban !== $bankAccount->iban) {
            $exists = SellerBankAccount::where('seller_id', $request->user()->id)
                ->where('iban', $request->iban)
                ->where('id', '!=', $bankAccount->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'error' => 'Bu IBAN zaten kayıtlı.',
                ], 400);
            }
        }

        $bankAccount->update($request->only([
            'bank_name', 'iban', 'account_holder', 'swift_code',
            'tax_id', 'tax_office', 'kep_address', 'mersis_number', 'phone',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Banka hesabı güncellendi.',
            'bank_account' => $bankAccount->fresh(),
        ]);
    }

    /**
     * Set default bank account
     */
    public function setDefaultBankAccount(SellerBankAccount $bankAccount, Request $request): JsonResponse
    {
        if ($bankAccount->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'Bu hesabı düzenleme yetkiniz yok.',
            ], 403);
        }

        $bankAccount->makeDefault();

        return response()->json([
            'success' => true,
            'message' => 'Varsayılan hesap güncellendi.',
        ]);
    }

    /**
     * Get payout requests
     */
    public function payoutRequests(Request $request): JsonResponse
    {
        $requests = $this->payoutService->getSellerRequests($request->user());

        return response()->json([
            'payout_requests' => $requests,
        ]);
    }

    /**
     * Create payout request
     */
    public function createPayoutRequest(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'bank_account_id' => 'required|exists:seller_bank_accounts,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $bankAccount = SellerBankAccount::find($request->bank_account_id);

        if ($bankAccount->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'Geçersiz banka hesabı.',
            ], 400);
        }

        $result = $this->payoutService->createRequest(
            $request->user(),
            $request->amount,
            $bankAccount,
            $request->notes
        );

        if (is_array($result) && isset($result['error'])) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ödeme talebi oluşturuldu.',
            'payout_request' => $result,
        ]);
    }

    /**
     * Get settlements overview (upcoming + past + wallet balances)
     */
    public function settlements(Request $request): JsonResponse
    {
        $user = $request->user();
        $summary = $this->walletService->getWalletSummary($user);
        $upcomingSummary = $this->settlementService->getUpcomingSummary($user);

        // Confirmed + processing sub_order count for this seller
        $confirmedOrderCount = \App\Models\SubOrder::where('seller_id', $user->id)
            ->whereIn('status', ['confirmed', 'processing'])
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
            ->count();

        // Total gross sales (all active sub_orders, no deductions)
        $totalGrossSales = (float) \App\Models\OrderItem::where('seller_id', $user->id)
            ->whereHas('subOrder', fn ($q) => $q->whereIn('status', ['delivered', 'confirmed', 'processing', 'shipped']))
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
            ->sum('total_price');

        return response()->json([
            'upcoming_summary' => $upcomingSummary,
            'upcoming' => $this->settlementService->getUpcomingSettlements($user),
            'past' => $this->settlementService->getPastSettlements($user),
            'wallet' => [
                'balance' => (float) ($summary['balance'] ?? 0),
                'pending_balance' => (float) ($summary['pending_balance'] ?? 0),
            ],
            'total_gross_sales' => round($totalGrossSales, 2),
            'confirmed_order_count' => $confirmedOrderCount,
        ]);
    }

    /**
     * Get settlement details for a specific date
     */
    public function settlementDetails(Request $request, string $date): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:upcoming,past',
        ]);

        $user = $request->user();
        $details = $this->settlementService->getSettlementDetails($user, $date, $request->type);

        return response()->json($details);
    }

    /**
     * Hakediş raporu PDF indir
     */
    public function settlementPdf(Request $request, string $date)
    {
        $request->validate([
            'type' => 'required|in:upcoming,past',
        ]);

        $user = $request->user();

        try {
            $pdfService = app(PdfService::class);
            $pdf = $pdfService->generateSettlementReport($user->id, $date, $request->type);

            $filename = "hakedis-raporu-{$date}.pdf";

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Settlement PDF generation error', [
                'user_id' => $user->id,
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'PDF oluşturulurken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }
}
