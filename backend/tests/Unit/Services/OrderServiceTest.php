<?php

namespace Tests\Unit\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
        $this->orderService = new OrderService($this->cartService);
    }

    /**
     * Test creating an order from cart successfully.
     */
    public function test_create_from_cart_creates_order_successfully(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $cart = Cart::factory()->forUser($buyer)->create();

        $category = Category::factory()->withCommissionRate(10)->create();
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
            ->withQuantity(5)
            ->withPriceAtAddition($offer->price)
            ->create();

        $shippingAddress = [
            'name' => 'Test User',
            'phone' => '5551234567',
            'address' => 'Test Address 123',
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'postal_code' => '34000',
        ];

        $order = $this->orderService->createFromCart($cart, $shippingAddress, 'Test notes');

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($buyer->id, $order->user_id);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals($shippingAddress, $order->shipping_address);
        $this->assertEquals('Test notes', $order->notes);
        $this->assertNotEmpty($order->order_number);
        $this->assertCount(1, $order->items);

        // Check order item
        $orderItem = $order->items->first();
        $this->assertEquals($product->id, $orderItem->product_id);
        $this->assertEquals($offer->id, $orderItem->offer_id);
        $this->assertEquals($seller->id, $orderItem->seller_id);
        $this->assertEquals(5, $orderItem->quantity);
        $this->assertEquals(100.00, $orderItem->unit_price);
        $this->assertEquals(500.00, $orderItem->total_price);

        // Check stock was decreased
        $this->assertEquals(45, $offer->fresh()->stock);

        // Check cart was marked as converted
        $this->assertEquals('converted', $cart->fresh()->status);
    }

    /**
     * Test creating order from empty cart throws exception.
     */
    public function test_create_from_cart_throws_exception_for_empty_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();

        $shippingAddress = [
            'name' => 'Test User',
            'address' => 'Test Address',
            'city' => 'Istanbul',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Sepetiniz boş.');

        $this->orderService->createFromCart($cart, $shippingAddress);
    }

    /**
     * Test creating order with unavailable items throws exception.
     */
    public function test_create_from_cart_throws_exception_for_unavailable_items(): void
    {
        $buyer = User::factory()->create();
        $cart = Cart::factory()->forUser($buyer)->create();
        $product = Product::factory()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->unavailable()
            ->create();

        CartItem::factory()
            ->forCart($cart)
            ->forOffer($offer)
            ->create();

        $shippingAddress = [
            'name' => 'Test User',
            'address' => 'Test Address',
            'city' => 'Istanbul',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Sepetinizde düzeltilmesi gereken sorunlar var.');

        $this->orderService->createFromCart($cart, $shippingAddress);
    }

    /**
     * Test cancelling a pending order successfully.
     */
    public function test_cancel_order_cancels_pending_order(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create();
        $product = Product::factory()->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withStock(45) // After order was created
            ->available()
            ->create();

        $order = Order::factory()->forUser($user)->create(['status' => 'pending']);
        $order->items()->create([
            'product_id' => $product->id,
            'offer_id' => $offer->id,
            'seller_id' => $seller->id,
            'quantity' => 5,
            'unit_price' => 100.00,
            'total_price' => 500.00,
            'commission_rate' => 10,
            'commission_amount' => 50.00,
            'seller_payout_amount' => 450.00,
        ]);

        $this->orderService->cancelOrder($order);

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(50, $offer->fresh()->stock); // Stock restored
    }

    /**
     * Test cancelling a confirmed order successfully.
     */
    public function test_cancel_order_cancels_confirmed_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->forUser($user)->confirmed()->create();

        $this->orderService->cancelOrder($order);

        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    /**
     * Test cancelling a shipped order throws exception.
     */
    public function test_cancel_order_throws_exception_for_shipped_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->forUser($user)->shipped()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bu sipariş iptal edilemez.');

        $this->orderService->cancelOrder($order);
    }

    /**
     * Test cancelling a delivered order throws exception.
     */
    public function test_cancel_order_throws_exception_for_delivered_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->forUser($user)->delivered()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bu sipariş iptal edilemez.');

        $this->orderService->cancelOrder($order);
    }

    /**
     * Test generate order number returns correct format.
     */
    public function test_generate_order_number_returns_correct_format(): void
    {
        $orderNumber = $this->orderService->generateOrderNumber();

        // Format: EPZ + YYMMDD + 4 digit sequence + 4 random chars
        // Example: EPZ2401150001ABCD
        $this->assertMatchesRegularExpression('/^EPZ\d{6}\d{4}[A-Z0-9]{4}$/', $orderNumber);
        $this->assertStringStartsWith('EPZ', $orderNumber);
        $this->assertEquals(17, strlen($orderNumber));
    }

    /**
     * Test generate order number is unique.
     */
    public function test_generate_order_number_is_unique(): void
    {
        $orderNumbers = [];

        for ($i = 0; $i < 10; $i++) {
            $orderNumber = $this->orderService->generateOrderNumber();
            $this->assertNotContains($orderNumber, $orderNumbers);
            $orderNumbers[] = $orderNumber;
        }
    }

    /**
     * Test generate order number includes current date.
     */
    public function test_generate_order_number_includes_current_date(): void
    {
        $orderNumber = $this->orderService->generateOrderNumber();
        $expectedDate = now()->format('ymd');

        $this->assertStringContainsString($expectedDate, $orderNumber);
    }
}
