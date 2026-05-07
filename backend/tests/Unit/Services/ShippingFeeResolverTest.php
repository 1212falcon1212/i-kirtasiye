<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Setting;
use App\Models\User;
use App\Services\ShippingFeeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingFeeResolverTest extends TestCase
{
    use RefreshDatabase;

    protected ShippingFeeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ShippingFeeResolver;

        // Global default'lar
        Setting::setValue('shipping.flat_rate', 29.90);
        Setting::setValue('shipping.free_threshold', 2500);
        Setting::clearCache();
    }

    public function test_uses_global_default_when_seller_has_no_override(): void
    {
        $seller = User::factory()->seller()->create([
            'default_shipping_fee' => null,
            'free_shipping_threshold' => null,
        ]);

        $result = $this->resolver->resolveForSeller($seller, 1000);

        $this->assertSame(29.90, $result['fee']);
        $this->assertSame(29.90, $result['base_fee']);
        $this->assertSame(2500.0, $result['threshold']);
        $this->assertFalse($result['is_free']);
        $this->assertFalse($result['uses_seller_override']);
        $this->assertSame(1500.0, $result['remaining_for_free']);
    }

    public function test_global_threshold_makes_shipping_free_when_subtotal_meets_it(): void
    {
        $seller = User::factory()->seller()->create([
            'default_shipping_fee' => null,
            'free_shipping_threshold' => null,
        ]);

        $result = $this->resolver->resolveForSeller($seller, 5000);

        $this->assertSame(0.0, $result['fee']);
        $this->assertTrue($result['is_free']);
        $this->assertSame(0.0, $result['remaining_for_free']);
    }

    public function test_seller_override_replaces_global_fee(): void
    {
        $seller = User::factory()->seller()->create([
            'default_shipping_fee' => 49.50,
            'free_shipping_threshold' => 1500,
        ]);

        $result = $this->resolver->resolveForSeller($seller, 1000);

        $this->assertSame(49.50, $result['fee']);
        $this->assertSame(49.50, $result['base_fee']);
        $this->assertSame(1500.0, $result['threshold']);
        $this->assertFalse($result['is_free']);
        $this->assertTrue($result['uses_seller_override']);
        $this->assertSame(500.0, $result['remaining_for_free']);
    }

    public function test_seller_override_threshold_can_make_shipping_free(): void
    {
        $seller = User::factory()->seller()->create([
            'default_shipping_fee' => 49.50,
            'free_shipping_threshold' => 1500,
        ]);

        $result = $this->resolver->resolveForSeller($seller, 1500);

        $this->assertSame(0.0, $result['fee']);
        $this->assertTrue($result['is_free']);
        $this->assertTrue($result['uses_seller_override']);
    }

    public function test_partial_override_only_fee_uses_global_threshold(): void
    {
        $seller = User::factory()->seller()->create([
            'default_shipping_fee' => 19.90,
            'free_shipping_threshold' => null,
        ]);

        $result = $this->resolver->resolveForSeller($seller, 1000);

        $this->assertSame(19.90, $result['fee']);
        $this->assertSame(2500.0, $result['threshold']);
        $this->assertTrue($result['uses_seller_override']);
    }

    public function test_partial_override_only_threshold_uses_global_fee(): void
    {
        $seller = User::factory()->seller()->create([
            'default_shipping_fee' => null,
            'free_shipping_threshold' => 800,
        ]);

        $result = $this->resolver->resolveForSeller($seller, 500);

        $this->assertSame(29.90, $result['fee']);
        $this->assertSame(800.0, $result['threshold']);
        $this->assertTrue($result['uses_seller_override']);
    }

    public function test_zero_threshold_disables_free_shipping(): void
    {
        $seller = User::factory()->seller()->create([
            'default_shipping_fee' => 25,
            'free_shipping_threshold' => 0,
        ]);

        $result = $this->resolver->resolveForSeller($seller, 1_000_000);

        $this->assertSame(25.0, $result['fee']);
        $this->assertFalse($result['is_free']);
    }

    public function test_resolve_for_cart_aggregates_per_seller(): void
    {
        $sellerA = User::factory()->seller()->create([
            'default_shipping_fee' => 19.90,
            'free_shipping_threshold' => null,
        ]);

        $sellerB = User::factory()->seller()->create([
            'default_shipping_fee' => null,
            'free_shipping_threshold' => 800,
        ]);

        $result = $this->resolver->resolveForCart([
            ['seller_id' => $sellerA->id, 'subtotal' => 500],   // override fee 19.90, global threshold 2500 → 19.90
            ['seller_id' => $sellerB->id, 'subtotal' => 800],   // global fee 29.90, override threshold 800 → free
        ]);

        $this->assertSame(19.90, $result['per_seller'][$sellerA->id]['fee']);
        $this->assertSame(0.0, $result['per_seller'][$sellerB->id]['fee']);
        $this->assertTrue($result['per_seller'][$sellerB->id]['is_free']);
        $this->assertSame(19.90, $result['total']);
    }
}
