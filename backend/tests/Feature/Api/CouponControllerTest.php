<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $buyer;
    protected string $sellerToken;
    protected string $buyerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seller = User::factory()->seller()->create(['is_verified' => true]);
        $this->buyer = User::factory()->create(['is_verified' => true]);
        $this->sellerToken = $this->seller->createToken('test-token')->plainTextToken;
        $this->buyerToken = $this->buyer->createToken('test-token')->plainTextToken;
    }

    /**
     * Helper method to make authenticated requests as seller.
     */
    protected function sellerHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->sellerToken];
    }

    /**
     * Helper method to make authenticated requests as buyer.
     */
    protected function buyerHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->buyerToken];
    }

    // ==========================================
    // GET /api/coupons - List seller's coupons
    // ==========================================

    /**
     * Test listing coupons for authenticated seller.
     */
    public function test_index_returns_sellers_coupons(): void
    {
        Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->count(3)
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/coupons');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'coupons',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(3, 'coupons');
    }

    /**
     * Test listing coupons returns empty for seller with no coupons.
     */
    public function test_index_returns_empty_for_no_coupons(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/coupons');

        $response->assertStatus(200)
            ->assertJson([
                'coupons' => [],
                'pagination' => [
                    'total' => 0,
                ],
            ]);
    }

    /**
     * Test listing coupons does not include other sellers' coupons.
     */
    public function test_index_excludes_other_sellers_coupons(): void
    {
        $otherSeller = User::factory()->seller()->create(['is_verified' => true]);

        // Create coupons for other seller
        Coupon::factory()
            ->forSeller($otherSeller)
            ->count(2)
            ->create();

        // Create coupon for our seller
        Coupon::factory()
            ->forSeller($this->seller)
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/coupons');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'coupons');
    }

    /**
     * Test listing coupons can filter by status.
     */
    public function test_index_filters_by_status(): void
    {
        Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->count(2)
            ->create();

        Coupon::factory()
            ->forSeller($this->seller)
            ->inactive()
            ->count(3)
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/coupons?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'coupons');
    }

    /**
     * Test listing coupons pagination works.
     */
    public function test_index_pagination_works(): void
    {
        Coupon::factory()
            ->forSeller($this->seller)
            ->count(25)
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/coupons?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'coupons')
            ->assertJsonPath('pagination.total', 25)
            ->assertJsonPath('pagination.last_page', 3);
    }

    /**
     * Test listing coupons requires authentication.
     */
    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/coupons');

        $response->assertStatus(401);
    }

    /**
     * Test listing coupons includes campaign relationship.
     */
    public function test_index_includes_campaign_relationship(): void
    {
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        Coupon::factory()
            ->forSeller($this->seller)
            ->forCampaign($campaign)
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/coupons');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'coupons' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'campaign',
                    ],
                ],
            ]);
    }

    // ==========================================
    // POST /api/coupons - Create a coupon
    // ==========================================

    /**
     * Test creating a coupon with valid data.
     */
    public function test_store_creates_coupon_with_valid_data(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'name' => 'Test Kuponu',
                'description' => 'Test aciklamasi',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_purchase_amount' => 100,
                'max_discount_amount' => 50,
                'usage_limit' => 100,
                'usage_limit_per_user' => 1,
                'starts_at' => now()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'coupon' => [
                    'id',
                    'code',
                    'name',
                    'discount_type',
                    'discount_value',
                ],
            ]);

        $this->assertDatabaseHas('coupons', [
            'seller_id' => $this->seller->id,
            'name' => 'Test Kuponu',
            'discount_type' => 'percentage',
            'discount_value' => 15,
        ]);
    }

    /**
     * Test creating a coupon with custom code.
     */
    public function test_store_creates_coupon_with_custom_code(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'code' => 'MYCODE2024',
                'name' => 'Test Kuponu',
                'discount_type' => 'fixed',
                'discount_value' => 25,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('coupons', [
            'code' => 'MYCODE2024',
        ]);
    }

    /**
     * Test creating a coupon generates code if not provided.
     */
    public function test_store_generates_code_if_not_provided(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'name' => 'Test Kuponu',
                'discount_type' => 'percentage',
                'discount_value' => 10,
            ]);

        $response->assertStatus(201);

        $coupon = Coupon::first();
        $this->assertNotEmpty($coupon->code);
        $this->assertEquals(8, strlen($coupon->code));
    }

    /**
     * Test creating a coupon converts code to uppercase.
     */
    public function test_store_converts_code_to_uppercase(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'code' => 'lowercase',
                'name' => 'Test Kuponu',
                'discount_type' => 'percentage',
                'discount_value' => 10,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('coupons', [
            'code' => 'LOWERCASE',
        ]);
    }

    /**
     * Test creating a coupon with campaign association.
     */
    public function test_store_creates_coupon_with_campaign(): void
    {
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'name' => 'Kampanya Kuponu',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'campaign_id' => $campaign->id,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('coupons', [
            'campaign_id' => $campaign->id,
        ]);
    }

    /**
     * Test creating a coupon fails with another seller's campaign.
     */
    public function test_store_fails_with_another_sellers_campaign(): void
    {
        $otherSeller = User::factory()->seller()->create(['is_verified' => true]);
        $campaign = Campaign::factory()
            ->forSeller($otherSeller)
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'name' => 'Kampanya Kuponu',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'campaign_id' => $campaign->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Belirtilen kampanya size ait değil.',
            ]);
    }

    /**
     * Test creating a coupon fails with duplicate code.
     */
    public function test_store_fails_with_duplicate_code(): void
    {
        Coupon::factory()
            ->forSeller($this->seller)
            ->withCode('EXISTING')
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'code' => 'EXISTING',
                'name' => 'Yeni Kupon',
                'discount_type' => 'percentage',
                'discount_value' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /**
     * Test creating a coupon validates required fields.
     */
    public function test_store_validates_required_fields(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'discount_type', 'discount_value']);
    }

    /**
     * Test creating a coupon validates discount type.
     */
    public function test_store_validates_discount_type(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'name' => 'Test Kupon',
                'discount_type' => 'invalid',
                'discount_value' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_type']);
    }

    /**
     * Test creating a coupon validates discount value minimum.
     */
    public function test_store_validates_discount_value_minimum(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'name' => 'Test Kupon',
                'discount_type' => 'percentage',
                'discount_value' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_value']);
    }

    /**
     * Test creating a coupon validates ends_at after starts_at.
     */
    public function test_store_validates_ends_at_after_starts_at(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'name' => 'Test Kupon',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ends_at']);
    }

    /**
     * Test creating a coupon requires authentication.
     */
    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/coupons', [
            'name' => 'Test Kupon',
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]);

        $response->assertStatus(401);
    }

    // ==========================================
    // DELETE /api/coupons/{coupon} - Delete a coupon
    // ==========================================

    /**
     * Test deleting an unused coupon.
     */
    public function test_destroy_deletes_unused_coupon(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->withUsedCount(0)
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->deleteJson("/api/coupons/{$coupon->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Kupon başarıyla silindi.',
            ]);

        $this->assertSoftDeleted('coupons', ['id' => $coupon->id]);
    }

    /**
     * Test deleting a coupon fails if it has been used.
     */
    public function test_destroy_fails_for_used_coupon(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->withUsedCount(5)
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->deleteJson("/api/coupons/{$coupon->id}");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Kullanılmış kuponlar silinemez. Pasife alabilirsiniz.',
            ]);

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'deleted_at' => null]);
    }

    /**
     * Test deleting another seller's coupon fails.
     */
    public function test_destroy_fails_for_another_sellers_coupon(): void
    {
        $otherSeller = User::factory()->seller()->create(['is_verified' => true]);
        $coupon = Coupon::factory()
            ->forSeller($otherSeller)
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->deleteJson("/api/coupons/{$coupon->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Bu kuponu silme yetkiniz yok.',
            ]);
    }

    /**
     * Test deleting a non-existent coupon fails.
     */
    public function test_destroy_fails_for_nonexistent_coupon(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->deleteJson('/api/coupons/99999');

        $response->assertStatus(404);
    }

    /**
     * Test deleting a coupon requires authentication.
     */
    public function test_destroy_requires_authentication(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->create();

        $response = $this->deleteJson("/api/coupons/{$coupon->id}");

        $response->assertStatus(401);
    }

    // ==========================================
    // POST /api/coupons/{coupon}/toggle-status - Toggle status
    // ==========================================

    /**
     * Test toggling active coupon to inactive.
     */
    public function test_toggle_status_deactivates_active_coupon(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/coupons/{$coupon->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Kupon pasife alındı.',
            ]);

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'status' => Coupon::STATUS_INACTIVE,
        ]);
    }

    /**
     * Test toggling inactive coupon to active.
     */
    public function test_toggle_status_activates_inactive_coupon(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->inactive()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/coupons/{$coupon->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Kupon aktifleştirildi.',
            ]);

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'status' => Coupon::STATUS_ACTIVE,
        ]);
    }

    /**
     * Test toggling another seller's coupon fails.
     */
    public function test_toggle_status_fails_for_another_sellers_coupon(): void
    {
        $otherSeller = User::factory()->seller()->create(['is_verified' => true]);
        $coupon = Coupon::factory()
            ->forSeller($otherSeller)
            ->active()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/coupons/{$coupon->id}/toggle-status");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Bu kuponu düzenleme yetkiniz yok.',
            ]);
    }

    /**
     * Test toggling status requires authentication.
     */
    public function test_toggle_status_requires_authentication(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->create();

        $response = $this->postJson("/api/coupons/{$coupon->id}/toggle-status");

        $response->assertStatus(401);
    }

    // ==========================================
    // POST /api/coupons/apply - Apply coupon to cart
    // ==========================================

    /**
     * Test applying a valid percentage coupon.
     */
    public function test_apply_applies_percentage_coupon(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->percentage(10)
            ->active()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'message' => 'Kupon uygulandı.',
                'discount_amount' => 50, // 10% of 500
                'new_total' => 450,
            ])
            ->assertJsonStructure([
                'coupon' => [
                    'id',
                    'code',
                    'name',
                    'discount_type',
                    'discount_value',
                    'formatted_discount',
                ],
                'formatted_discount_amount',
                'formatted_new_total',
            ]);
    }

    /**
     * Test applying a valid fixed discount coupon.
     */
    public function test_apply_applies_fixed_coupon(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->fixed(75)
            ->active()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount_amount' => 75,
                'new_total' => 425,
            ]);
    }

    /**
     * Test applying coupon respects max discount cap for percentage.
     */
    public function test_apply_respects_max_discount_cap(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->percentage(50)
            ->withMaxDiscount(100)
            ->active()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500, // 50% would be 250, but max is 100
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount_amount' => 100, // Capped at max_discount_amount
                'new_total' => 400,
            ]);
    }

    /**
     * Test applying fixed coupon does not exceed cart total.
     */
    public function test_apply_fixed_coupon_does_not_exceed_cart_total(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->fixed(200)
            ->active()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 100, // Less than discount value
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount_amount' => 100, // Capped at cart total
                'new_total' => 0,
            ]);
    }

    /**
     * Test applying coupon with case-insensitive code.
     */
    public function test_apply_accepts_case_insensitive_code(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->withCode('TESTCODE')
            ->active()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => 'testcode', // lowercase
                'cart_total' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    /**
     * Test applying invalid coupon code fails.
     */
    public function test_apply_fails_for_invalid_code(): void
    {
        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => 'INVALIDCODE',
                'cart_total' => 500,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'valid' => false,
                'message' => 'Geçersiz kupon kodu.',
            ]);
    }

    /**
     * Test applying inactive coupon fails.
     */
    public function test_apply_fails_for_inactive_coupon(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->inactive()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'valid' => false,
                'message' => 'Bu kupon aktif değil.',
            ]);
    }

    /**
     * Test applying expired coupon fails.
     */
    public function test_apply_fails_for_expired_coupon(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->expired()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'valid' => false,
                'message' => 'Bu kuponun süresi dolmuş.',
            ]);
    }

    /**
     * Test applying coupon that hasn't started yet fails.
     */
    public function test_apply_fails_for_not_yet_started_coupon(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->notStarted()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'valid' => false,
                'message' => 'Bu kupon henüz geçerli değil.',
            ]);
    }

    /**
     * Test applying coupon with exhausted usage limit fails.
     */
    public function test_apply_fails_for_exhausted_usage_limit(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->exhausted()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'valid' => false,
                'message' => 'Bu kuponun kullanım limiti dolmuş.',
            ]);
    }

    /**
     * Test applying coupon with exhausted per-user limit fails.
     */
    public function test_apply_fails_for_exhausted_per_user_limit(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->withUsageLimitPerUser(1)
            ->create();

        // Simulate user already used this coupon
        $order = Order::factory()->forUser($this->buyer)->create();
        CouponUsage::factory()
            ->forCoupon($coupon)
            ->forUser($this->buyer)
            ->forOrder($order)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'valid' => false,
                'message' => 'Bu kuponu maksimum kullanım sayısına ulaştınız.',
            ]);
    }

    /**
     * Test applying coupon with minimum purchase amount not met fails.
     */
    public function test_apply_fails_when_min_purchase_not_met(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->withMinPurchase(200)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 150, // Below minimum
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'valid' => false,
            ]);

        $this->assertStringContainsString('minimum sepet tutarı', $response->json('message'));
    }

    /**
     * Test applying coupon with seller_id mismatch fails.
     */
    public function test_apply_fails_for_wrong_seller(): void
    {
        $otherSeller = User::factory()->seller()->create(['is_verified' => true]);
        $coupon = Coupon::factory()
            ->forSeller($otherSeller)
            ->active()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
                'seller_id' => $this->seller->id, // Different from coupon's seller
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'valid' => false,
                'message' => 'Bu kupon bu satıcı için geçerli değil.',
            ]);
    }

    /**
     * Test applying coupon validates required fields.
     */
    public function test_apply_validates_required_fields(): void
    {
        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'cart_total']);
    }

    /**
     * Test applying coupon requires authentication.
     */
    public function test_apply_requires_authentication(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        $response = $this->postJson('/api/coupons/apply', [
            'code' => $coupon->code,
            'cart_total' => 500,
        ]);

        $response->assertStatus(401);
    }

    // ==========================================
    // POST /api/coupons/remove - Remove coupon from cart
    // ==========================================

    /**
     * Test removing coupon returns success.
     */
    public function test_remove_returns_success(): void
    {
        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/remove');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Kupon kaldırıldı.',
            ]);
    }

    /**
     * Test removing coupon requires authentication.
     */
    public function test_remove_requires_authentication(): void
    {
        $response = $this->postJson('/api/coupons/remove');

        $response->assertStatus(401);
    }

    // ==========================================
    // Business Logic Edge Cases
    // ==========================================

    /**
     * Test coupon allows usage when under limit.
     */
    public function test_apply_succeeds_when_under_usage_limit(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->withUsageLimit(10)
            ->withUsedCount(5)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    /**
     * Test coupon allows per-user usage when under limit.
     */
    public function test_apply_succeeds_when_under_per_user_limit(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->withUsageLimitPerUser(3)
            ->create();

        // User has used this coupon twice before
        $order1 = Order::factory()->forUser($this->buyer)->create();
        $order2 = Order::factory()->forUser($this->buyer)->create();

        CouponUsage::factory()
            ->forCoupon($coupon)
            ->forUser($this->buyer)
            ->forOrder($order1)
            ->create();

        CouponUsage::factory()
            ->forCoupon($coupon)
            ->forUser($this->buyer)
            ->forOrder($order2)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    /**
     * Test coupon works when no date restrictions.
     */
    public function test_apply_succeeds_with_no_date_restrictions(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->noDateRestrictions()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    /**
     * Test coupon works when no usage limits.
     */
    public function test_apply_succeeds_with_unlimited_usage(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->unlimited()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    /**
     * Test buyer can list their own usage but sellers manage coupons.
     */
    public function test_buyer_can_apply_coupon_but_cannot_manage(): void
    {
        // Buyer can apply coupons
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        $applyResponse = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 500,
            ]);

        $applyResponse->assertStatus(200);

        // Buyer's coupon list would be their seller coupons (empty if not a seller)
        $listResponse = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/coupons');

        $listResponse->assertStatus(200)
            ->assertJsonCount(0, 'coupons');
    }

    /**
     * Test percentage discount calculation accuracy.
     */
    public function test_percentage_discount_calculation_accuracy(): void
    {
        $coupon = Coupon::factory()
            ->forSeller($this->seller)
            ->percentage(15)
            ->active()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/coupons/apply', [
                'code' => $coupon->code,
                'cart_total' => 333.33,
            ]);

        $response->assertStatus(200);

        // 15% of 333.33 = 50.00 (rounded)
        $this->assertEquals(50.00, $response->json('discount_amount'));
    }

    /**
     * Test creating multiple coupons for same seller.
     */
    public function test_seller_can_create_multiple_coupons(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->withHeaders($this->sellerHeaders())
                ->postJson('/api/coupons', [
                    'name' => "Kupon $i",
                    'discount_type' => 'percentage',
                    'discount_value' => $i * 5,
                ]);

            $response->assertStatus(201);
        }

        $this->assertDatabaseCount('coupons', 3);
    }

    /**
     * Test coupon code uniqueness is global, not per seller.
     */
    public function test_coupon_code_uniqueness_is_global(): void
    {
        $otherSeller = User::factory()->seller()->create(['is_verified' => true]);
        $otherSellerToken = $otherSeller->createToken('test-token')->plainTextToken;

        // First seller creates coupon
        Coupon::factory()
            ->forSeller($otherSeller)
            ->withCode('GLOBALCODE')
            ->create();

        // Second seller tries same code
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/coupons', [
                'code' => 'GLOBALCODE',
                'name' => 'Test Kupon',
                'discount_type' => 'percentage',
                'discount_value' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }
}
