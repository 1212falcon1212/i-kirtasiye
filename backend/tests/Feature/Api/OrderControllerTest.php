<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_verified' => true]);
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Helper method to make authenticated requests.
     */
    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    /**
     * Helper method to create a complete order with items.
     */
    protected function createOrderWithItems(User $buyer, User $seller, int $itemCount = 1): Order
    {
        $category = Category::factory()->create();
        $order = Order::factory()->forUser($buyer)->create();

        for ($i = 0; $i < $itemCount; $i++) {
            $product = Product::factory()->forCategory($category)->create();
            $offer = Offer::factory()
                ->forProduct($product)
                ->forSeller($seller)
                ->withPrice(100.00)
                ->withStock(50)
                ->available()
                ->create();

            OrderItem::factory()
                ->forOrder($order)
                ->forOffer($offer)
                ->forSeller($seller)
                ->withQuantityAndPrice(2, 100.00)
                ->create();
        }

        return $order->fresh(['items']);
    }

    /**
     * Helper method to create a cart with items for checkout.
     */
    protected function createCartWithItems(User $buyer, User $seller, int $itemCount = 1): Cart
    {
        $cart = Cart::factory()->forUser($buyer)->create();
        $category = Category::factory()->withCommissionRate(10)->create();

        for ($i = 0; $i < $itemCount; $i++) {
            $product = Product::factory()->forCategory($category)->create();
            $offer = Offer::factory()
                ->forProduct($product)
                ->forSeller($seller)
                ->withPrice(100.00)
                ->withStock(50)
                ->available()
                ->create();

            CartItem::factory()
                ->forCart($cart)
                ->forOffer($offer)
                ->withQuantity(2)
                ->withPriceAtAddition(100.00)
                ->create();
        }

        return $cart->fresh(['items']);
    }

    // ==========================================
    // INDEX (GET /api/orders) - Buyer's orders
    // ==========================================

    /**
     * Test buyer can list their orders.
     */
    public function test_index_returns_buyer_orders(): void
    {
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($this->user, $seller, 2);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'orders',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(1, $response->json('orders'));
    }

    /**
     * Test buyer sees empty list when no orders exist.
     */
    public function test_index_returns_empty_list_for_new_user(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJson([
                'orders' => [],
                'pagination' => [
                    'total' => 0,
                ],
            ]);
    }

    /**
     * Test buyer cannot see other users' orders.
     */
    public function test_index_does_not_show_other_users_orders(): void
    {
        $otherUser = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $this->createOrderWithItems($otherUser, $seller);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJson([
                'orders' => [],
                'pagination' => [
                    'total' => 0,
                ],
            ]);
    }

    /**
     * Test orders are paginated correctly.
     */
    public function test_index_paginates_orders(): void
    {
        $seller = User::factory()->seller()->create();

        // Create 15 orders
        for ($i = 0; $i < 15; $i++) {
            $this->createOrderWithItems($this->user, $seller);
        }

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/orders?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 15)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.last_page', 2);

        $this->assertCount(10, $response->json('orders'));
    }

    /**
     * Test unauthenticated user cannot access orders.
     */
    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    // ==========================================
    // SHOW (GET /api/orders/{id}) - Order details
    // ==========================================

    /**
     * Test buyer can view their own order details.
     */
    public function test_show_returns_order_details_for_buyer(): void
    {
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($this->user, $seller, 2);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'order' => [
                    'id',
                    'order_number',
                    'status',
                    'payment_status',
                    'subtotal',
                    'total_amount',
                    'shipping_address',
                ],
                'items',
                'items_by_seller',
                'is_buyer',
                'is_seller',
            ])
            ->assertJsonPath('is_buyer', true)
            ->assertJsonPath('is_seller', false);
    }

    /**
     * Test seller can view order where they are the seller.
     */
    public function test_show_returns_order_details_for_seller(): void
    {
        $buyer = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $order = $this->createOrderWithItems($buyer, $seller, 2);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('is_buyer', false)
            ->assertJsonPath('is_seller', true);
    }

    /**
     * Test buyer cannot view another user's order.
     */
    public function test_show_returns_404_for_unauthorized_user(): void
    {
        $otherUser = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($otherUser, $seller);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Sipariş bulunamadı.',
            ]);
    }

    /**
     * Test non-existent order returns 404.
     */
    public function test_show_returns_404_for_nonexistent_order(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/orders/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Sipariş bulunamadı.',
            ]);
    }

    /**
     * Test super admin can view any order.
     */
    public function test_show_allows_super_admin_to_view_any_order(): void
    {
        // Note: The User model expects role='super-admin' for isSuperAdmin() to return true
        $admin = User::factory()->create([
            'role' => 'super-admin',
            'is_verified' => true,
        ]);
        $adminToken = $admin->createToken('test-token')->plainTextToken;

        $otherUser = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($otherUser, $seller);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['order', 'items']);
    }

    // ==========================================
    // STORE (POST /api/orders) - Create order from cart
    // ==========================================

    /**
     * Test buyer can create order from cart.
     */
    public function test_store_creates_order_from_cart(): void
    {
        $seller = User::factory()->seller()->create();
        $this->createCartWithItems($this->user, $seller, 2);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'shipping_address' => [
                    'name' => 'Test Kullanıcı',
                    'phone' => '5551234567',
                    'address' => 'Test Mahallesi, Test Sokak No:1',
                    'city' => 'Istanbul',
                    'district' => 'Kadikoy',
                    'postal_code' => '34700',
                ],
                'notes' => 'Test siparis notu',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Siparişiniz başarıyla oluşturuldu.',
            ])
            ->assertJsonStructure([
                'order' => [
                    'id',
                    'order_number',
                    'status',
                    'subtotal',
                    'total_amount',
                ],
                'order_number',
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test order creation fails with empty cart.
     */
    public function test_store_fails_with_empty_cart(): void
    {
        // Create empty cart
        Cart::factory()->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'shipping_address' => [
                    'name' => 'Test Kullanıcı',
                    'phone' => '5551234567',
                    'address' => 'Test Mahallesi No:1',
                    'city' => 'Istanbul',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Sepetiniz boş.',
            ]);
    }

    /**
     * Test order creation fails without shipping address.
     */
    public function test_store_fails_without_shipping_address(): void
    {
        $seller = User::factory()->seller()->create();
        $this->createCartWithItems($this->user, $seller);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shipping_address']);
    }

    /**
     * Test order creation validates required address fields.
     */
    public function test_store_validates_shipping_address_fields(): void
    {
        $seller = User::factory()->seller()->create();
        $this->createCartWithItems($this->user, $seller);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'shipping_address' => [
                    'name' => 'Test',
                    // Missing phone, address, city
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'shipping_address.phone',
                'shipping_address.address',
                'shipping_address.city',
            ]);
    }

    /**
     * Test order creation accepts optional payment method.
     */
    public function test_store_accepts_payment_method(): void
    {
        $seller = User::factory()->seller()->create();
        $this->createCartWithItems($this->user, $seller);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'shipping_address' => [
                    'name' => 'Test Kullanıcı',
                    'phone' => '5551234567',
                    'address' => 'Test Mahallesi No:1',
                    'city' => 'Istanbul',
                ],
                'payment_method' => 'bank_transfer',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'payment_method' => 'bank_transfer',
        ]);
    }

    /**
     * Test order creation validates payment method options.
     */
    public function test_store_validates_payment_method(): void
    {
        $seller = User::factory()->seller()->create();
        $this->createCartWithItems($this->user, $seller);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'shipping_address' => [
                    'name' => 'Test Kullanıcı',
                    'phone' => '5551234567',
                    'address' => 'Test Mahallesi No:1',
                    'city' => 'Istanbul',
                ],
                'payment_method' => 'invalid_method',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    /**
     * Test order creation decreases stock.
     */
    public function test_store_decreases_offer_stock(): void
    {
        $seller = User::factory()->seller()->create();
        $cart = $this->createCartWithItems($this->user, $seller);
        $cartItem = $cart->items->first();
        $initialStock = $cartItem->offer->stock;

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'shipping_address' => [
                    'name' => 'Test Kullanıcı',
                    'phone' => '5551234567',
                    'address' => 'Test Mahallesi No:1',
                    'city' => 'Istanbul',
                ],
            ]);

        $response->assertStatus(201);

        $cartItem->offer->refresh();
        $this->assertEquals($initialStock - $cartItem->quantity, $cartItem->offer->stock);
    }

    /**
     * Test order creation clears the cart.
     */
    public function test_store_converts_cart(): void
    {
        $seller = User::factory()->seller()->create();
        $cart = $this->createCartWithItems($this->user, $seller);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'shipping_address' => [
                    'name' => 'Test Kullanıcı',
                    'phone' => '5551234567',
                    'address' => 'Test Mahallesi No:1',
                    'city' => 'Istanbul',
                ],
            ]);

        $response->assertStatus(201);

        $cart->refresh();
        $this->assertEquals('converted', $cart->status);
    }

    // ==========================================
    // CANCEL (PUT /api/orders/{id}/cancel)
    // ==========================================

    /**
     * Test buyer can cancel a pending order.
     */
    public function test_cancel_pending_order_succeeds(): void
    {
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($this->user, $seller);
        $this->assertEquals('pending', $order->status);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Sipariş iptal edildi.',
            ]);

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    /**
     * Test buyer can cancel a confirmed order.
     */
    public function test_cancel_confirmed_order_succeeds(): void
    {
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($this->user, $seller);
        $order->update(['status' => 'confirmed']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Sipariş iptal edildi.',
            ]);

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    /**
     * Test cannot cancel a shipped order.
     */
    public function test_cancel_shipped_order_fails(): void
    {
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($this->user, $seller);
        $order->update(['status' => 'shipped']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Bu sipariş iptal edilemez.',
            ]);
    }

    /**
     * Test cannot cancel a delivered order.
     */
    public function test_cancel_delivered_order_fails(): void
    {
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($this->user, $seller);
        $order->update(['status' => 'delivered']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Bu sipariş iptal edilemez.',
            ]);
    }

    /**
     * Test cannot cancel another user's order.
     */
    public function test_cancel_fails_for_unauthorized_user(): void
    {
        $otherUser = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($otherUser, $seller);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Sipariş bulunamadı.',
            ]);
    }

    /**
     * Test cancelling order restores stock.
     */
    public function test_cancel_restores_offer_stock(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $order = Order::factory()->forUser($this->user)->create(['status' => 'pending']);
        OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($seller)
            ->withQuantityAndPrice(5, 100.00)
            ->create();

        // Simulate stock decrease from order
        $offer->decrement('stock', 5);
        $offer->refresh();
        $this->assertEquals(45, $offer->stock);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200);

        $offer->refresh();
        $this->assertEquals(50, $offer->stock);
    }

    // ==========================================
    // UPDATE STATUS (PUT /api/orders/{id}/status) - Seller
    // ==========================================

    /**
     * Test seller can update order to processing.
     */
    public function test_update_status_to_processing_succeeds(): void
    {
        $buyer = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $order = $this->createOrderWithItems($buyer, $seller);
        $order->update(['status' => 'pending']);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'processing',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Sipariş durumu güncellendi.',
            ]);

        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }

    /**
     * Test seller can update order to shipped.
     */
    public function test_update_status_to_shipped_succeeds(): void
    {
        $buyer = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $order = $this->createOrderWithItems($buyer, $seller);
        $order->update(['status' => 'processing']);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'shipped',
            ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('shipped', $order->status);
        $this->assertNotNull($order->shipped_at);
    }

    /**
     * Test seller can update order to delivered.
     */
    public function test_update_status_to_delivered_succeeds(): void
    {
        $buyer = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $order = $this->createOrderWithItems($buyer, $seller);
        $order->update(['status' => 'shipped', 'shipped_at' => now()]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'delivered',
            ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->delivered_at);
    }

    /**
     * Test invalid status transition fails.
     */
    public function test_update_status_invalid_transition_fails(): void
    {
        $buyer = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $order = $this->createOrderWithItems($buyer, $seller);
        $order->update(['status' => 'pending']);

        // Try to skip directly to shipped (should fail)
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'shipped',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Geçersiz durum geçişi.',
            ]);
    }

    /**
     * Test cannot update to invalid status value.
     */
    public function test_update_status_validates_status_value(): void
    {
        $buyer = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $order = $this->createOrderWithItems($buyer, $seller);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test non-seller cannot update order status.
     */
    public function test_update_status_fails_for_non_seller(): void
    {
        $buyer = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($buyer, $seller);

        // Buyer trying to update status
        $buyerToken = $buyer->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $buyerToken])
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'processing',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Bu işlem için yetkiniz yok.',
            ]);
    }

    /**
     * Test unrelated user cannot update order status.
     */
    public function test_update_status_fails_for_unrelated_user(): void
    {
        $buyer = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($buyer, $seller);

        // Random user trying to update status
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'processing',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Bu işlem için yetkiniz yok.',
            ]);
    }

    /**
     * Test super admin can update any order status.
     */
    public function test_update_status_allows_super_admin(): void
    {
        // Note: The User model expects role='super-admin' for isSuperAdmin() to return true
        $admin = User::factory()->create([
            'role' => 'super-admin',
            'is_verified' => true,
        ]);
        $adminToken = $admin->createToken('test-token')->plainTextToken;

        $buyer = User::factory()->create(['is_verified' => true]);
        $seller = User::factory()->seller()->create();
        $order = $this->createOrderWithItems($buyer, $seller);
        $order->update(['status' => 'pending']);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->putJson("/api/orders/{$order->id}/status", [
                'status' => 'processing',
            ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }

    /**
     * Test update status returns 404 for non-existent order.
     */
    public function test_update_status_returns_404_for_nonexistent_order(): void
    {
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->putJson('/api/orders/99999/status', [
                'status' => 'processing',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Sipariş bulunamadı.',
            ]);
    }

    // ==========================================
    // SELLER ORDERS (GET /api/orders/seller)
    // ==========================================

    /**
     * Test seller can list their orders.
     */
    public function test_seller_orders_returns_orders_for_seller(): void
    {
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $buyer = User::factory()->create(['is_verified' => true]);
        $this->createOrderWithItems($buyer, $seller, 2);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->getJson('/api/orders/seller');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'orders' => [
                    '*' => [
                        'id',
                        'order_number',
                        'status',
                        'status_label',
                        'buyer',
                        'items',
                        'seller_total',
                        'seller_commission',
                        'seller_payout',
                        'created_at',
                    ],
                ],
                'pagination',
            ]);

        $this->assertCount(1, $response->json('orders'));
    }

    /**
     * Test seller only sees items they sell.
     */
    public function test_seller_orders_only_shows_seller_items(): void
    {
        $seller1 = User::factory()->seller()->create();
        $seller2 = User::factory()->seller()->create();
        $seller1Token = $seller1->createToken('test-token')->plainTextToken;

        $buyer = User::factory()->create(['is_verified' => true]);

        // Create order with items from both sellers
        $category = Category::factory()->create();
        $order = Order::factory()->forUser($buyer)->create();

        // Items for seller1
        $product1 = Product::factory()->forCategory($category)->create();
        $offer1 = Offer::factory()->forProduct($product1)->forSeller($seller1)->available()->create();
        OrderItem::factory()->forOrder($order)->forOffer($offer1)->forSeller($seller1)->create();

        // Items for seller2
        $product2 = Product::factory()->forCategory($category)->create();
        $offer2 = Offer::factory()->forProduct($product2)->forSeller($seller2)->available()->create();
        OrderItem::factory()->forOrder($order)->forOffer($offer2)->forSeller($seller2)->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $seller1Token])
            ->getJson('/api/orders/seller');

        $response->assertStatus(200);

        $orders = $response->json('orders');
        $this->assertCount(1, $orders);
        $this->assertCount(1, $orders[0]['items']);
        $this->assertEquals($seller1->id, $orders[0]['items'][0]['seller_id']);
    }

    /**
     * Test seller orders can be filtered by status.
     */
    public function test_seller_orders_filters_by_status(): void
    {
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;
        $buyer = User::factory()->create(['is_verified' => true]);

        // Create orders with different statuses
        $pendingOrder = $this->createOrderWithItems($buyer, $seller);
        $pendingOrder->update(['status' => 'pending']);

        $shippedOrder = $this->createOrderWithItems($buyer, $seller);
        $shippedOrder->update(['status' => 'shipped']);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->getJson('/api/orders/seller?status=pending');

        $response->assertStatus(200);

        $orders = $response->json('orders');
        $this->assertCount(1, $orders);
        $this->assertEquals('pending', $orders[0]['status']);
    }

    /**
     * Test seller sees empty list when no orders.
     */
    public function test_seller_orders_returns_empty_for_new_seller(): void
    {
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->getJson('/api/orders/seller');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 0);

        $this->assertEmpty($response->json('orders'));
    }

    /**
     * Test seller orders includes buyer information.
     */
    public function test_seller_orders_includes_buyer_info(): void
    {
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;

        $buyer = User::factory()->create([
            'is_verified' => true,
            'pharmacy_name' => 'Test Eczanesi',
            'email' => 'buyer@test.com',
        ]);

        $this->createOrderWithItems($buyer, $seller);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->getJson('/api/orders/seller');

        $response->assertStatus(200)
            ->assertJsonPath('orders.0.buyer.pharmacy_name', 'Test Eczanesi')
            ->assertJsonPath('orders.0.buyer.email', 'buyer@test.com');
    }

    /**
     * Test seller orders are paginated.
     */
    public function test_seller_orders_paginates_results(): void
    {
        $seller = User::factory()->seller()->create();
        $sellerToken = $seller->createToken('test-token')->plainTextToken;
        $buyer = User::factory()->create(['is_verified' => true]);

        // Create 15 orders
        for ($i = 0; $i < 15; $i++) {
            $this->createOrderWithItems($buyer, $seller);
        }

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $sellerToken])
            ->getJson('/api/orders/seller?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 15)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.last_page', 2);

        $this->assertCount(10, $response->json('orders'));
    }

    // ==========================================
    // EDGE CASES AND ERROR HANDLING
    // ==========================================

    /**
     * Test order creation fails when cart has stock issues.
     */
    public function test_store_fails_when_stock_insufficient(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->withCommissionRate(10)->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(5)
            ->available()
            ->create();

        $cart = Cart::factory()->forUser($this->user)->create();
        CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(10) // More than available stock
            ->withPriceAtAddition(100.00)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'shipping_address' => [
                    'name' => 'Test Kullanıcı',
                    'phone' => '5551234567',
                    'address' => 'Test Mahallesi No:1',
                    'city' => 'Istanbul',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Sepetinizde düzeltilmesi gereken sorunlar var.',
            ]);
    }

    /**
     * Test order number is unique and follows format.
     */
    public function test_store_generates_unique_order_number(): void
    {
        $seller = User::factory()->seller()->create();
        $this->createCartWithItems($this->user, $seller);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', [
                'shipping_address' => [
                    'name' => 'Test Kullanıcı',
                    'phone' => '5551234567',
                    'address' => 'Test Mahallesi No:1',
                    'city' => 'Istanbul',
                ],
            ]);

        $response->assertStatus(201);

        $orderNumber = $response->json('order_number');
        $this->assertStringStartsWith('EPZ', $orderNumber);
        $this->assertMatchesRegularExpression('/^EPZ\d{6}\d{4}[A-Z0-9]{4}$/', $orderNumber);
    }

    /**
     * Test orders are ordered by created_at desc.
     */
    public function test_index_orders_sorted_by_created_at_desc(): void
    {
        $seller = User::factory()->seller()->create();

        // Create old order first with timestamp in the past
        $category = Category::factory()->create();
        $oldOrder = Order::factory()->forUser($this->user)->create([
            'created_at' => now()->subDays(5),
        ]);
        $product1 = Product::factory()->forCategory($category)->create();
        $offer1 = Offer::factory()
            ->forProduct($product1)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();
        OrderItem::factory()
            ->forOrder($oldOrder)
            ->forOffer($offer1)
            ->forSeller($seller)
            ->create();

        // Create new order with current timestamp
        $newOrder = Order::factory()->forUser($this->user)->create([
            'created_at' => now(),
        ]);
        $product2 = Product::factory()->forCategory($category)->create();
        $offer2 = Offer::factory()
            ->forProduct($product2)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();
        OrderItem::factory()
            ->forOrder($newOrder)
            ->forOffer($offer2)
            ->forSeller($seller)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/orders');

        $response->assertStatus(200);

        $orders = $response->json('orders');
        $this->assertCount(2, $orders);
        $this->assertEquals($newOrder->id, $orders[0]['id']);
        $this->assertEquals($oldOrder->id, $orders[1]['id']);
    }
}
