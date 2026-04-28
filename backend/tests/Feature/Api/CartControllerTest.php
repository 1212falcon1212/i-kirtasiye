<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
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
     * Helper method to make authenticated requests
     */
    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    /**
     * Test viewing empty cart.
     */
    public function test_index_returns_empty_cart_for_new_user(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJson([
                'cart' => null,
                'items' => [],
                'item_count' => 0,
                'total' => 0,
            ]);
    }

    /**
     * Test viewing cart with items.
     */
    public function test_index_returns_cart_with_items(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
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
            ->withQuantity(5)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'cart',
                'items',
                'item_count',
                'total',
            ]);
    }

    /**
     * Test adding item to cart.
     */
    public function test_add_item_adds_product_to_cart(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/items', [
                'offer_id' => $offer->id,
                'quantity' => 3,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Urun sepete eklendi.',
            ])
            ->assertJsonStructure([
                'item',
                'item_count',
                'total',
            ]);

        $this->assertDatabaseHas('cart_items', [
            'offer_id' => $offer->id,
            'quantity' => 3,
        ]);
    }

    /**
     * Test adding item with default quantity of 1.
     */
    public function test_add_item_uses_default_quantity_of_one(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/items', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cart_items', [
            'offer_id' => $offer->id,
            'quantity' => 1,
        ]);
    }

    /**
     * Test adding same item increases quantity.
     */
    public function test_add_item_increases_quantity_for_existing_item(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
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
            ->withQuantity(3)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/items', [
                'offer_id' => $offer->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cart_items', [
            'offer_id' => $offer->id,
            'quantity' => 5,
        ]);
    }

    /**
     * Test adding item with insufficient stock fails.
     */
    public function test_add_item_fails_with_insufficient_stock(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(5)
            ->available()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/items', [
                'offer_id' => $offer->id,
                'quantity' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Yeterli stok bulunmuyor.',
            ]);
    }

    /**
     * Test adding unavailable offer fails.
     */
    public function test_add_item_fails_for_unavailable_offer(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->inactive()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/items', [
                'offer_id' => $offer->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Bu teklif mevcut değil.',
            ]);
    }

    /**
     * Test adding non-existent offer fails.
     */
    public function test_add_item_fails_for_nonexistent_offer(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/items', [
                'offer_id' => 99999,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offer_id']);
    }

    /**
     * Test updating item quantity.
     */
    public function test_update_item_updates_quantity(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $cartItem = CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 10,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Miktar güncellendi.',
            ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 10,
        ]);
    }

    /**
     * Test updating item quantity to zero removes item.
     */
    public function test_update_item_with_zero_quantity_removes_item(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $cartItem = CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 0,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Ürün sepetten kaldırıldı.',
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    /**
     * Test updating item with insufficient stock fails.
     */
    public function test_update_item_fails_with_insufficient_stock(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(10)
            ->available()
            ->create();

        $cartItem = CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 20,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Yeterli stok bulunmuyor.',
            ]);
    }

    /**
     * Test updating non-existent item fails.
     */
    public function test_update_item_fails_for_nonexistent_item(): void
    {
        Cart::factory()->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/cart/items/99999', [
                'quantity' => 5,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Ürün bulunamadı.',
            ]);
    }

    /**
     * Test removing item from cart.
     */
    public function test_remove_item_removes_from_cart(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $cartItem = CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/cart/items/{$cartItem->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Ürün sepetten kaldırıldı.',
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    /**
     * Test removing non-existent item fails.
     */
    public function test_remove_item_fails_for_nonexistent_item(): void
    {
        Cart::factory()->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson('/api/cart/items/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Ürün bulunamadı.',
            ]);
    }

    /**
     * Test clearing cart removes all items.
     */
    public function test_clear_removes_all_items(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();

        for ($i = 0; $i < 3; $i++) {
            $offer = Offer::factory()
                ->forProduct($product)
                ->forSeller($seller)
                ->withPrice(100.00 + ($i * 10))
                ->withStock(50)
                ->available()
                ->create();

            CartItem::factory()
                ->forCart($cart)
                ->forOffer($offer)
                ->withQuantity(1)
                ->create();
        }

        $this->assertEquals(3, $cart->items()->count());

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson('/api/cart');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Sepet temizlendi.',
                'item_count' => 0,
                'total' => 0,
            ]);

        $this->assertEquals(0, $cart->fresh()->items()->count());
    }

    /**
     * Test validating cart with valid items.
     */
    public function test_validate_returns_valid_for_good_cart(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
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
            ->withQuantity(5)
            ->withPriceAtAddition($offer->price)
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/validate');

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'issues' => [],
            ]);
    }

    /**
     * Test validating cart detects stock issues.
     */
    public function test_validate_detects_stock_issues(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(5)
            ->available()
            ->create();

        CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(10) // More than stock
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/validate');

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
            ]);

        $this->assertNotEmpty($response->json('issues'));
        $this->assertEquals('stock', $response->json('issues.0.type'));
    }

    /**
     * Test validating cart detects price changes.
     */
    public function test_validate_detects_price_changes(): void
    {
        $cart = Cart::factory()->forUser($this->user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(150.00) // Current price
            ->withStock(50)
            ->available()
            ->create();

        CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->withPriceAtAddition(100.00) // Old price
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/validate');

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
            ]);

        $this->assertNotEmpty($response->json('issues'));
        $this->assertEquals('price_changed', $response->json('issues.0.type'));
    }

    /**
     * Test unauthenticated user cannot access cart.
     */
    public function test_unauthenticated_user_cannot_access_cart(): void
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(401);
    }

    /**
     * Test unauthenticated user cannot add items.
     */
    public function test_unauthenticated_user_cannot_add_items(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $response = $this->postJson('/api/cart/items', [
            'offer_id' => $offer->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user cannot access another user's cart items.
     */
    public function test_user_cannot_modify_another_users_cart(): void
    {
        $otherUser = User::factory()->create(['is_verified' => true]);
        $cart = Cart::factory()->forUser($otherUser)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $cartItem = CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->create();

        // Try to update another user's cart item
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 10,
            ]);

        // Should not find the item (404) or cart not found
        $response->assertStatus(404);
    }

    /**
     * Test add item validates quantity max limit.
     */
    public function test_add_item_validates_quantity_max_limit(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(500)
            ->available()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/items', [
                'offer_id' => $offer->id,
                'quantity' => 150, // Exceeds max of 100
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test add item validates quantity min limit.
     */
    public function test_add_item_validates_quantity_min_limit(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/cart/items', [
                'offer_id' => $offer->id,
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }
}
