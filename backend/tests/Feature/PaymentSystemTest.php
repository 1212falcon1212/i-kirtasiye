<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerWallet;
use App\Models\Setting;
use App\Models\SubOrder;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\FeeCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PaymentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected string $sellerToken;

    protected string $buyerToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear settings cache to ensure fresh reads
        Cache::flush();

        $this->seller = User::factory()->seller()->create();
        $this->buyer = User::factory()->create(['is_verified' => true]);

        $this->sellerToken = $this->seller->createToken('test-token')->plainTextToken;
        $this->buyerToken = $this->buyer->createToken('test-token')->plainTextToken;
    }

    /**
     * Helper: Create authenticated request headers for a given token.
     */
    protected function authHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    /**
     * Helper: Create a delivered, paid order with items belonging to a seller.
     *
     * Yeni komisyon formülü (T0 - 2026-05):
     *   commission_amount = totalPrice * %10 + (₺50 / sellerItemCount)
     *   withholding_tax   = netPrice (KDV hariç) * %1
     *   net_seller_amount = totalPrice - commission_amount - withholding_tax - shipping_cost_share
     *
     * Returns the order with fee-calculated items + a SubOrder per seller
     * (required by /api/seller/orders/{id} endpoint).
     */
    protected function createDeliveredOrderWithFees(
        User $buyer,
        User $seller,
        float $unitPrice = 1000.00,
        int $quantity = 1,
        float $shippingCost = 0,
        ?string $subOrderStatus = null
    ): Order {
        $category = Category::factory()->withCommissionRate(10)->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice($unitPrice)
            ->withStock(100)
            ->available()
            ->create();

        $totalPrice = $unitPrice * $quantity;

        // Calculate fees using the service. Sabit ₺50 hizmet bedeli tek bir
        // kalem olduğu için tamamı bu kaleme düşer.
        $feeService = app(FeeCalculationService::class);
        $fees = $feeService->calculateFees(
            totalPrice: $totalPrice,
            flatFeeShare: 50.0,
            shippingCostShare: $shippingCost,
        );

        $order = Order::factory()
            ->forUser($buyer)
            ->delivered()
            ->paid()
            ->create([
                'subtotal' => $totalPrice,
                'total_commission' => $fees['service_fee_amount'],
                'total_amount' => $totalPrice + $shippingCost,
                'shipping_cost' => $shippingCost,
            ]);

        $subOrder = SubOrder::factory()
            ->forOrder($order)
            ->forSeller($seller)
            ->create([
                'status' => $subOrderStatus ?? 'delivered',
                'subtotal' => $totalPrice,
                'total_commission' => $fees['service_fee_amount'],
                'total_payout' => $fees['net_seller_amount'],
                'shipped_at' => now()->subDays(3),
                'delivered_at' => now(),
            ]);

        OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($seller)
            ->create([
                'sub_order_id' => $subOrder->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'commission_rate' => $fees['commission_rate'],
                'commission_amount' => $fees['service_fee_amount'],
                'marketplace_fee' => $fees['marketplace_fee'],
                'withholding_tax' => $fees['withholding_tax'],
                'shipping_cost_share' => $fees['shipping_cost_share'],
                'net_seller_amount' => $fees['net_seller_amount'],
                'seller_payout_amount' => $fees['net_seller_amount'],
            ]);

        return $order->fresh(['items', 'subOrders']);
    }

    /**
     * Helper: Create a paid + shipped order (with sub_order) for status
     * transition tests. Status update endpoint operates on sub_orders so we
     * have to wire one up.
     */
    protected function createPendingPaidOrder(
        User $buyer,
        User $seller,
        float $unitPrice = 1000.00,
        int $quantity = 1
    ): Order {
        $category = Category::factory()->withCommissionRate(10)->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice($unitPrice)
            ->withStock(100)
            ->available()
            ->create();

        $totalPrice = $unitPrice * $quantity;

        $feeService = app(FeeCalculationService::class);
        $fees = $feeService->calculateFees(
            totalPrice: $totalPrice,
            flatFeeShare: 50.0,
        );

        $order = Order::factory()
            ->forUser($buyer)
            ->paid()
            ->create([
                'status' => 'shipped',
                'shipped_at' => now(),
                'subtotal' => $totalPrice,
                'total_commission' => $fees['service_fee_amount'],
                'total_amount' => $totalPrice,
                'shipping_cost' => 0,
            ]);

        $subOrder = SubOrder::factory()
            ->forOrder($order)
            ->forSeller($seller)
            ->create([
                'status' => 'shipped',
                'subtotal' => $totalPrice,
                'total_commission' => $fees['service_fee_amount'],
                'total_payout' => $fees['net_seller_amount'],
                'shipped_at' => now(),
            ]);

        OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($seller)
            ->create([
                'sub_order_id' => $subOrder->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'commission_rate' => $fees['commission_rate'],
                'commission_amount' => $fees['service_fee_amount'],
                'marketplace_fee' => $fees['marketplace_fee'],
                'withholding_tax' => $fees['withholding_tax'],
                'shipping_cost_share' => 0,
                'net_seller_amount' => $fees['net_seller_amount'],
                'seller_payout_amount' => $fees['net_seller_amount'],
            ]);

        return $order->fresh(['items', 'subOrders']);
    }

    // ==========================================
    // SECTION 1: PAYOUT DAY PROCESSING TESTS
    // ==========================================

    /**
     * Test that payout settings have correct default values when not configured.
     */
    public function test_payout_settings_have_correct_defaults(): void
    {
        // Arrange: No settings in DB, defaults should be used.

        // Act
        $payoutDay = (int) Setting::getValue('payment.payout_day', 15);
        $payoutMinAmount = (float) Setting::getValue('payment.payout_min_amount', 100);
        $withholdingRate = (float) Setting::getValue('payment.withholding_rate', 1);

        // Assert
        $this->assertEquals(15, $payoutDay);
        $this->assertEquals(100.0, $payoutMinAmount);
        $this->assertEquals(1.0, $withholdingRate);
    }

    /**
     * Test that custom payout settings are read correctly from the database.
     */
    public function test_custom_payout_settings_are_read_from_database(): void
    {
        // Arrange
        Setting::setValue('payment.payout_day', '20', 'payment', 'string');
        Setting::setValue('payment.payout_min_amount', '250', 'payment', 'string');
        Setting::setValue('payment.withholding_rate', '2.5', 'payment', 'string');
        Cache::flush();

        // Act
        $payoutDay = (int) Setting::getValue('payment.payout_day', 15);
        $payoutMinAmount = (float) Setting::getValue('payment.payout_min_amount', 100);
        $withholdingRate = (float) Setting::getValue('payment.withholding_rate', 1);

        // Assert
        $this->assertEquals(20, $payoutDay);
        $this->assertEquals(250.0, $payoutMinAmount);
        $this->assertEquals(2.5, $withholdingRate);
    }

    /**
     * Test next payout date is calculated as current month when payout day has not passed.
     */
    public function test_next_payout_date_is_current_month_when_day_not_passed(): void
    {
        // Arrange: Set payout day to 25, freeze time to the 10th
        Setting::setValue('payment.payout_day', '25', 'payment', 'string');
        Cache::flush();

        SellerWallet::factory()->forSeller($this->seller)->withBalance(500)->create();

        Carbon::setTestNow(Carbon::create(2026, 2, 10, 12, 0, 0));

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals('2026-02-25', $response->json('payout_estimate.next_payout_date'));

        Carbon::setTestNow();
    }

    /**
     * Test next payout date rolls to next month when payout day has already passed.
     */
    public function test_next_payout_date_rolls_to_next_month_when_day_passed(): void
    {
        // Arrange: Set payout day to 10, freeze time to the 15th
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 12, 0, 0));

        Setting::setValue('payment.payout_day', '10', 'payment', 'string');
        Cache::flush();

        SellerWallet::factory()->forSeller($this->seller)->withBalance(500)->create();

        // Re-create token after time change to avoid Sanctum expiry issues
        $token = $this->seller->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals('2026-03-10', $response->json('payout_estimate.next_payout_date'));

        Carbon::setTestNow();
    }

    /**
     * Test seller is eligible for payout when net amount meets minimum.
     */
    public function test_seller_eligible_for_payout_when_net_meets_minimum(): void
    {
        // Arrange: Balance = 500, withholding 1% = 5, net = 495, min = 100
        SellerWallet::factory()->forSeller($this->seller)->withBalance(500)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('payout_estimate.is_eligible', true)
            ->assertJsonPath('payout_estimate.available_balance', '500.00');

        $netPayout = $response->json('payout_estimate.net_payout_amount');
        $this->assertEquals(495.0, (float) $netPayout);
    }

    /**
     * Test seller is not eligible for payout when net amount is below minimum.
     */
    public function test_seller_not_eligible_when_net_below_minimum(): void
    {
        // Arrange: Balance = 50, withholding 1% = 0.50, net = 49.50, min = 100
        SellerWallet::factory()->forSeller($this->seller)->withBalance(50)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('payout_estimate.is_eligible', false);
    }

    /**
     * Test seller is not eligible when balance is exactly zero.
     */
    public function test_seller_not_eligible_with_zero_balance(): void
    {
        // Arrange
        SellerWallet::factory()->forSeller($this->seller)->withBalance(0)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('payout_estimate.is_eligible', false);

        $netPayout = $response->json('payout_estimate.net_payout_amount');
        $this->assertEquals(0, (float) $netPayout);
    }

    /**
     * Test payout eligibility with custom minimum amount setting.
     */
    public function test_payout_eligibility_respects_custom_min_amount(): void
    {
        // Arrange: Set minimum to 500, balance = 400 -> net 396 (1% withholding)
        Setting::setValue('payment.payout_min_amount', '500', 'payment', 'string');
        Cache::flush();

        SellerWallet::factory()->forSeller($this->seller)->withBalance(400)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('payout_estimate.is_eligible', false);

        $minAmount = $response->json('payout_estimate.min_amount');
        $this->assertEquals(500, (float) $minAmount);
    }

    /**
     * Test payout withholding amount calculation with custom rate.
     */
    public function test_payout_withholding_calculated_with_custom_rate(): void
    {
        // Arrange: Balance = 1000, withholding rate = 5%
        Setting::setValue('payment.withholding_rate', '5', 'payment', 'string');
        Cache::flush();

        SellerWallet::factory()->forSeller($this->seller)->withBalance(1000)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert: withholding = 1000 * 5% = 50, net = 950
        $response->assertStatus(200);

        $estimate = $response->json('payout_estimate');
        $this->assertEquals(5, (float) $estimate['withholding_rate']);
        $this->assertEquals(50.0, (float) $estimate['withholding_amount']);
        $this->assertEquals(950.0, (float) $estimate['net_payout_amount']);
    }

    /**
     * Test buyer-confirmed delivery triggers wallet earnings addition.
     *
     * Akış: seller mark as delivered -> buyer confirmDelivery -> wallet earnings.
     * Yalnızca alıcı teslimatı onayladığında satıcının cüzdanına gelir yazılır
     * (processSubOrderEarnings yalnızca confirmDelivery yolunda çağrılır).
     */
    public function test_order_delivery_triggers_wallet_earnings(): void
    {
        // Arrange: Create a shipped, paid order with sub_order
        $order = $this->createPendingPaidOrder($this->buyer, $this->seller, 1000.00, 1);

        // Act 1: Seller marks the sub_order as delivered.
        $statusResponse = $this->actingAs($this->seller, 'sanctum')
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'delivered',
            ]);
        $statusResponse->assertStatus(200);

        // Act 2: Buyer confirms delivery -> earnings flow runs.
        $confirmResponse = $this->actingAs($this->buyer, 'sanctum')
            ->putJson("/api/orders/{$order->id}/confirm-delivery");
        $confirmResponse->assertStatus(200);

        // Wallet should now have the earnings
        $wallet = SellerWallet::where('seller_id', $this->seller->id)->first();
        $this->assertNotNull($wallet);

        // Transactions should exist
        $transactions = WalletTransaction::where('wallet_id', $wallet->id)->get();
        $this->assertGreaterThanOrEqual(2, $transactions->count());

        // Sale transaction should exist
        $saleTransaction = $transactions->where('type', WalletTransaction::TYPE_SALE)->first();
        $this->assertNotNull($saleTransaction);
        $this->assertEquals(1000.00, (float) $saleTransaction->amount);
        $this->assertEquals(WalletTransaction::DIRECTION_CREDIT, $saleTransaction->direction);

        // Commission transaction should exist
        $commissionTransaction = $transactions->where('type', WalletTransaction::TYPE_COMMISSION)->first();
        $this->assertNotNull($commissionTransaction);
        $this->assertGreaterThan(0, (float) $commissionTransaction->amount);
        $this->assertEquals(WalletTransaction::DIRECTION_DEBIT, $commissionTransaction->direction);
    }

    /**
     * Test order delivery does NOT trigger wallet earnings when payment is not paid.
     */
    public function test_order_delivery_does_not_trigger_earnings_when_not_paid(): void
    {
        // Arrange: Create a shipped order that is NOT paid (with a sub_order
        // since updateStatus endpoint operates on sub_order level).
        $category = Category::factory()->withCommissionRate(10)->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($this->seller)
            ->withPrice(500.00)
            ->withStock(100)
            ->available()
            ->create();

        $order = Order::factory()
            ->forUser($this->buyer)
            ->create([
                'status' => 'shipped',
                'payment_status' => 'pending', // NOT paid
                'shipped_at' => now(),
                'subtotal' => 500,
                'total_amount' => 500,
                'total_commission' => 50,
            ]);

        $subOrder = SubOrder::factory()
            ->forOrder($order)
            ->forSeller($this->seller)
            ->create([
                'status' => 'shipped',
                'subtotal' => 500,
                'total_commission' => 50,
                'total_payout' => 450,
                'shipped_at' => now(),
            ]);

        OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($this->seller)
            ->withQuantityAndPrice(1, 500.00)
            ->create([
                'sub_order_id' => $subOrder->id,
            ]);

        // Act: Deliver the order
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'delivered',
            ]);

        // Assert
        $response->assertStatus(200);

        // Wallet should NOT have any earnings
        $wallet = SellerWallet::where('seller_id', $this->seller->id)->first();
        if ($wallet) {
            $this->assertEquals(0, (float) $wallet->balance);
            $this->assertEquals(0, (float) $wallet->pending_balance);
        }

        // No wallet transactions should be created
        $transactionCount = WalletTransaction::whereHas('wallet', function ($q) {
            $q->where('seller_id', $this->seller->id);
        })->count();
        $this->assertEquals(0, $transactionCount);
    }

    /**
     * Test wallet balance reflects correctly after multiple orders delivered.
     *
     * Her satıcı teslim attıktan sonra alıcının onayı ile cüzdana yansır.
     */
    public function test_wallet_balance_accumulates_from_multiple_orders(): void
    {
        // Arrange & Act: Deliver + confirm two orders
        $order1 = $this->createPendingPaidOrder($this->buyer, $this->seller, 500.00, 1);
        $this->actingAs($this->seller, 'sanctum')
            ->putJson("/api/orders/{$order1->id}/status", ['status' => 'delivered']);
        $this->actingAs($this->buyer, 'sanctum')
            ->putJson("/api/orders/{$order1->id}/confirm-delivery");

        $order2 = $this->createPendingPaidOrder($this->buyer, $this->seller, 700.00, 1);
        $this->actingAs($this->seller, 'sanctum')
            ->putJson("/api/orders/{$order2->id}/status", ['status' => 'delivered']);
        $this->actingAs($this->buyer, 'sanctum')
            ->putJson("/api/orders/{$order2->id}/confirm-delivery");

        // Assert: Wallet should have accumulated earnings
        $wallet = SellerWallet::where('seller_id', $this->seller->id)->first();
        $this->assertNotNull($wallet);

        // Cüzdan toplam (released + pending) iki siparişin net tutarını yansıtmalı.
        // total_earned addOrderEarnings içinde kümülatif olarak güncelleniyor.
        $this->assertGreaterThan(500, (float) $wallet->total_earned);
    }

    /**
     * Test wallet payout estimate endpoint returns correct structure.
     */
    public function test_wallet_endpoint_returns_payout_estimate_structure(): void
    {
        // Arrange
        SellerWallet::factory()->forSeller($this->seller)->withBalance(2000)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
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
                'payout_estimate' => [
                    'next_payout_date',
                    'next_payout_formatted',
                    'available_balance',
                    'withholding_rate',
                    'withholding_amount',
                    'net_payout_amount',
                    'min_amount',
                    'is_eligible',
                ],
            ]);
    }

    /**
     * Test wallet endpoint requires authentication.
     */
    public function test_wallet_endpoint_requires_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/wallet');

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // SECTION 2: FINANCIAL DETAILS VISIBILITY
    // ==========================================

    // ------------------------------------------
    // 2A: Seller Order Detail Financial Visibility
    // ------------------------------------------

    /**
     * Test seller order detail returns financials object with all required fields.
     */
    public function test_seller_order_detail_returns_financials_object(): void
    {
        // Arrange
        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'financials' => [
                        'subtotal' => ['label', 'value', 'formatted'],
                        'deductions',
                        'total_deductions' => ['label', 'value', 'formatted'],
                        'net_amount' => ['label', 'value', 'formatted'],
                    ],
                    'items',
                    'buyer',
                ],
            ]);
    }

    /**
     * Test seller order detail financials subtotal matches sum of item prices.
     */
    public function test_seller_order_detail_subtotal_matches_items_total(): void
    {
        // Arrange
        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 500.00, 2);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(200);

        $financials = $response->json('data.financials');
        $this->assertEquals(1000.00, $financials['subtotal']['value']);
    }

    /**
     * Test seller order detail deductions array contains expected items.
     *
     * Default fee_mode='combined' -> SellerController kesinti label'ı
     * "Komisyon" döner. Pazaryeri ek hizmet bedeli "marketplace_fee_enabled"
     * pasif olduğu için 0 değerle dönse de kalemler dizisinde mevcuttur.
     * Stopaj her zaman görünür. Kargo payı 0 -> visible=false.
     */
    public function test_seller_order_detail_deductions_contain_expected_items(): void
    {
        // Arrange
        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(200);

        $deductions = $response->json('data.financials.deductions');
        $this->assertIsArray($deductions);

        // Extract labels
        $labels = array_column($deductions, 'label');

        // Yeni varsayılan combined modunda komisyon kalemi "Komisyon" label'ıyla döner.
        $this->assertContains('Komisyon', $labels);
        $this->assertContains('Pazaryeri Hizmet Bedeli', $labels);
        $this->assertContains('Stopaj', $labels);

        // Kargo Payı - label'ı içeren bir kalem olmalı (visible flag ayrı kontrol).
        $hasKargoPay = false;
        foreach ($labels as $label) {
            if (str_contains($label, 'Kargo Pay')) {
                $hasKargoPay = true;
                break;
            }
        }
        $this->assertTrue($hasKargoPay, 'Deductions should contain Kargo Payi label');
    }

    /**
     * Test seller order detail net amount is positive and less than subtotal.
     */
    public function test_seller_order_detail_net_amount_is_correct(): void
    {
        // Arrange
        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(200);

        $subtotal = $response->json('data.financials.subtotal.value');
        $netAmount = $response->json('data.financials.net_amount.value');
        $totalDeductions = $response->json('data.financials.total_deductions.value');

        $this->assertGreaterThan(0, $netAmount);
        $this->assertLessThan($subtotal, $netAmount);
        $this->assertGreaterThan(0, $totalDeductions);

        // Net = Subtotal - Total Deductions (approximately, due to rounding)
        $this->assertEqualsWithDelta($subtotal - $totalDeductions, $netAmount, 0.02);
    }

    /**
     * Test seller order detail withholding tax deduction is calculated correctly.
     *
     * Yeni formül (T0 - 2026-05): stopaj KDV HARİÇ tutar üzerinden hesaplanır.
     * 1000 TL KDV dahil, KDV %20 (config default) -> netPrice = 833.33,
     * %1 stopaj = 8.33 TL.
     */
    public function test_seller_order_detail_withholding_tax_correct(): void
    {
        // Arrange
        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(200);

        $deductions = $response->json('data.financials.deductions');
        $stopajDeduction = collect($deductions)->firstWhere('label', 'Stopaj');

        $this->assertNotNull($stopajDeduction);
        // KDV hariç stopaj: 1000 / 1.20 * 0.01 = 8.33
        $this->assertEqualsWithDelta(8.33, (float) $stopajDeduction['value'], 0.02);
    }

    /**
     * Test seller cannot access another seller's order detail.
     */
    public function test_seller_cannot_access_other_sellers_order_detail(): void
    {
        // Arrange
        $otherSeller = User::factory()->seller()->create();
        $order = $this->createDeliveredOrderWithFees($this->buyer, $otherSeller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert: Should return 403 (no items for this seller in the order)
        $response->assertStatus(403);
    }

    /**
     * Test seller order detail returns 404 for non-existent order.
     */
    public function test_seller_order_detail_returns_404_for_nonexistent_order(): void
    {
        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/seller/orders/99999');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test seller order detail requires authentication.
     */
    public function test_seller_order_detail_requires_authentication(): void
    {
        // Arrange
        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test seller order detail deduction values are properly formatted.
     */
    public function test_seller_order_detail_deductions_have_formatted_values(): void
    {
        // Arrange
        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(200);

        $deductions = $response->json('data.financials.deductions');
        foreach ($deductions as $deduction) {
            $this->assertArrayHasKey('label', $deduction);
            $this->assertArrayHasKey('value', $deduction);
            $this->assertArrayHasKey('formatted', $deduction);
            // Formatted should start with "-" (deduction)
            $this->assertStringStartsWith('-', $deduction['formatted']);
        }
    }

    // ------------------------------------------
    // 2B: Buyer/Admin Order Detail with Commission Details
    // ------------------------------------------

    /**
     * Test buyer order detail (GET /api/orders/{id}) shows items with commission fields.
     */
    public function test_buyer_order_detail_shows_items_with_financial_fields(): void
    {
        // Arrange
        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->buyerToken))
            ->getJson("/api/orders/{$order->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'order' => [
                    'id',
                    'order_number',
                    'subtotal',
                    'total_amount',
                    'total_commission',
                ],
                'items',
                'items_by_seller',
                'is_buyer',
                'is_seller',
            ])
            ->assertJsonPath('is_buyer', true);

        // Items should have commission details
        $items = $response->json('items');
        $this->assertNotEmpty($items);

        $firstItem = $items[0];
        $this->assertArrayHasKey('commission_rate', $firstItem);
        $this->assertArrayHasKey('commission_amount', $firstItem);
        $this->assertArrayHasKey('net_seller_amount', $firstItem);
        $this->assertArrayHasKey('seller_payout_amount', $firstItem);
    }

    /**
     * Test admin can view any order with full financial details.
     */
    public function test_admin_can_view_any_order_with_financial_details(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => 'super-admin',
            'is_verified' => true,
        ]);
        $adminToken = $admin->createToken('test-token')->plainTextToken;

        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($adminToken))
            ->getJson("/api/orders/{$order->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'order' => [
                    'subtotal',
                    'total_amount',
                    'total_commission',
                ],
                'items',
                'items_by_seller',
            ]);

        // items_by_seller should have commission/payout data
        $itemsBySeller = $response->json('items_by_seller');
        $this->assertNotEmpty($itemsBySeller);

        $firstGroup = $itemsBySeller[0];
        $this->assertArrayHasKey('subtotal', $firstGroup);
        $this->assertArrayHasKey('commission', $firstGroup);
        $this->assertArrayHasKey('payout', $firstGroup);
    }

    /**
     * Test items_by_seller groups items correctly for multi-seller orders.
     */
    public function test_order_detail_groups_items_by_seller(): void
    {
        // Arrange: Order with two different sellers
        $seller2 = User::factory()->seller()->create();
        $category = Category::factory()->withCommissionRate(10)->create();

        $order = Order::factory()->forUser($this->buyer)->delivered()->paid()->create([
            'subtotal' => 2000,
            'total_amount' => 2000,
        ]);

        // Items for seller 1
        $product1 = Product::factory()->forCategory($category)->create();
        $offer1 = Offer::factory()->forProduct($product1)->forSeller($this->seller)->available()->create();
        OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer1)
            ->forSeller($this->seller)
            ->withQuantityAndPrice(1, 1000.00)
            ->create();

        // Items for seller 2
        $product2 = Product::factory()->forCategory($category)->create();
        $offer2 = Offer::factory()->forProduct($product2)->forSeller($seller2)->available()->create();
        OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer2)
            ->forSeller($seller2)
            ->withQuantityAndPrice(1, 1000.00)
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->buyerToken))
            ->getJson("/api/orders/{$order->id}");

        // Assert
        $response->assertStatus(200);

        $itemsBySeller = $response->json('items_by_seller');
        $this->assertCount(2, $itemsBySeller);
    }

    /**
     * Test unrelated user cannot view order financial details.
     *
     * OrderPolicy::view false -> Laravel authorize() throws 403.
     */
    public function test_unrelated_user_cannot_view_order_financial_details(): void
    {
        // Arrange
        $unrelatedUser = User::factory()->create(['is_verified' => true]);
        $unrelatedToken = $unrelatedUser->createToken('test-token')->plainTextToken;

        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($unrelatedToken))
            ->getJson("/api/orders/{$order->id}");

        // Assert: OrderPolicy::view returns false for unrelated retailer -> 403.
        $response->assertStatus(403);
    }

    // ------------------------------------------
    // 2C: Wallet Endpoint Financial Visibility
    // ------------------------------------------

    /**
     * Test wallet endpoint payout estimate gross/withholding/net calculations.
     */
    public function test_wallet_payout_estimate_calculations_are_correct(): void
    {
        // Arrange: Balance = 2000, default withholding = 1%
        SellerWallet::factory()->forSeller($this->seller)->withBalance(2000)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200);

        $estimate = $response->json('payout_estimate');

        // Gross (available balance)
        $this->assertEquals(2000.00, (float) $estimate['available_balance']);

        // Withholding: 2000 * 1% = 20
        $this->assertEquals(20.00, (float) $estimate['withholding_amount']);

        // Net: 2000 - 20 = 1980
        $this->assertEquals(1980.00, (float) $estimate['net_payout_amount']);

        // Should be eligible (1980 >= 100)
        $this->assertTrue($estimate['is_eligible']);
    }

    /**
     * Test wallet endpoint returns correct date format for next payout.
     */
    public function test_wallet_payout_date_format(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2026, 3, 1, 12, 0, 0));

        SellerWallet::factory()->forSeller($this->seller)->withBalance(500)->create();

        // Re-create token after time change
        $token = $this->seller->createToken('test-token-date')->plainTextToken;

        // Act
        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200);

        $estimate = $response->json('payout_estimate');

        // next_payout_date should be ISO format (Y-m-d)
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $estimate['next_payout_date']);

        // next_payout_formatted should be d.m.Y format
        $this->assertMatchesRegularExpression('/^\d{2}\.\d{2}\.\d{4}$/', $estimate['next_payout_formatted']);

        Carbon::setTestNow();
    }

    /**
     * Test wallet endpoint with zero withholding rate.
     */
    public function test_wallet_payout_with_zero_withholding(): void
    {
        // Arrange
        Setting::setValue('payment.withholding_rate', '0', 'payment', 'string');
        Cache::flush();

        SellerWallet::factory()->forSeller($this->seller)->withBalance(1000)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert: No withholding, net = gross
        $response->assertStatus(200);

        $estimate = $response->json('payout_estimate');
        $this->assertEquals(0, (float) $estimate['withholding_rate']);
        $this->assertEquals(0, (float) $estimate['withholding_amount']);
        $this->assertEquals(1000, (float) $estimate['net_payout_amount']);
    }

    // ------------------------------------------
    // 2D: Fee Calculation Service Tests
    // ------------------------------------------

    /**
     * Test FeeCalculationService calculates correct fees with default rates.
     *
     * Yeni komisyon formülü (T0 - 2026-05):
     *   commission_rate         = %10 (Setting::commission.commission_percentage)
     *   commission_amount       = 1000 * %10 = 100
     *   flat_service_fee (pay)  = ₺50 (tek satıcı, tek kalem -> tamamı bu kaleme)
     *   service_fee_amount      = commission + flat = 150
     *   netPrice (KDV %20 ayrış)= 1000 / 1.20 = 833.33
     *   withholding_tax         = 833.33 * %1 = 8.33
     *   total_fees              = 150 + 8.33 + 0 (kargo) = 158.33
     *   net_seller_amount       = 1000 - 158.33 = 841.67
     */
    public function test_fee_calculation_service_default_rates(): void
    {
        // Arrange
        $feeService = app(FeeCalculationService::class);

        // Act: Calculate fees for 1000 TL with full ₺50 flat fee share.
        $fees = $feeService->calculateFees(
            totalPrice: 1000.00,
            flatFeeShare: 50.0,
        );

        // Assert
        // Yüzdelik komisyon
        $this->assertEqualsWithDelta(100.00, $fees['commission_amount'], 0.01);
        $this->assertEqualsWithDelta(10.00, $fees['commission_rate'], 0.01);

        // Sabit hizmet bedeli payı
        $this->assertEqualsWithDelta(50.00, $fees['flat_service_fee'], 0.01);

        // service_fee_amount = commission + flat
        $this->assertEqualsWithDelta(150.00, $fees['service_fee_amount'], 0.01);

        // Stopaj KDV hariç tutar üzerinden: 1000/1.20 = 833.33 → %1 = 8.33
        $this->assertEqualsWithDelta(8.33, $fees['withholding_tax'], 0.02);

        // Total fees: 150 + 8.33 + 0 = 158.33
        $this->assertEqualsWithDelta(158.33, $fees['total_fees'], 0.02);

        // Net seller amount: 1000 - 158.33 = 841.67
        $this->assertEqualsWithDelta(841.67, $fees['net_seller_amount'], 0.02);
    }

    /**
     * Test FeeCalculationService includes shipping cost share in deductions.
     *
     * 1000 TL satış + ₺50 sabit hizmet bedeli + ₺25 kargo payı:
     *   commission        = 100
     *   flat_service_fee  = 50
     *   service_fee_amount= 150
     *   withholding (KDV%20 ayrış) = 1000/1.20 * %1 = 8.33
     *   total_fees        = 150 + 8.33 + 25 = 183.33
     *   net_seller_amount = 1000 - 183.33 = 816.67
     */
    public function test_fee_calculation_includes_shipping_cost(): void
    {
        // Arrange
        $feeService = app(FeeCalculationService::class);

        // Act
        $fees = $feeService->calculateFees(
            totalPrice: 1000.00,
            flatFeeShare: 50.0,
            shippingCostShare: 25.00,
        );

        // Assert
        $this->assertEqualsWithDelta(25.00, $fees['shipping_cost_share'], 0.01);
        $this->assertEqualsWithDelta(183.33, $fees['total_fees'], 0.02);
        $this->assertEqualsWithDelta(816.67, $fees['net_seller_amount'], 0.02);
    }

    /**
     * Test FeeCalculationService with zero total price.
     */
    public function test_fee_calculation_with_zero_price(): void
    {
        // Arrange
        $feeService = app(FeeCalculationService::class);

        // Act: 0 TL + 0 flat fee share -> hiçbir kesinti olmamalı.
        $fees = $feeService->calculateFees(0);

        // Assert: All fees should be zero
        $this->assertEquals(0, $fees['service_fee_amount']);
        $this->assertEquals(0, $fees['commission_amount']);
        $this->assertEquals(0, $fees['withholding_tax']);
        $this->assertEquals(0, $fees['total_fees']);
        $this->assertEquals(0, $fees['net_seller_amount']);
    }

    /**
     * Test FeeCalculationService applies fees to an order item correctly.
     *
     * Yeni davranışta commission_rate %10 olmalı (varsayılan combined mode).
     * commission_amount = yüzdelik komisyon + sabit hizmet bedeli payı
     * (applyFeesToOrderItem kolonları bu birleşik tutarı yazıyor).
     */
    public function test_fee_calculation_applies_to_order_item(): void
    {
        // Arrange
        $feeService = app(FeeCalculationService::class);

        $category = Category::factory()->withCommissionRate(10)->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()->forProduct($product)->forSeller($this->seller)->available()->create();

        $order = Order::factory()->forUser($this->buyer)->create([
            'subtotal' => 1000,
            'total_amount' => 1000,
        ]);

        $orderItem = OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($this->seller)
            ->create([
                'quantity' => 1,
                'unit_price' => 1000,
                'total_price' => 1000,
                'commission_rate' => 0,
                'commission_amount' => 0,
                'net_seller_amount' => 1000,
                'seller_payout_amount' => 1000,
            ]);

        // Act: tek satıcı tek kalem -> ₺50 sabit bedel tamamı bu kaleme düşer.
        $feeService->applyFeesToOrderItem($orderItem, flatFeeShare: 50.0);
        $orderItem->refresh();

        // Assert
        $this->assertEqualsWithDelta(10.00, (float) $orderItem->commission_rate, 0.01);
        // commission_amount = yüzdelik (100) + sabit (50) = 150
        $this->assertEqualsWithDelta(150.00, (float) $orderItem->commission_amount, 0.01);
        $this->assertGreaterThan(0, (float) $orderItem->withholding_tax);
        $this->assertLessThan(1000, (float) $orderItem->net_seller_amount);
        $this->assertEquals($orderItem->net_seller_amount, $orderItem->seller_payout_amount);
    }

    /**
     * Test seller financial visibility shows correct data for multi-item order.
     *
     * 3 kalem aynı satıcı -> ₺50 sabit hizmet bedeli üç kaleme eşit bölünür
     * (her kalem 50/3 ≈ 16.67). commission_amount kolonu bu birleşik kesintiyi
     * tutar (yüzdelik 100 + sabit pay 16.67 ≈ 116.67).
     */
    public function test_seller_order_detail_with_multiple_items(): void
    {
        // Arrange: Create order with 3 items for the same seller
        $category = Category::factory()->withCommissionRate(10)->create();

        $order = Order::factory()
            ->forUser($this->buyer)
            ->delivered()
            ->paid()
            ->create([
                'subtotal' => 3000,
                'total_amount' => 3000,
            ]);

        $subOrder = SubOrder::factory()
            ->forOrder($order)
            ->forSeller($this->seller)
            ->create([
                'status' => 'delivered',
                'subtotal' => 3000,
                'shipped_at' => now()->subDays(3),
                'delivered_at' => now(),
            ]);

        $feeService = app(FeeCalculationService::class);

        for ($i = 0; $i < 3; $i++) {
            $product = Product::factory()->forCategory($category)->create();
            $offer = Offer::factory()
                ->forProduct($product)
                ->forSeller($this->seller)
                ->withPrice(1000)
                ->withStock(100)
                ->available()
                ->create();

            // Sabit ₺50 üç kaleme bölündü: her kaleme 50/3 ≈ 16.67 düşer.
            $fees = $feeService->calculateFees(
                totalPrice: 1000.00,
                flatFeeShare: 50.0 / 3,
            );

            OrderItem::factory()
                ->forOrder($order)
                ->forOffer($offer)
                ->forSeller($this->seller)
                ->create([
                    'sub_order_id' => $subOrder->id,
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'total_price' => 1000,
                    'commission_rate' => $fees['commission_rate'],
                    'commission_amount' => $fees['service_fee_amount'],
                    'marketplace_fee' => $fees['marketplace_fee'],
                    'withholding_tax' => $fees['withholding_tax'],
                    'shipping_cost_share' => 0,
                    'net_seller_amount' => $fees['net_seller_amount'],
                    'seller_payout_amount' => $fees['net_seller_amount'],
                ]);
        }

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(200);

        $financials = $response->json('data.financials');
        $items = $response->json('data.items');

        // Subtotal should be 3000
        $this->assertEquals(3000.00, $financials['subtotal']['value']);

        // Should have 3 items
        $this->assertCount(3, $items);

        // Net amount should be positive and less than 3000
        $this->assertGreaterThan(0, $financials['net_amount']['value']);
        $this->assertLessThan(3000, $financials['net_amount']['value']);
    }

    /**
     * Test wallet balance and pending balance are shown separately in wallet endpoint.
     */
    public function test_wallet_shows_balance_and_pending_separately(): void
    {
        // Arrange
        SellerWallet::factory()
            ->forSeller($this->seller)
            ->withBalance(1500)
            ->withPendingBalance(500)
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'wallet' => [
                    'balance' => '1500.00',
                    'pending_balance' => '500.00',
                ],
            ]);

        // Payout estimate should only be based on available balance
        $estimate = $response->json('payout_estimate');
        $this->assertEquals(1500.00, (float) $estimate['available_balance']);
    }

    /**
     * Test seller order detail shows item-level financial details.
     */
    public function test_seller_order_detail_items_have_financial_fields(): void
    {
        // Arrange
        $order = $this->createDeliveredOrderWithFees($this->buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertNotEmpty($items);

        $firstItem = $items[0];
        $this->assertArrayHasKey('quantity', $firstItem);
        $this->assertArrayHasKey('unit_price', $firstItem);
        $this->assertArrayHasKey('total_price', $firstItem);
        $this->assertEquals(1000.00, $firstItem['total_price']);
    }

    /**
     * Test seller order detail shows buyer information.
     */
    public function test_seller_order_detail_shows_buyer_info(): void
    {
        // Arrange
        $buyer = User::factory()->create([
            'is_verified' => true,
            'business_name' => 'Test Kırtasiye',
            'email' => 'buyer@test.com',
            'city' => 'Istanbul',
        ]);
        $order = $this->createDeliveredOrderWithFees($buyer, $this->seller, 1000.00);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson("/api/seller/orders/{$order->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.buyer.email', 'buyer@test.com');
    }

    // ------------------------------------------
    // 2E: Edge Cases
    // ------------------------------------------

    /**
     * Test payout estimate for seller with only pending balance (no available).
     */
    public function test_payout_estimate_with_only_pending_balance(): void
    {
        // Arrange: Only pending balance, no available
        SellerWallet::factory()
            ->forSeller($this->seller)
            ->create([
                'balance' => 0,
                'pending_balance' => 5000,
                'total_earned' => 5000,
            ]);

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('payout_estimate.available_balance', '0.00')
            ->assertJsonPath('payout_estimate.is_eligible', false);
    }

    /**
     * Test payout estimate at exact minimum boundary.
     */
    public function test_payout_eligibility_at_exact_minimum_boundary(): void
    {
        // Arrange: After 1% withholding from 101.01, net ~= 100.0
        // Balance needs to be slightly above 100 / 0.99 = ~101.0101
        SellerWallet::factory()
            ->forSeller($this->seller)
            ->withBalance(101.01)
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200);

        $estimate = $response->json('payout_estimate');
        // 101.01 * 0.99 = 99.9999 which is just below 100
        // So this should NOT be eligible
        $this->assertFalse($estimate['is_eligible']);
    }

    /**
     * Test payout estimate just above minimum boundary.
     */
    public function test_payout_eligibility_just_above_minimum(): void
    {
        // Arrange: Balance = 200, net = 198, min = 100 -> eligible
        SellerWallet::factory()
            ->forSeller($this->seller)
            ->withBalance(200)
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders($this->sellerToken))
            ->getJson('/api/wallet');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('payout_estimate.is_eligible', true);
    }
}
