<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\SellerWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = new WalletService();
    }

    /**
     * Test getting wallet for a seller creates new wallet if not exists.
     */
    public function test_get_wallet_creates_wallet_if_not_exists(): void
    {
        $seller = User::factory()->seller()->create();

        $wallet = $this->walletService->getWallet($seller);

        $this->assertInstanceOf(SellerWallet::class, $wallet);
        $this->assertEquals($seller->id, $wallet->seller_id);
        $this->assertEquals(0, $wallet->balance);
        $this->assertEquals(0, $wallet->pending_balance);
    }

    /**
     * Test getting wallet returns existing wallet.
     */
    public function test_get_wallet_returns_existing_wallet(): void
    {
        $seller = User::factory()->seller()->create();
        $existingWallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 100.00,
            'pending_balance' => 50.00,
            'withdrawn_balance' => 0,
            'total_earned' => 150.00,
            'total_commission' => 15.00,
        ]);

        $wallet = $this->walletService->getWallet($seller);

        $this->assertEquals($existingWallet->id, $wallet->id);
        $this->assertEquals(100.00, $wallet->balance);
    }

    /**
     * Test getting wallet summary returns correct data.
     */
    public function test_get_wallet_summary_returns_correct_data(): void
    {
        $seller = User::factory()->seller()->create();
        SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 500.00,
            'pending_balance' => 200.00,
            'withdrawn_balance' => 100.00,
            'total_earned' => 800.00,
            'total_commission' => 80.00,
        ]);

        $summary = $this->walletService->getWalletSummary($seller);

        $this->assertEquals(500.00, $summary['balance']);
        $this->assertEquals(200.00, $summary['pending_balance']);
        $this->assertEquals(700.00, $summary['total_balance']); // balance + pending
        $this->assertEquals(100.00, $summary['withdrawn_balance']);
        $this->assertEquals(800.00, $summary['total_earned']);
        $this->assertEquals(80.00, $summary['total_commission']);
    }

    /**
     * Test adding order earnings to pending balance.
     */
    public function test_add_order_earnings_to_pending_balance(): void
    {
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->create();
        $order = Order::factory()->forUser($buyer)->create([
            'order_number' => 'EPZ2401150001ABCD',
        ]);

        $this->walletService->addOrderEarnings(
            seller: $seller,
            order: $order,
            saleAmount: 1000.00,
            commission: 100.00,
            shippingCost: 50.00
        );

        $wallet = $this->walletService->getWallet($seller);

        // Net amount = 1000 - 100 - 50 = 850
        $this->assertEquals(850.00, $wallet->pending_balance);
        $this->assertEquals(100.00, $wallet->total_commission);
        $this->assertEquals(0, $wallet->balance); // Not released yet

        // Check transaction records
        $transactions = WalletTransaction::where('wallet_id', $wallet->id)->get();
        $this->assertGreaterThanOrEqual(2, $transactions->count()); // Sale and commission

        // Check sale transaction
        $saleTransaction = $transactions->where('type', WalletTransaction::TYPE_SALE)->first();
        $this->assertNotNull($saleTransaction);
        $this->assertEquals(1000.00, $saleTransaction->amount);
        $this->assertEquals(WalletTransaction::DIRECTION_CREDIT, $saleTransaction->direction);

        // Check commission transaction
        $commissionTransaction = $transactions->where('type', WalletTransaction::TYPE_COMMISSION)->first();
        $this->assertNotNull($commissionTransaction);
        $this->assertEquals(100.00, $commissionTransaction->amount);
        $this->assertEquals(WalletTransaction::DIRECTION_DEBIT, $commissionTransaction->direction);
    }

    /**
     * Test adding order earnings without shipping cost.
     */
    public function test_add_order_earnings_without_shipping(): void
    {
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->create();
        $order = Order::factory()->forUser($buyer)->create();

        $this->walletService->addOrderEarnings(
            seller: $seller,
            order: $order,
            saleAmount: 500.00,
            commission: 50.00
        );

        $wallet = $this->walletService->getWallet($seller);

        // Net amount = 500 - 50 = 450
        $this->assertEquals(450.00, $wallet->pending_balance);
    }

    /**
     * Test processing withdrawal successfully.
     */
    public function test_process_withdrawal_successfully(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 500.00,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'total_earned' => 500.00,
            'total_commission' => 50.00,
        ]);

        $result = $this->walletService->processWithdrawal($seller, 200.00);

        $this->assertTrue($result);
        $this->assertEquals(300.00, $wallet->fresh()->balance);
        $this->assertEquals(200.00, $wallet->fresh()->withdrawn_balance);

        // Check transaction
        $transaction = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', WalletTransaction::TYPE_WITHDRAWAL)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(200.00, $transaction->amount);
        $this->assertEquals(WalletTransaction::DIRECTION_DEBIT, $transaction->direction);
    }

    /**
     * Test processing withdrawal fails with insufficient balance.
     */
    public function test_process_withdrawal_fails_with_insufficient_balance(): void
    {
        $seller = User::factory()->seller()->create();
        SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 100.00,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'total_earned' => 100.00,
            'total_commission' => 10.00,
        ]);

        $result = $this->walletService->processWithdrawal($seller, 200.00);

        $this->assertFalse($result);

        // Balance should be unchanged
        $wallet = $this->walletService->getWallet($seller);
        $this->assertEquals(100.00, $wallet->balance);
    }

    /**
     * Test processing withdrawal fails when balance is exactly zero.
     */
    public function test_process_withdrawal_fails_with_zero_balance(): void
    {
        $seller = User::factory()->seller()->create();
        SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 0,
            'pending_balance' => 100.00, // Pending doesn't count
            'withdrawn_balance' => 0,
            'total_earned' => 100.00,
            'total_commission' => 0,
        ]);

        $result = $this->walletService->processWithdrawal($seller, 50.00);

        $this->assertFalse($result);
    }

    /**
     * Test withdrawal with custom description.
     */
    public function test_process_withdrawal_with_custom_description(): void
    {
        $seller = User::factory()->seller()->create();
        SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 500.00,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'total_earned' => 500.00,
            'total_commission' => 0,
        ]);

        $this->walletService->processWithdrawal($seller, 100.00, 'Ocak 2024 odemesi');

        $transaction = WalletTransaction::where('type', WalletTransaction::TYPE_WITHDRAWAL)->first();
        $this->assertEquals('Ocak 2024 odemesi', $transaction->description);
    }

    /**
     * Test getting transactions returns correct records.
     */
    public function test_get_transactions_returns_records(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 500.00,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'total_earned' => 500.00,
            'total_commission' => 0,
        ]);

        // Create some transactions
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_SALE,
            'amount' => 500.00,
            'direction' => WalletTransaction::DIRECTION_CREDIT,
            'balance_type' => WalletTransaction::BALANCE_AVAILABLE,
            'description' => 'Test sale',
        ]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_COMMISSION,
            'amount' => 50.00,
            'direction' => WalletTransaction::DIRECTION_DEBIT,
            'balance_type' => WalletTransaction::BALANCE_AVAILABLE,
            'description' => 'Test commission',
        ]);

        $transactions = $this->walletService->getTransactions($seller);

        $this->assertCount(2, $transactions);
    }

    /**
     * Test getting transactions respects limit.
     */
    public function test_get_transactions_respects_limit(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 0,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'total_earned' => 0,
            'total_commission' => 0,
        ]);

        // Create 10 transactions
        for ($i = 0; $i < 10; $i++) {
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransaction::TYPE_SALE,
                'amount' => 100.00,
                'direction' => WalletTransaction::DIRECTION_CREDIT,
                'balance_type' => WalletTransaction::BALANCE_PENDING,
                'description' => "Transaction {$i}",
            ]);
        }

        $transactions = $this->walletService->getTransactions($seller, 5);

        $this->assertCount(5, $transactions);
    }

    /**
     * Test SellerWallet model can_withdraw method.
     */
    public function test_wallet_can_withdraw_returns_correct_result(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 100.00,
            'pending_balance' => 50.00, // Pending doesn't count for withdrawal
            'withdrawn_balance' => 0,
            'total_earned' => 150.00,
            'total_commission' => 0,
        ]);

        $this->assertTrue($wallet->canWithdraw(100.00));
        $this->assertTrue($wallet->canWithdraw(50.00));
        $this->assertFalse($wallet->canWithdraw(150.00)); // More than available balance
        $this->assertFalse($wallet->canWithdraw(101.00));
    }

    /**
     * Test SellerWallet total_balance attribute.
     */
    public function test_wallet_total_balance_attribute(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 300.00,
            'pending_balance' => 200.00,
            'withdrawn_balance' => 0,
            'total_earned' => 500.00,
            'total_commission' => 0,
        ]);

        $this->assertEquals(500.00, $wallet->total_balance);
    }

    /**
     * Test SellerWallet add_pending_balance method.
     */
    public function test_wallet_add_pending_balance(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 0,
            'pending_balance' => 100.00,
            'withdrawn_balance' => 0,
            'total_earned' => 100.00,
            'total_commission' => 0,
        ]);

        $wallet->addPendingBalance(50.00);

        $this->assertEquals(150.00, $wallet->fresh()->pending_balance);
        $this->assertEquals(150.00, $wallet->fresh()->total_earned);
    }

    /**
     * Test SellerWallet release_pending_to_available method.
     */
    public function test_wallet_release_pending_to_available(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 0,
            'pending_balance' => 200.00,
            'withdrawn_balance' => 0,
            'total_earned' => 200.00,
            'total_commission' => 0,
        ]);

        $result = $wallet->releasePendingToAvailable(100.00);

        $this->assertTrue($result);
        $this->assertEquals(100.00, $wallet->fresh()->balance);
        $this->assertEquals(100.00, $wallet->fresh()->pending_balance);
    }

    /**
     * Test release pending fails when amount exceeds pending balance.
     */
    public function test_wallet_release_pending_fails_when_amount_exceeds(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 0,
            'pending_balance' => 100.00,
            'withdrawn_balance' => 0,
            'total_earned' => 100.00,
            'total_commission' => 0,
        ]);

        $result = $wallet->releasePendingToAvailable(150.00);

        $this->assertFalse($result);
        $this->assertEquals(0, $wallet->fresh()->balance);
        $this->assertEquals(100.00, $wallet->fresh()->pending_balance);
    }

    /**
     * Test SellerWallet withdraw method.
     */
    public function test_wallet_withdraw_method(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 500.00,
            'pending_balance' => 0,
            'withdrawn_balance' => 100.00,
            'total_earned' => 600.00,
            'total_commission' => 0,
        ]);

        $result = $wallet->withdraw(200.00);

        $this->assertTrue($result);
        $this->assertEquals(300.00, $wallet->fresh()->balance);
        $this->assertEquals(300.00, $wallet->fresh()->withdrawn_balance);
    }

    /**
     * Test SellerWallet withdraw fails when insufficient balance.
     */
    public function test_wallet_withdraw_fails_when_insufficient(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 100.00,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'total_earned' => 100.00,
            'total_commission' => 0,
        ]);

        $result = $wallet->withdraw(200.00);

        $this->assertFalse($result);
        $this->assertEquals(100.00, $wallet->fresh()->balance);
    }

    /**
     * Test SellerWallet add_commission method.
     */
    public function test_wallet_add_commission(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 0,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'total_earned' => 0,
            'total_commission' => 50.00,
        ]);

        $wallet->addCommission(25.00);

        $this->assertEquals(75.00, $wallet->fresh()->total_commission);
    }

    /**
     * Test SellerWallet belongs to seller.
     */
    public function test_wallet_belongs_to_seller(): void
    {
        $seller = User::factory()->seller()->create([
            'pharmacy_name' => 'Test Eczanesi',
        ]);

        $wallet = SellerWallet::create([
            'seller_id' => $seller->id,
            'balance' => 0,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'total_earned' => 0,
            'total_commission' => 0,
        ]);

        $this->assertInstanceOf(User::class, $wallet->seller);
        $this->assertEquals('Test Eczanesi', $wallet->seller->pharmacy_name);
    }

    /**
     * Test WalletTransaction signed_amount attribute.
     */
    public function test_transaction_signed_amount_for_credit(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::getOrCreate($seller);

        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_SALE,
            'amount' => 100.00,
            'direction' => WalletTransaction::DIRECTION_CREDIT,
            'balance_type' => WalletTransaction::BALANCE_PENDING,
            'description' => 'Test',
        ]);

        $this->assertEquals(100.00, $transaction->signed_amount);
    }

    /**
     * Test WalletTransaction signed_amount for debit.
     */
    public function test_transaction_signed_amount_for_debit(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::getOrCreate($seller);

        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_COMMISSION,
            'amount' => 50.00,
            'direction' => WalletTransaction::DIRECTION_DEBIT,
            'balance_type' => WalletTransaction::BALANCE_PENDING,
            'description' => 'Test',
        ]);

        $this->assertEquals(-50.00, $transaction->signed_amount);
    }

    /**
     * Test WalletTransaction type_label attribute.
     */
    public function test_transaction_type_label(): void
    {
        $seller = User::factory()->seller()->create();
        $wallet = SellerWallet::getOrCreate($seller);

        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_SALE,
            'amount' => 100.00,
            'direction' => WalletTransaction::DIRECTION_CREDIT,
            'balance_type' => WalletTransaction::BALANCE_PENDING,
            'description' => 'Test',
        ]);

        $this->assertEquals('Satış Geliri', $transaction->type_label);
    }
}
