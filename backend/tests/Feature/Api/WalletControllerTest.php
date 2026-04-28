<?php

namespace Tests\Feature\Api;

use App\Models\PayoutRequest;
use App\Models\SellerBankAccount;
use App\Models\SellerWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->seller()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Helper method to make authenticated requests
     */
    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ==========================================
    // WALLET SUMMARY TESTS
    // ==========================================

    /**
     * Test getting wallet summary for new user creates wallet automatically.
     */
    public function test_index_returns_wallet_summary_for_new_user(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'wallet' => [
                    'balance',
                    'pending_balance',
                    'total_balance',
                    'withdrawn_balance',
                    'total_earned',
                    'total_commission',
                ],
            ]);

        // Verify wallet was created with zero balances
        $response->assertJson([
            'wallet' => [
                'balance' => '0.00',
                'pending_balance' => '0.00',
            ],
        ]);

        // Verify wallet exists in database
        $this->assertDatabaseHas('seller_wallets', [
            'seller_id' => $this->user->id,
        ]);
    }

    /**
     * Test getting wallet summary with existing wallet and balances.
     */
    public function test_index_returns_correct_balances_for_existing_wallet(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(1500.50)
            ->withPendingBalance(500.25)
            ->withWithdrawnBalance(2000.00)
            ->withCommission(150.75)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet');

        $response->assertStatus(200)
            ->assertJson([
                'wallet' => [
                    'balance' => '1500.50',
                    'pending_balance' => '500.25',
                    'withdrawn_balance' => '2000.00',
                    'total_commission' => '150.75',
                ],
            ]);
    }

    /**
     * Test wallet summary requires authentication.
     */
    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/wallet');

        $response->assertStatus(401);
    }

    // ==========================================
    // WALLET TRANSACTIONS TESTS
    // ==========================================

    /**
     * Test getting wallet transactions returns empty for new wallet.
     */
    public function test_transactions_returns_empty_for_new_wallet(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/transactions');

        $response->assertStatus(200)
            ->assertJson([
                'transactions' => [],
            ]);
    }

    /**
     * Test getting wallet transactions with existing transactions.
     */
    public function test_transactions_returns_transaction_list(): void
    {
        $wallet = SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(1000)
            ->create();

        WalletTransaction::factory()
            ->forWallet($wallet)
            ->sale(500.00)
            ->create();

        WalletTransaction::factory()
            ->forWallet($wallet)
            ->commission(50.00)
            ->create();

        WalletTransaction::factory()
            ->forWallet($wallet)
            ->withdrawal(200.00)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'transactions');
    }

    /**
     * Test transactions limit parameter works correctly.
     */
    public function test_transactions_respects_limit_parameter(): void
    {
        $wallet = SellerWallet::factory()
            ->forSeller($this->user)
            ->create();

        // Create 10 transactions
        for ($i = 0; $i < 10; $i++) {
            WalletTransaction::factory()
                ->forWallet($wallet)
                ->sale(100.00 + $i)
                ->create();
        }

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/transactions?limit=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'transactions');
    }

    /**
     * Test transactions requires authentication.
     */
    public function test_transactions_requires_authentication(): void
    {
        $response = $this->getJson('/api/wallet/transactions');

        $response->assertStatus(401);
    }

    // ==========================================
    // BANK ACCOUNTS LIST TESTS
    // ==========================================

    /**
     * Test getting bank accounts returns empty for new user.
     */
    public function test_bank_accounts_returns_empty_for_new_user(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/bank-accounts');

        $response->assertStatus(200)
            ->assertJson([
                'bank_accounts' => [],
            ]);
    }

    /**
     * Test getting bank accounts with existing accounts.
     */
    public function test_bank_accounts_returns_account_list(): void
    {
        SellerBankAccount::factory()
            ->forSeller($this->user)
            ->default()
            ->create(['bank_name' => 'Ziraat Bankasi']);

        SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create(['bank_name' => 'Is Bankasi']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/bank-accounts');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'bank_accounts');
    }

    /**
     * Test bank accounts are ordered by default first.
     */
    public function test_bank_accounts_ordered_by_default_first(): void
    {
        $nonDefault = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create(['bank_name' => 'Non-Default Bank']);

        $defaultAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->default()
            ->create(['bank_name' => 'Default Bank']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/bank-accounts');

        $response->assertStatus(200);
        $accounts = $response->json('bank_accounts');

        $this->assertEquals('Default Bank', $accounts[0]['bank_name']);
        $this->assertEquals('Non-Default Bank', $accounts[1]['bank_name']);
    }

    /**
     * Test bank accounts requires authentication.
     */
    public function test_bank_accounts_requires_authentication(): void
    {
        $response = $this->getJson('/api/wallet/bank-accounts');

        $response->assertStatus(401);
    }

    /**
     * Test user cannot see other users bank accounts.
     */
    public function test_user_cannot_see_other_users_bank_accounts(): void
    {
        $otherUser = User::factory()->seller()->create();

        SellerBankAccount::factory()
            ->forSeller($otherUser)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/bank-accounts');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'bank_accounts');
    }

    // ==========================================
    // ADD BANK ACCOUNT TESTS
    // ==========================================

    /**
     * Test adding bank account with valid data.
     */
    public function test_add_bank_account_with_valid_data(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/bank-accounts', [
                'bank_name' => 'Ziraat Bankasi',
                'iban' => 'TR330006100519786457841326',
                'account_holder' => 'Test User',
                'swift_code' => 'TCZBTR2A',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Banka hesabı eklendi.',
            ])
            ->assertJsonStructure([
                'bank_account' => [
                    'id',
                    'bank_name',
                    'iban',
                    'account_holder',
                ],
            ]);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'seller_id' => $this->user->id,
            'bank_name' => 'Ziraat Bankasi',
            'iban' => 'TR330006100519786457841326',
        ]);
    }

    /**
     * Test first bank account is set as default automatically.
     */
    public function test_first_bank_account_is_set_as_default(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/bank-accounts', [
                'bank_name' => 'Ziraat Bankasi',
                'iban' => 'TR330006100519786457841326',
                'account_holder' => 'Test User',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'seller_id' => $this->user->id,
            'is_default' => true,
        ]);
    }

    /**
     * Test second bank account is not default.
     */
    public function test_second_bank_account_is_not_default(): void
    {
        SellerBankAccount::factory()
            ->forSeller($this->user)
            ->default()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/bank-accounts', [
                'bank_name' => 'Is Bankasi',
                'iban' => 'TR330006100519786457841327',
                'account_holder' => 'Test User',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'seller_id' => $this->user->id,
            'iban' => 'TR330006100519786457841327',
            'is_default' => false,
        ]);
    }

    /**
     * Test adding bank account with duplicate IBAN fails.
     */
    public function test_add_bank_account_fails_with_duplicate_iban(): void
    {
        SellerBankAccount::factory()
            ->forSeller($this->user)
            ->withIban('TR330006100519786457841326')
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/bank-accounts', [
                'bank_name' => 'Is Bankasi',
                'iban' => 'TR330006100519786457841326',
                'account_holder' => 'Test User',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Bu IBAN zaten kayıtlı.',
            ]);
    }

    /**
     * Test IBAN is normalized (spaces removed, uppercase).
     */
    public function test_iban_is_normalized(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/bank-accounts', [
                'bank_name' => 'Ziraat Bankasi',
                'iban' => 'tr33 0006 1005 1978 6457 8413 26',
                'account_holder' => 'Test User',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'seller_id' => $this->user->id,
            'iban' => 'TR330006100519786457841326',
        ]);
    }

    /**
     * Test adding bank account fails with invalid IBAN length.
     */
    public function test_add_bank_account_fails_with_invalid_iban_length(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/bank-accounts', [
                'bank_name' => 'Ziraat Bankasi',
                'iban' => 'TR12345', // Too short
                'account_holder' => 'Test User',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['iban']);
    }

    /**
     * Test adding bank account fails without required fields.
     */
    public function test_add_bank_account_fails_without_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/bank-accounts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bank_name', 'iban', 'account_holder']);
    }

    /**
     * Test adding bank account requires authentication.
     */
    public function test_add_bank_account_requires_authentication(): void
    {
        $response = $this->postJson('/api/wallet/bank-accounts', [
            'bank_name' => 'Ziraat Bankasi',
            'iban' => 'TR330006100519786457841326',
            'account_holder' => 'Test User',
        ]);

        $response->assertStatus(401);
    }

    // ==========================================
    // UPDATE BANK ACCOUNT TESTS
    // ==========================================

    /**
     * Test updating bank account with valid data.
     */
    public function test_update_bank_account_with_valid_data(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->withBank('Ziraat Bankasi')
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/wallet/bank-accounts/{$bankAccount->id}", [
                'bank_name' => 'Is Bankasi',
                'account_holder' => 'Updated Holder',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Banka hesabı güncellendi.',
            ]);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'id' => $bankAccount->id,
            'bank_name' => 'Is Bankasi',
            'account_holder' => 'Updated Holder',
        ]);
    }

    /**
     * Test updating bank account IBAN.
     */
    public function test_update_bank_account_iban(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->withIban('TR330006100519786457841326')
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/wallet/bank-accounts/{$bankAccount->id}", [
                'iban' => 'TR330006100519786457841327',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'id' => $bankAccount->id,
            'iban' => 'TR330006100519786457841327',
        ]);
    }

    /**
     * Test updating bank account fails with duplicate IBAN.
     */
    public function test_update_bank_account_fails_with_duplicate_iban(): void
    {
        SellerBankAccount::factory()
            ->forSeller($this->user)
            ->withIban('TR330006100519786457841326')
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->withIban('TR330006100519786457841327')
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/wallet/bank-accounts/{$bankAccount->id}", [
                'iban' => 'TR330006100519786457841326',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Bu IBAN zaten kayıtlı.',
            ]);
    }

    /**
     * Test user cannot update another users bank account.
     */
    public function test_user_cannot_update_another_users_bank_account(): void
    {
        $otherUser = User::factory()->seller()->create();
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($otherUser)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/wallet/bank-accounts/{$bankAccount->id}", [
                'bank_name' => 'Updated Bank',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Bu hesabı düzenleme yetkiniz yok.',
            ]);
    }

    /**
     * Test updating bank account requires authentication.
     */
    public function test_update_bank_account_requires_authentication(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->putJson("/api/wallet/bank-accounts/{$bankAccount->id}", [
            'bank_name' => 'Updated Bank',
        ]);

        $response->assertStatus(401);
    }

    // ==========================================
    // DELETE BANK ACCOUNT TESTS
    // ==========================================

    /**
     * Test deleting bank account successfully.
     */
    public function test_delete_bank_account_successfully(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/wallet/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Banka hesabı silindi.',
            ]);

        $this->assertDatabaseMissing('seller_bank_accounts', [
            'id' => $bankAccount->id,
        ]);
    }

    /**
     * Test user cannot delete another users bank account.
     */
    public function test_user_cannot_delete_another_users_bank_account(): void
    {
        $otherUser = User::factory()->seller()->create();
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($otherUser)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/wallet/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Bu hesabı silme yetkiniz yok.',
            ]);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'id' => $bankAccount->id,
        ]);
    }

    /**
     * Test cannot delete bank account with pending payouts.
     */
    public function test_cannot_delete_bank_account_with_pending_payouts(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->pending()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/wallet/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Bu hesapla bekleyen ödeme taleplerini bulunuyor.',
            ]);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'id' => $bankAccount->id,
        ]);
    }

    /**
     * Test cannot delete bank account with approved payouts.
     */
    public function test_cannot_delete_bank_account_with_approved_payouts(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->approved()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/wallet/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(400);
    }

    /**
     * Test cannot delete bank account with processing payouts.
     */
    public function test_cannot_delete_bank_account_with_processing_payouts(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->processing()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/wallet/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(400);
    }

    /**
     * Test can delete bank account with completed payouts.
     */
    public function test_can_delete_bank_account_with_completed_payouts(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->completed()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/wallet/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test deleting bank account requires authentication.
     */
    public function test_delete_bank_account_requires_authentication(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->deleteJson("/api/wallet/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(401);
    }

    // ==========================================
    // SET DEFAULT BANK ACCOUNT TESTS
    // ==========================================

    /**
     * Test setting bank account as default.
     */
    public function test_set_default_bank_account(): void
    {
        $firstAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->default()
            ->create();

        $secondAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/wallet/bank-accounts/{$secondAccount->id}/default");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Varsayılan hesap güncellendi.',
            ]);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'id' => $secondAccount->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('seller_bank_accounts', [
            'id' => $firstAccount->id,
            'is_default' => false,
        ]);
    }

    /**
     * Test user cannot set another users bank account as default.
     */
    public function test_user_cannot_set_another_users_bank_account_as_default(): void
    {
        $otherUser = User::factory()->seller()->create();
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($otherUser)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/wallet/bank-accounts/{$bankAccount->id}/default");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Bu hesabı düzenleme yetkiniz yok.',
            ]);
    }

    /**
     * Test set default requires authentication.
     */
    public function test_set_default_requires_authentication(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->postJson("/api/wallet/bank-accounts/{$bankAccount->id}/default");

        $response->assertStatus(401);
    }

    // ==========================================
    // PAYOUT REQUESTS LIST TESTS
    // ==========================================

    /**
     * Test getting payout requests returns empty for new user.
     */
    public function test_payout_requests_returns_empty_for_new_user(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/payout-requests');

        $response->assertStatus(200)
            ->assertJson([
                'payout_requests' => [],
            ]);
    }

    /**
     * Test getting payout requests with existing requests.
     */
    public function test_payout_requests_returns_request_list(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->pending()
            ->withAmount(1000)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->completed()
            ->withAmount(500)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/payout-requests');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'payout_requests');
    }

    /**
     * Test user cannot see other users payout requests.
     */
    public function test_user_cannot_see_other_users_payout_requests(): void
    {
        $otherUser = User::factory()->seller()->create();
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($otherUser)
            ->create();

        PayoutRequest::factory()
            ->forSeller($otherUser)
            ->forBankAccount($bankAccount)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/wallet/payout-requests');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'payout_requests');
    }

    /**
     * Test payout requests requires authentication.
     */
    public function test_payout_requests_requires_authentication(): void
    {
        $response = $this->getJson('/api/wallet/payout-requests');

        $response->assertStatus(401);
    }

    // ==========================================
    // CREATE PAYOUT REQUEST TESTS
    // ==========================================

    /**
     * Test creating payout request with valid data.
     */
    public function test_create_payout_request_with_valid_data(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(2000)
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 1000,
                'bank_account_id' => $bankAccount->id,
                'notes' => 'Test withdrawal',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ödeme talebi oluşturuldu.',
            ])
            ->assertJsonStructure([
                'payout_request' => [
                    'id',
                    'amount',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('payout_requests', [
            'seller_id' => $this->user->id,
            'bank_account_id' => $bankAccount->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);
    }

    /**
     * Test creating payout request without notes.
     */
    public function test_create_payout_request_without_notes(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(2000)
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 500,
                'bank_account_id' => $bankAccount->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test creating payout request fails with insufficient balance.
     */
    public function test_create_payout_request_fails_with_insufficient_balance(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(500)
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 1000,
                'bank_account_id' => $bankAccount->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);

        // Error message should mention insufficient balance
        $this->assertStringContainsString('Yetersiz bakiye', $response->json('error'));
    }

    /**
     * Test creating payout request fails with minimum amount validation.
     */
    public function test_create_payout_request_fails_with_amount_below_minimum(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(2000)
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 50, // Below minimum of 100
                'bank_account_id' => $bankAccount->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * Test creating payout request fails when pending request exists.
     */
    public function test_create_payout_request_fails_when_pending_request_exists(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(5000)
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->pending()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 1000,
                'bank_account_id' => $bankAccount->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Zaten bekleyen bir ödeme talebiniz var.',
            ]);
    }

    /**
     * Test creating payout request fails when approved request exists.
     */
    public function test_create_payout_request_fails_when_approved_request_exists(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(5000)
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->approved()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 1000,
                'bank_account_id' => $bankAccount->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test creating payout request fails when processing request exists.
     */
    public function test_create_payout_request_fails_when_processing_request_exists(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(5000)
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->processing()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 1000,
                'bank_account_id' => $bankAccount->id,
            ]);

        $response->assertStatus(400);
    }

    /**
     * Test can create payout request after previous one is completed.
     */
    public function test_can_create_payout_request_after_previous_completed(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(5000)
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->completed()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 1000,
                'bank_account_id' => $bankAccount->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test can create payout request after previous one is rejected.
     */
    public function test_can_create_payout_request_after_previous_rejected(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(5000)
            ->create();

        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        PayoutRequest::factory()
            ->forSeller($this->user)
            ->forBankAccount($bankAccount)
            ->rejected()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 1000,
                'bank_account_id' => $bankAccount->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test creating payout request fails with invalid bank account.
     */
    public function test_create_payout_request_fails_with_invalid_bank_account(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(2000)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 1000,
                'bank_account_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bank_account_id']);
    }

    /**
     * Test creating payout request fails with another users bank account.
     */
    public function test_create_payout_request_fails_with_another_users_bank_account(): void
    {
        SellerWallet::factory()
            ->forSeller($this->user)
            ->withBalance(2000)
            ->create();

        $otherUser = User::factory()->seller()->create();
        $otherBankAccount = SellerBankAccount::factory()
            ->forSeller($otherUser)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 1000,
                'bank_account_id' => $otherBankAccount->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Geçersiz banka hesabı.',
            ]);
    }

    /**
     * Test creating payout request requires authentication.
     */
    public function test_create_payout_request_requires_authentication(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->postJson('/api/wallet/payout-requests', [
            'amount' => 1000,
            'bank_account_id' => $bankAccount->id,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test creating payout request fails without required fields.
     */
    public function test_create_payout_request_fails_without_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'bank_account_id']);
    }

    /**
     * Test creating payout request fails with non-numeric amount.
     */
    public function test_create_payout_request_fails_with_non_numeric_amount(): void
    {
        $bankAccount = SellerBankAccount::factory()
            ->forSeller($this->user)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/wallet/payout-requests', [
                'amount' => 'invalid',
                'bank_account_id' => $bankAccount->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }
}
