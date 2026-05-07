<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Helper for auth headers.
     */
    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    // ==========================================
    // INDEX TESTS
    // ==========================================

    /**
     * Test user can list their addresses.
     */
    public function test_user_can_list_addresses(): void
    {
        UserAddress::factory()->count(3)->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/user/addresses');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test user only sees own addresses.
     */
    public function test_user_only_sees_own_addresses(): void
    {
        UserAddress::factory()->count(2)->forUser($this->user)->create();
        $otherUser = User::factory()->create();
        UserAddress::factory()->count(3)->forUser($otherUser)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/user/addresses');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test unauthenticated user cannot list addresses.
     */
    public function test_unauthenticated_user_cannot_list_addresses(): void
    {
        $response = $this->getJson('/api/user/addresses');

        $response->assertStatus(401);
    }

    // ==========================================
    // STORE TESTS
    // ==========================================

    /**
     * Test user can create an address.
     */
    public function test_user_can_create_address(): void
    {
        $addressData = [
            'title' => 'Ev Adresi',
            'name' => 'Test Kırtasiye',
            'phone' => '5551234567',
            'address' => 'Test Sokak No:1',
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'postal_code' => '34000',
            'is_default' => false,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/user/addresses', $addressData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Adres başarıyla eklendi',
            ]);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'title' => 'Ev Adresi',
            'city' => 'Istanbul',
        ]);
    }

    /**
     * Test first address becomes default automatically.
     */
    public function test_first_address_becomes_default_automatically(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/user/addresses', [
                'title' => 'Ev',
                'name' => 'Test',
                'phone' => '5551234567',
                'address' => 'Test Sokak',
                'city' => 'Istanbul',
                'district' => 'Kadikoy',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);
    }

    /**
     * Test setting new address as default unsets previous default.
     */
    public function test_setting_new_default_unsets_previous(): void
    {
        $existingAddress = UserAddress::factory()
            ->forUser($this->user)
            ->default()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/user/addresses', [
                'title' => 'Yeni Adres',
                'name' => 'Test',
                'phone' => '5551234567',
                'address' => 'Yeni Sokak',
                'city' => 'Ankara',
                'district' => 'Cankaya',
                'is_default' => true,
            ]);

        $response->assertStatus(201);

        // Previous default should now be false
        $this->assertFalse($existingAddress->fresh()->is_default);
    }

    /**
     * Test store validation requires mandatory fields.
     */
    public function test_store_validation_requires_mandatory_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/user/addresses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'name', 'phone', 'address', 'city', 'district']);
    }

    // ==========================================
    // UPDATE TESTS
    // ==========================================

    /**
     * Test user can update their own address.
     */
    public function test_user_can_update_own_address(): void
    {
        $address = UserAddress::factory()->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/user/addresses/{$address->id}", [
                'title' => 'Updated Title',
                'city' => 'Ankara',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Adres güncellendi',
            ]);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'title' => 'Updated Title',
            'city' => 'Ankara',
        ]);
    }

    /**
     * Test user cannot update another user's address.
     */
    public function test_user_cannot_update_another_users_address(): void
    {
        $otherUser = User::factory()->create();
        $address = UserAddress::factory()->forUser($otherUser)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/user/addresses/{$address->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // DELETE TESTS
    // ==========================================

    /**
     * Test user can delete their own address.
     */
    public function test_user_can_delete_own_address(): void
    {
        $address = UserAddress::factory()->forUser($this->user)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/user/addresses/{$address->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Adres silindi',
            ]);

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $address->id,
        ]);
    }

    /**
     * Test user cannot delete another user's address.
     */
    public function test_user_cannot_delete_another_users_address(): void
    {
        $otherUser = User::factory()->create();
        $address = UserAddress::factory()->forUser($otherUser)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/user/addresses/{$address->id}");

        $response->assertStatus(403);
    }
}
