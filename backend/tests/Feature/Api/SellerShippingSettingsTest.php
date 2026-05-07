<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerShippingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('shipping.flat_rate', 29.90);
        Setting::setValue('shipping.free_threshold', 2500);
        Setting::clearCache();

        $this->seller = User::factory()->seller()->create([
            'default_shipping_fee' => null,
            'free_shipping_threshold' => null,
        ]);
        $this->token = $this->seller->createToken('test-token')->plainTextToken;
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_show_returns_global_defaults_when_no_override(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/seller/shipping-settings');

        $response->assertStatus(200)
            ->assertJsonPath('default_shipping_fee', null)
            ->assertJsonPath('free_shipping_threshold', null)
            ->assertJsonPath('effective_shipping_fee', 29.90)
            ->assertJsonPath('effective_threshold', 2500)
            ->assertJsonPath('uses_override', false);
    }

    public function test_update_persists_seller_override(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->patchJson('/api/seller/shipping-settings', [
                'default_shipping_fee' => 49.50,
                'free_shipping_threshold' => 1500,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('default_shipping_fee', 49.50)
            ->assertJsonPath('free_shipping_threshold', 1500)
            ->assertJsonPath('effective_shipping_fee', 49.50)
            ->assertJsonPath('effective_threshold', 1500)
            ->assertJsonPath('uses_override', true);

        $this->seller->refresh();
        $this->assertSame('49.50', $this->seller->default_shipping_fee);
        $this->assertSame('1500.00', $this->seller->free_shipping_threshold);
    }

    public function test_update_accepts_null_to_revert_to_global(): void
    {
        $this->seller->update([
            'default_shipping_fee' => 49.50,
            'free_shipping_threshold' => 1500,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson('/api/seller/shipping-settings', [
                'default_shipping_fee' => null,
                'free_shipping_threshold' => null,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('default_shipping_fee', null)
            ->assertJsonPath('free_shipping_threshold', null)
            ->assertJsonPath('uses_override', false);

        $this->seller->refresh();
        $this->assertNull($this->seller->default_shipping_fee);
        $this->assertNull($this->seller->free_shipping_threshold);
    }

    public function test_update_rejects_negative_values(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->patchJson('/api/seller/shipping-settings', [
                'default_shipping_fee' => -10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_shipping_fee']);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $this->getJson('/api/seller/shipping-settings')->assertStatus(401);
        $this->patchJson('/api/seller/shipping-settings', [])->assertStatus(401);
    }
}
