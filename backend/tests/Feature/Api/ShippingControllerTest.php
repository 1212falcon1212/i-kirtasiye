<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Shipping\ShippingManager;
use App\Services\ShippingCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingControllerTest extends TestCase
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

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    // ==========================================
    // CONFIG ENDPOINT TESTS
    // ==========================================

    /**
     * Test shipping config endpoint returns expected structure.
     */
    public function test_shipping_config_returns_expected_structure(): void
    {
        $this->mock(ShippingManager::class, function ($mock) {
            $mock->shouldReceive('getFlatRate')->once()->andReturn(29.99);
            $mock->shouldReceive('getFreeThreshold')->once()->andReturn(500.00);
            $mock->shouldReceive('getActiveProvider')->once()->andReturn('manual');
            $mock->shouldReceive('isEnabled')->once()->andReturn(true);
        });

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/shipping/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'flat_rate',
                'free_threshold',
                'provider',
                'enabled',
            ]);
    }

    // ==========================================
    // CALCULATE ENDPOINT TESTS
    // ==========================================

    /**
     * Test shipping calculation with subtotal below free threshold.
     */
    public function test_shipping_calculation_below_free_threshold(): void
    {
        $this->mock(ShippingManager::class, function ($mock) {
            $mock->shouldReceive('calculateShippingCost')->with(100.0)->andReturn(29.99);
            $mock->shouldReceive('getRemainingForFreeShipping')->with(100.0)->andReturn(400.01);
            $mock->shouldReceive('getFreeThreshold')->andReturn(500.00);
        });

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/shipping/calculate', [
                'subtotal' => 100,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'shipping_cost',
                'is_free',
                'remaining_for_free',
                'free_threshold',
            ])
            ->assertJsonPath('is_free', false);
    }

    /**
     * Test shipping calculation with subtotal above free threshold.
     */
    public function test_shipping_calculation_above_free_threshold(): void
    {
        $this->mock(ShippingManager::class, function ($mock) {
            $mock->shouldReceive('calculateShippingCost')->with(600.0)->andReturn(0.0);
            $mock->shouldReceive('getRemainingForFreeShipping')->with(600.0)->andReturn(0.0);
            $mock->shouldReceive('getFreeThreshold')->andReturn(500.00);
        });

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/shipping/calculate', [
                'subtotal' => 600,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_free', true)
            ->assertJsonPath('shipping_cost', 0);
    }

    /**
     * Test shipping calculation validates subtotal is required.
     */
    public function test_shipping_calculation_requires_subtotal(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/shipping/calculate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subtotal']);
    }

    /**
     * Test shipping calculation validates subtotal is numeric.
     */
    public function test_shipping_calculation_validates_subtotal_is_numeric(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/shipping/calculate', [
                'subtotal' => 'not-a-number',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subtotal']);
    }

    // ==========================================
    // GET OPTIONS ENDPOINT TESTS
    // ==========================================

    /**
     * Test get shipping options returns expected structure.
     */
    public function test_get_shipping_options_returns_expected_structure(): void
    {
        $this->mock(ShippingCalculatorService::class, function ($mock) {
            $mock->shouldReceive('getShippingOptions')
                ->with(5.0, 200.0)
                ->andReturn([
                    [
                        'provider' => 'Aras',
                        'cost' => 29.99,
                        'estimated_delivery' => '2-3 iş günü',
                    ],
                ]);
        });

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/shipping/options', [
                'total_desi' => 5,
                'order_amount' => 200,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'options',
                'total_desi',
                'order_amount',
            ]);
    }

    /**
     * Test get shipping options validates required fields.
     */
    public function test_get_shipping_options_validates_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/shipping/options', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['total_desi', 'order_amount']);
    }

    // ==========================================
    // DOWNLOAD LABEL TESTS
    // ==========================================

    /**
     * Test download label for order with label.
     */
    public function test_download_label_for_order_with_label(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->forUser($buyer)->shipped()->create([
            'shipping_label_url' => 'https://example.com/label.pdf',
        ]);
        OrderItem::factory()->forOrder($order)->forSeller($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/shipping/orders/{$order->id}/label");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('label_url', 'https://example.com/label.pdf');
    }

    /**
     * Test download label returns 404 when no label.
     */
    public function test_download_label_returns_404_when_no_label(): void
    {
        $buyer = User::factory()->create();
        $order = Order::factory()->forUser($buyer)->create([
            'shipping_label_url' => null,
        ]);
        OrderItem::factory()->forOrder($order)->forSeller($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/shipping/orders/{$order->id}/label");

        $response->assertStatus(404);
    }

    /**
     * Test unauthorized user cannot download label.
     */
    public function test_unauthorized_user_cannot_download_label(): void
    {
        $otherUser = User::factory()->create();
        $otherToken = $otherUser->createToken('other-token')->plainTextToken;

        $buyer = User::factory()->create();
        $order = Order::factory()->forUser($buyer)->shipped()->create([
            'shipping_label_url' => 'https://example.com/label.pdf',
        ]);
        OrderItem::factory()->forOrder($order)->forSeller($this->user)->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$otherToken])
            ->getJson("/api/shipping/orders/{$order->id}/label");

        $response->assertStatus(403);
    }

    // ==========================================
    // UNAUTHENTICATED ACCESS
    // ==========================================

    /**
     * Test unauthenticated user cannot access shipping endpoints.
     */
    public function test_unauthenticated_user_cannot_access_shipping_endpoints(): void
    {
        $this->getJson('/api/shipping/config')->assertStatus(401);
        $this->postJson('/api/shipping/calculate', ['subtotal' => 100])->assertStatus(401);
        $this->postJson('/api/shipping/options', [])->assertStatus(401);
    }
}
