<?php

namespace Tests\Unit\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
    }

    /**
     * Test adding an item to the cart.
     */
    public function test_add_item_creates_new_cart_item(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $cartItem = $this->cartService->addItem($cart, $offer, 5);

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertEquals($cart->id, $cartItem->cart_id);
        $this->assertEquals($offer->id, $cartItem->offer_id);
        $this->assertEquals($product->id, $cartItem->product_id);
        $this->assertEquals($seller->id, $cartItem->seller_id);
        $this->assertEquals(5, $cartItem->quantity);
        $this->assertEquals(100.00, $cartItem->price_at_addition);
    }

    /**
     * Test adding item increases quantity if already exists in cart.
     */
    public function test_add_item_increases_quantity_for_existing_item(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withStock(100)
            ->available()
            ->create();

        // Add item first time
        $this->cartService->addItem($cart, $offer, 3);

        // Add same item again
        $cartItem = $this->cartService->addItem($cart, $offer, 2);

        $this->assertEquals(5, $cartItem->quantity);
        $this->assertCount(1, $cart->fresh()->items);
    }

    /**
     * Test adding item throws exception when offer is unavailable.
     */
    public function test_add_item_throws_exception_for_unavailable_offer(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $offer = Offer::factory()->inactive()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bu teklif mevcut değil.');

        $this->cartService->addItem($cart, $offer, 1);
    }

    /**
     * Test adding item throws exception when stock is insufficient.
     */
    public function test_add_item_throws_exception_for_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $offer = Offer::factory()->withStock(5)->available()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Yeterli stok bulunmuyor.');

        $this->cartService->addItem($cart, $offer, 10);
    }

    /**
     * Test updating item quantity successfully.
     */
    public function test_update_item_quantity_updates_successfully(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $offer = Offer::factory()->withStock(50)->available()->create();

        $cartItem = CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->create();

        $updatedItem = $this->cartService->updateItemQuantity($cartItem, 10);

        $this->assertEquals(10, $updatedItem->quantity);
    }

    /**
     * Test updating item quantity removes item when quantity is zero or less.
     */
    public function test_update_item_quantity_removes_item_when_zero(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $offer = Offer::factory()->withStock(50)->available()->create();

        $cartItem = CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ürün sepetten kaldırıldı.');

        $this->cartService->updateItemQuantity($cartItem, 0);

        $this->assertNull(CartItem::find($cartItem->id));
    }

    /**
     * Test updating item quantity throws exception for insufficient stock.
     */
    public function test_update_item_quantity_throws_exception_for_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $offer = Offer::factory()->withStock(10)->available()->create();

        $cartItem = CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Yeterli stok bulunmuyor.');

        $this->cartService->updateItemQuantity($cartItem, 20);
    }

    /**
     * Test cart validation returns empty array for valid cart.
     */
    public function test_validate_cart_returns_empty_for_valid_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $offer = Offer::factory()->withStock(50)->available()->create();

        CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->withPriceAtAddition($offer->price)
            ->create();

        $issues = $this->cartService->validateCart($cart);

        $this->assertEmpty($issues);
    }

    /**
     * Test cart validation detects unavailable offers.
     */
    public function test_validate_cart_detects_unavailable_offers(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $product = Product::factory()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->unavailable()
            ->create();

        CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->create();

        $issues = $this->cartService->validateCart($cart);

        $this->assertNotEmpty($issues);
        $this->assertEquals('unavailable', $issues[0]['type']);
        $this->assertEquals('Bu ürün artık mevcut değil.', $issues[0]['message']);
    }

    /**
     * Test cart validation detects insufficient stock.
     */
    public function test_validate_cart_detects_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $offer = Offer::factory()->withStock(5)->available()->create();

        CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(10)
            ->create();

        $issues = $this->cartService->validateCart($cart);

        $this->assertNotEmpty($issues);
        $this->assertEquals('stock', $issues[0]['type']);
        $this->assertStringContains('Stok yetersiz', $issues[0]['message']);
    }

    /**
     * Test cart validation detects price changes.
     */
    public function test_validate_cart_detects_price_changes(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $offer = Offer::factory()->withPrice(150.00)->withStock(50)->available()->create();

        CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->withQuantity(5)
            ->withPriceAtAddition(100.00) // Old price different from current
            ->create();

        $issues = $this->cartService->validateCart($cart);

        $this->assertNotEmpty($issues);
        $this->assertEquals('price_changed', $issues[0]['type']);
        $this->assertStringContains('Fiyat değişti', $issues[0]['message']);
    }

    /**
     * Helper method to check if string contains substring.
     */
    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
