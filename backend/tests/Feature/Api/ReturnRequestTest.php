<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\SubOrder;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $seller;

    protected string $buyerToken;

    protected string $sellerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->create();
        $this->seller = User::factory()->seller()->create();
        $this->buyerToken = $this->buyer->createToken('buyer-token')->plainTextToken;
        $this->sellerToken = $this->seller->createToken('seller-token')->plainTextToken;

        // Mock NotificationService to avoid actual notifications
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyReturnRequestCreated')->andReturn(null);
            $mock->shouldReceive('notifyReturnRequestApproved')->andReturn(null);
            $mock->shouldReceive('notifyReturnRequestRejected')->andReturn(null);
        });
    }

    protected function buyerHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->buyerToken];
    }

    protected function sellerHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->sellerToken];
    }

    /**
     * Create a shipped order with items for testing.
     */
    protected function createShippedOrderWithItems(): array
    {
        $product = Product::factory()->create();
        $order = Order::factory()->shipped()->paid()->forUser($this->buyer)->create();
        $orderItem = OrderItem::factory()->forOrder($order)->forSeller($this->seller)->create([
            'product_id' => $product->id,
            'unit_price' => 100.00,
            'quantity' => 2,
            'total_price' => 200.00,
        ]);
        $subOrder = SubOrder::factory()->shipped()->forOrder($order)->forSeller($this->seller)->create();

        return [$order, $orderItem, $subOrder, $product];
    }

    // ==========================================
    // LIST MY REQUESTS (BUYER)
    // ==========================================

    /**
     * Test buyer can list their return requests.
     */
    public function test_buyer_can_list_return_requests(): void
    {
        ReturnRequest::factory()->count(3)->forBuyer($this->buyer)->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/returns/my-requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['total'],
            ])
            ->assertJsonPath('pagination.total', 3);
    }

    /**
     * Test buyer only sees own return requests.
     */
    public function test_buyer_only_sees_own_return_requests(): void
    {
        ReturnRequest::factory()->count(2)->forBuyer($this->buyer)->create();
        $otherBuyer = User::factory()->create();
        ReturnRequest::factory()->count(3)->forBuyer($otherBuyer)->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/returns/my-requests');

        $response->assertJsonPath('pagination.total', 2);
    }

    // ==========================================
    // LIST SELLER REQUESTS
    // ==========================================

    /**
     * Test seller can list return requests received.
     */
    public function test_seller_can_list_received_return_requests(): void
    {
        ReturnRequest::factory()->count(2)->forSeller($this->seller)->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/returns/seller-requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
                'pending_count',
            ]);
    }

    /**
     * Test seller can filter by status.
     */
    public function test_seller_can_filter_return_requests_by_status(): void
    {
        ReturnRequest::factory()->count(2)->pending()->forSeller($this->seller)->create();
        ReturnRequest::factory()->count(1)->approved()->forSeller($this->seller)->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/returns/seller-requests?status=pending');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 2);
    }

    // ==========================================
    // STORE (CREATE RETURN REQUEST)
    // ==========================================

    /**
     * Test buyer can create return request for shipped order.
     */
    public function test_buyer_can_create_return_request(): void
    {
        [$order, $orderItem, $subOrder, $product] = $this->createShippedOrderWithItems();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/returns', [
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'reason' => 'damaged',
                'reason_detail' => 'Ürün hasarlı geldi.',
                'quantity' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('return_requests', [
            'order_id' => $order->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'reason' => 'damaged',
            'status' => 'pending',
        ]);
    }

    /**
     * Test buyer cannot create return request for pending order.
     */
    public function test_buyer_cannot_create_return_for_pending_order(): void
    {
        $order = Order::factory()->forUser($this->buyer)->create(['status' => 'pending']);
        OrderItem::factory()->forOrder($order)->forSeller($this->seller)->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/returns', [
                'order_id' => $order->id,
                'reason' => 'damaged',
            ]);

        $response->assertStatus(400);
    }

    /**
     * Test buyer cannot create return for another user's order.
     */
    public function test_buyer_cannot_create_return_for_another_users_order(): void
    {
        $otherBuyer = User::factory()->create();
        $order = Order::factory()->shipped()->forUser($otherBuyer)->create();
        OrderItem::factory()->forOrder($order)->forSeller($this->seller)->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/returns', [
                'order_id' => $order->id,
                'reason' => 'damaged',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test duplicate return request is rejected.
     */
    public function test_duplicate_return_request_is_rejected(): void
    {
        [$order, $orderItem, $subOrder, $product] = $this->createShippedOrderWithItems();

        // Create existing pending return
        ReturnRequest::factory()->create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/returns', [
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'reason' => 'quality_issue',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    /**
     * Test store validation requires mandatory fields.
     */
    public function test_store_validation_requires_order_id_and_reason(): void
    {
        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/returns', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id', 'reason']);
    }

    /**
     * Test store validation checks reason values.
     */
    public function test_store_validation_checks_reason_values(): void
    {
        [$order] = $this->createShippedOrderWithItems();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/returns', [
                'order_id' => $order->id,
                'reason' => 'invalid_reason',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    // ==========================================
    // APPROVE TESTS
    // ==========================================

    /**
     * Test seller can approve a pending return request.
     */
    public function test_seller_can_approve_return_request(): void
    {
        $returnRequest = ReturnRequest::factory()->pending()->create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/returns/{$returnRequest->id}/approve", [
                'note' => 'Onaylandı, iade kargosunu gönderebilirsiniz.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'İade talebi onaylandı.',
            ]);

        $this->assertEquals('approved', $returnRequest->fresh()->status);
    }

    /**
     * Test buyer cannot approve a return request.
     */
    public function test_buyer_cannot_approve_return_request(): void
    {
        $returnRequest = ReturnRequest::factory()->pending()->create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson("/api/returns/{$returnRequest->id}/approve");

        $response->assertStatus(403);
    }

    /**
     * Test cannot approve already approved return.
     */
    public function test_cannot_approve_already_approved_return(): void
    {
        $returnRequest = ReturnRequest::factory()->approved()->create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/returns/{$returnRequest->id}/approve");

        $response->assertStatus(400);
    }

    // ==========================================
    // REJECT TESTS
    // ==========================================

    /**
     * Test seller can reject a pending return request.
     */
    public function test_seller_can_reject_return_request(): void
    {
        $returnRequest = ReturnRequest::factory()->pending()->create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/returns/{$returnRequest->id}/reject", [
                'note' => 'Ürün hasarsız, iade kabul edilmedi.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'İade talebi reddedildi.',
            ]);

        $this->assertEquals('rejected', $returnRequest->fresh()->status);
    }

    /**
     * Test reject requires a note.
     */
    public function test_reject_requires_note(): void
    {
        $returnRequest = ReturnRequest::factory()->pending()->create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/returns/{$returnRequest->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['note']);
    }

    /**
     * Test buyer cannot reject a return request.
     */
    public function test_buyer_cannot_reject_return_request(): void
    {
        $returnRequest = ReturnRequest::factory()->pending()->create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson("/api/returns/{$returnRequest->id}/reject", [
                'note' => 'Should not work.',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test another seller cannot approve/reject return request.
     */
    public function test_another_seller_cannot_approve_return_request(): void
    {
        $otherSeller = User::factory()->seller()->create();
        $otherToken = $otherSeller->createToken('other-token')->plainTextToken;

        $returnRequest = ReturnRequest::factory()->pending()->create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$otherToken])
            ->postJson("/api/returns/{$returnRequest->id}/approve");

        $response->assertStatus(403);
    }

    // ==========================================
    // REASONS ENDPOINT
    // ==========================================

    /**
     * Test reasons endpoint returns reason list.
     */
    public function test_reasons_endpoint_returns_reason_list(): void
    {
        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/returns/reasons');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'reasons' => [
                    '*' => ['value', 'label'],
                ],
            ]);
    }

    // ==========================================
    // UNAUTHENTICATED ACCESS
    // ==========================================

    /**
     * Test unauthenticated user cannot access return endpoints.
     */
    public function test_unauthenticated_user_cannot_access_return_endpoints(): void
    {
        $this->getJson('/api/returns/my-requests')->assertStatus(401);
        $this->getJson('/api/returns/seller-requests')->assertStatus(401);
        $this->postJson('/api/returns', [])->assertStatus(401);
    }
}
