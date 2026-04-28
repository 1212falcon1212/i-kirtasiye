<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seller = User::factory()->seller()->create();
        $this->token = $this->seller->createToken('test-token')->plainTextToken;
    }

    /**
     * Helper method to make authenticated requests
     */
    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    /**
     * Helper method to create valid campaign data for store requests
     */
    protected function validCampaignData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Kampanya',
            'description' => 'Test kampanya aciklamasi',
            'type' => Campaign::TYPE_STORE_DISCOUNT,
            'discount_rate' => 15,
            'min_purchase_amount' => 100,
            'starts_at' => now()->addDay()->format('Y-m-d'),
            'ends_at' => now()->addDays(30)->format('Y-m-d'),
        ], $overrides);
    }

    // ==========================================
    // INDEX TESTS - List campaigns for seller
    // ==========================================

    public function test_index_returns_paginated_campaigns_for_authenticated_seller(): void
    {
        // Arrange
        Campaign::factory()->forSeller($this->seller)->count(5)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/campaigns');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'campaigns',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(5, 'campaigns');
    }

    public function test_index_only_returns_campaigns_owned_by_seller(): void
    {
        // Arrange
        $otherSeller = User::factory()->seller()->create();
        Campaign::factory()->forSeller($this->seller)->count(3)->create();
        Campaign::factory()->forSeller($otherSeller)->count(2)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/campaigns');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'campaigns');
    }

    public function test_index_filters_by_status(): void
    {
        // Arrange
        Campaign::factory()->forSeller($this->seller)->pending()->count(2)->create();
        Campaign::factory()->forSeller($this->seller)->active()->count(3)->create();
        Campaign::factory()->forSeller($this->seller)->inactive()->count(1)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/campaigns?status=active');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'campaigns');

        foreach ($response->json('campaigns') as $campaign) {
            $this->assertEquals(Campaign::STATUS_ACTIVE, $campaign['status']);
        }
    }

    public function test_index_respects_per_page_parameter(): void
    {
        // Arrange
        Campaign::factory()->forSeller($this->seller)->count(10)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/campaigns?per_page=5');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonCount(5, 'campaigns');
    }

    public function test_index_includes_product_and_gift_product_relations(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $giftProduct = Product::factory()->forCategory($category)->create();

        Campaign::factory()
            ->forSeller($this->seller)
            ->giftProduct($giftProduct)
            ->create(['product_id' => $product->id]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/campaigns');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'campaigns' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'product',
                        'gift_product',
                    ],
                ],
            ]);
    }

    public function test_index_requires_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/campaigns');

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // STORE TESTS - Create campaign
    // ==========================================

    public function test_store_creates_campaign_successfully(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'campaign' => [
                    'id',
                    'name',
                    'type',
                    'discount_rate',
                    'status',
                ],
            ])
            ->assertJsonPath('campaign.name', 'Test Kampanya')
            ->assertJsonPath('campaign.status', Campaign::STATUS_PENDING);

        $this->assertDatabaseHas('campaigns', [
            'seller_id' => $this->seller->id,
            'name' => 'Test Kampanya',
            'status' => Campaign::STATUS_PENDING,
        ]);
    }

    public function test_store_creates_product_discount_campaign(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();

        $campaignData = $this->validCampaignData([
            'type' => Campaign::TYPE_PRODUCT_DISCOUNT,
            'product_id' => $product->id,
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('campaign.type', Campaign::TYPE_PRODUCT_DISCOUNT)
            ->assertJsonPath('campaign.product_id', $product->id);
    }

    public function test_store_creates_brand_discount_campaign(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData([
            'type' => Campaign::TYPE_BRAND_DISCOUNT,
            'brand' => 'Bayer',
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('campaign.type', Campaign::TYPE_BRAND_DISCOUNT)
            ->assertJsonPath('campaign.brand', 'Bayer');
    }

    public function test_store_creates_gift_product_campaign(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $giftProduct = Product::factory()->forCategory($category)->create();

        $campaignData = $this->validCampaignData([
            'type' => Campaign::TYPE_GIFT_PRODUCT,
            'discount_rate' => null,
            'gift_product_id' => $giftProduct->id,
            'gift_quantity' => 2,
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('campaign.type', Campaign::TYPE_GIFT_PRODUCT)
            ->assertJsonPath('campaign.gift_product_id', $giftProduct->id)
            ->assertJsonPath('campaign.gift_quantity', 2);
    }

    public function test_store_validates_required_fields(): void
    {
        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'starts_at', 'ends_at']);
    }

    public function test_store_validates_discount_rate_required_for_non_gift_types(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData([
            'type' => Campaign::TYPE_STORE_DISCOUNT,
            'discount_rate' => null,
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_rate']);
    }

    public function test_store_validates_product_id_required_for_product_discount(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData([
            'type' => Campaign::TYPE_PRODUCT_DISCOUNT,
            'product_id' => null,
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_store_validates_brand_required_for_brand_discount(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData([
            'type' => Campaign::TYPE_BRAND_DISCOUNT,
            'brand' => null,
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand']);
    }

    public function test_store_validates_gift_product_id_required_for_gift_campaign(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData([
            'type' => Campaign::TYPE_GIFT_PRODUCT,
            'gift_product_id' => null,
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gift_product_id']);
    }

    public function test_store_validates_discount_rate_range(): void
    {
        // Arrange - Test below minimum
        $campaignData = $this->validCampaignData(['discount_rate' => 0]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_rate']);

        // Arrange - Test above maximum
        $campaignData = $this->validCampaignData(['discount_rate' => 101]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_rate']);
    }

    public function test_store_validates_starts_at_must_be_today_or_later(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData([
            'starts_at' => now()->subDay()->format('Y-m-d'),
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_store_validates_ends_at_must_be_after_starts_at(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData([
            'starts_at' => now()->addDays(10)->format('Y-m-d'),
            'ends_at' => now()->addDays(5)->format('Y-m-d'),
        ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ends_at']);
    }

    public function test_store_denies_seller_with_low_rating(): void
    {
        // Arrange - Create seller with low rating (below 7)
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $buyer = User::factory()->create();

        // Create 5 reviews with rating 3 (which gives seller_score of 6)
        for ($i = 0; $i < 5; $i++) {
            Review::factory()
                ->forSeller($this->seller)
                ->forBuyer($buyer)
                ->forProduct($product)
                ->approved()
                ->withRating(3)
                ->create();
        }

        // Refresh seller to get updated rating
        $this->seller->refresh();

        $campaignData = $this->validCampaignData();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(403)
            ->assertJsonPath('message', "Puanınız 7'nin altında olduğu için kampanya oluşturamazsınız.");
    }

    public function test_store_allows_seller_with_no_reviews(): void
    {
        // Arrange - New seller without any reviews
        $campaignData = $this->validCampaignData();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(201);
    }

    public function test_store_requires_authentication(): void
    {
        // Act
        $response = $this->postJson('/api/campaigns', $this->validCampaignData());

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // SHOW TESTS - Get single campaign
    // ==========================================

    public function test_show_returns_campaign_details(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->storeDiscount()
            ->create(['name' => 'Test Kampanya']);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'campaign' => [
                    'id',
                    'name',
                    'description',
                    'type',
                    'discount_rate',
                    'status',
                    'starts_at',
                    'ends_at',
                ],
            ])
            ->assertJsonPath('campaign.name', 'Test Kampanya');
    }

    public function test_show_returns_campaign_with_product_relation(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create(['name' => 'Test Urun']);

        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->productDiscount($product)
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('campaign.product.name', 'Test Urun');
    }

    public function test_show_denies_access_to_other_sellers_campaign(): void
    {
        // Arrange
        $otherSeller = User::factory()->seller()->create();
        $campaign = Campaign::factory()->forSeller($otherSeller)->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJsonPath('message', 'Bu kampanyayı görüntüleme yetkiniz yok.');
    }

    public function test_show_returns_404_for_nonexistent_campaign(): void
    {
        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/campaigns/99999');

        // Assert
        $response->assertStatus(404);
    }

    public function test_show_requires_authentication(): void
    {
        // Arrange
        $campaign = Campaign::factory()->forSeller($this->seller)->create();

        // Act
        $response = $this->getJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // UPDATE TESTS - Update campaign
    // ==========================================

    public function test_update_updates_pending_campaign_successfully(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->pending()
            ->create(['name' => 'Eski Isim']);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/campaigns/{$campaign->id}", [
                'name' => 'Yeni Isim',
                'discount_rate' => 25,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Kampanya başarıyla güncellendi.')
            ->assertJsonPath('campaign.name', 'Yeni Isim')
            ->assertJsonPath('campaign.discount_rate', '25.00');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Yeni Isim',
        ]);
    }

    public function test_update_updates_inactive_campaign_successfully(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->inactive()
            ->create(['name' => 'Eski Isim']);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/campaigns/{$campaign->id}", [
                'name' => 'Yeni Isim',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('campaign.name', 'Yeni Isim');
    }

    public function test_update_denies_update_of_active_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/campaigns/{$campaign->id}", [
                'name' => 'Yeni Isim',
            ]);

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('message', 'Aktif veya reddedilen kampanyalar düzenlenemez.');
    }

    public function test_update_denies_update_of_rejected_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->rejected()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/campaigns/{$campaign->id}", [
                'name' => 'Yeni Isim',
            ]);

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('message', 'Aktif veya reddedilen kampanyalar düzenlenemez.');
    }

    public function test_update_validates_discount_rate_range(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->pending()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/campaigns/{$campaign->id}", [
                'discount_rate' => 150,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_rate']);
    }

    public function test_update_validates_ends_at_after_starts_at(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->pending()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/campaigns/{$campaign->id}", [
                'starts_at' => now()->addDays(20)->format('Y-m-d'),
                'ends_at' => now()->addDays(10)->format('Y-m-d'),
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ends_at']);
    }

    public function test_update_denies_access_to_other_sellers_campaign(): void
    {
        // Arrange
        $otherSeller = User::factory()->seller()->create();
        $campaign = Campaign::factory()->forSeller($otherSeller)->pending()->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/campaigns/{$campaign->id}", [
                'name' => 'Yeni Isim',
            ]);

        // Assert
        $response->assertStatus(403)
            ->assertJsonPath('message', 'Bu kampanyayı düzenleme yetkiniz yok.');
    }

    public function test_update_requires_authentication(): void
    {
        // Arrange
        $campaign = Campaign::factory()->forSeller($this->seller)->create();

        // Act
        $response = $this->putJson("/api/campaigns/{$campaign->id}", [
            'name' => 'Yeni Isim',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // DESTROY TESTS - Delete campaign
    // ==========================================

    public function test_destroy_deletes_pending_campaign_successfully(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->pending()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Kampanya başarıyla silindi.');

        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
    }

    public function test_destroy_deletes_inactive_campaign_successfully(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->inactive()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
    }

    public function test_destroy_deletes_rejected_campaign_successfully(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->rejected()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
    }

    public function test_destroy_denies_deletion_of_active_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('message', 'Aktif kampanyalar silinemez. Önce pasife alın.');
    }

    public function test_destroy_denies_access_to_other_sellers_campaign(): void
    {
        // Arrange
        $otherSeller = User::factory()->seller()->create();
        $campaign = Campaign::factory()->forSeller($otherSeller)->pending()->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(403)
            ->assertJsonPath('message', 'Bu kampanyayı silme yetkiniz yok.');
    }

    public function test_destroy_requires_authentication(): void
    {
        // Arrange
        $campaign = Campaign::factory()->forSeller($this->seller)->create();

        // Act
        $response = $this->deleteJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // TOGGLE STATUS TESTS
    // ==========================================

    public function test_toggle_status_deactivates_active_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/campaigns/{$campaign->id}/toggle-status");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Kampanya pasife alındı.')
            ->assertJsonPath('campaign.status', Campaign::STATUS_INACTIVE);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => Campaign::STATUS_INACTIVE,
        ]);
    }

    public function test_toggle_status_activates_inactive_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->inactive()
            ->withDates(now()->subDay(), now()->addDays(30))
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/campaigns/{$campaign->id}/toggle-status");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Kampanya aktifleştirildi.')
            ->assertJsonPath('campaign.status', Campaign::STATUS_ACTIVE);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => Campaign::STATUS_ACTIVE,
        ]);
    }

    public function test_toggle_status_denies_activation_of_expired_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->create([
                'status' => Campaign::STATUS_INACTIVE,
                'starts_at' => now()->subDays(60),
                'ends_at' => now()->subDays(30),
            ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/campaigns/{$campaign->id}/toggle-status");

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('message', 'Süresi dolmuş kampanya aktifleştirilemez.');
    }

    public function test_toggle_status_denies_toggle_of_pending_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->pending()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/campaigns/{$campaign->id}/toggle-status");

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('message', 'Sadece onaylanmış kampanyaların durumu değiştirilebilir.');
    }

    public function test_toggle_status_denies_toggle_of_rejected_campaign(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->rejected()
            ->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/campaigns/{$campaign->id}/toggle-status");

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('message', 'Sadece onaylanmış kampanyaların durumu değiştirilebilir.');
    }

    public function test_toggle_status_denies_access_to_other_sellers_campaign(): void
    {
        // Arrange
        $otherSeller = User::factory()->seller()->create();
        $campaign = Campaign::factory()->forSeller($otherSeller)->active()->create();

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/campaigns/{$campaign->id}/toggle-status");

        // Assert
        $response->assertStatus(403)
            ->assertJsonPath('message', 'Bu kampanyayı düzenleme yetkiniz yok.');
    }

    public function test_toggle_status_requires_authentication(): void
    {
        // Arrange
        $campaign = Campaign::factory()->forSeller($this->seller)->active()->create();

        // Act
        $response = $this->postJson("/api/campaigns/{$campaign->id}/toggle-status");

        // Assert
        $response->assertStatus(401);
    }

    // ==========================================
    // ACTIVE CAMPAIGNS TESTS - Public endpoint
    // ==========================================

    public function test_active_returns_only_active_campaigns(): void
    {
        // Arrange
        $seller1 = User::factory()->seller()->create();
        $seller2 = User::factory()->seller()->create();

        // Create various campaign states
        Campaign::factory()->forSeller($seller1)->active()->count(3)->create();
        Campaign::factory()->forSeller($seller2)->active()->count(2)->create();
        Campaign::factory()->forSeller($seller1)->pending()->count(2)->create();
        Campaign::factory()->forSeller($seller1)->inactive()->count(1)->create();

        // Act - This is a public endpoint, no authentication needed
        $response = $this->getJson('/api/campaigns/active');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'campaigns',
                'pagination',
            ])
            ->assertJsonCount(5, 'campaigns');

        foreach ($response->json('campaigns') as $campaign) {
            $this->assertEquals(Campaign::STATUS_ACTIVE, $campaign['status']);
        }
    }

    public function test_active_filters_by_type(): void
    {
        // Arrange
        Campaign::factory()->forSeller($this->seller)->active()->storeDiscount()->count(3)->create();
        Campaign::factory()->forSeller($this->seller)->active()->brandDiscount()->count(2)->create();

        // Act - This is a public endpoint
        $response = $this->getJson('/api/campaigns/active?type=' . Campaign::TYPE_STORE_DISCOUNT);

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'campaigns');

        foreach ($response->json('campaigns') as $campaign) {
            $this->assertEquals(Campaign::TYPE_STORE_DISCOUNT, $campaign['type']);
        }
    }

    public function test_active_filters_by_seller_id(): void
    {
        // Arrange
        $otherSeller = User::factory()->seller()->create();
        Campaign::factory()->forSeller($this->seller)->active()->count(3)->create();
        Campaign::factory()->forSeller($otherSeller)->active()->count(2)->create();

        // Act - This is a public endpoint
        $response = $this->getJson("/api/campaigns/active?seller_id={$this->seller->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'campaigns');
    }

    public function test_active_includes_seller_information(): void
    {
        // Arrange
        Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        // Act - This is a public endpoint
        $response = $this->getJson('/api/campaigns/active');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'campaigns' => [
                    '*' => [
                        'id',
                        'name',
                        'seller' => [
                            'id',
                            'pharmacy_name',
                        ],
                    ],
                ],
            ]);
    }

    public function test_active_respects_per_page_parameter(): void
    {
        // Arrange
        Campaign::factory()->forSeller($this->seller)->active()->count(10)->create();

        // Act - This is a public endpoint
        $response = $this->getJson('/api/campaigns/active?per_page=5');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonCount(5, 'campaigns');
    }

    public function test_active_excludes_expired_campaigns(): void
    {
        // Arrange
        // Active but not expired
        Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        // Active status but date expired
        Campaign::factory()
            ->forSeller($this->seller)
            ->expired()
            ->create();

        // Act - This is a public endpoint
        $response = $this->getJson('/api/campaigns/active');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'campaigns');
    }

    public function test_active_excludes_campaigns_not_yet_started(): void
    {
        // Arrange
        // Active and within date range
        Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->create();

        // Active status but not yet started
        Campaign::factory()
            ->forSeller($this->seller)
            ->create([
                'status' => Campaign::STATUS_ACTIVE,
                'starts_at' => now()->addDays(5),
                'ends_at' => now()->addDays(35),
            ]);

        // Act - This is a public endpoint
        $response = $this->getJson('/api/campaigns/active');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'campaigns');
    }

    public function test_active_campaigns_sorted_by_ends_at_ascending(): void
    {
        // Arrange
        Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->withDates(now()->subDay(), now()->addDays(30))
            ->create(['name' => 'Campaign 30 days']);

        Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->withDates(now()->subDay(), now()->addDays(10))
            ->create(['name' => 'Campaign 10 days']);

        Campaign::factory()
            ->forSeller($this->seller)
            ->active()
            ->withDates(now()->subDay(), now()->addDays(20))
            ->create(['name' => 'Campaign 20 days']);

        // Act - This is a public endpoint
        $response = $this->getJson('/api/campaigns/active');

        // Assert
        $response->assertStatus(200);
        $campaigns = $response->json('campaigns');

        $this->assertEquals('Campaign 10 days', $campaigns[0]['name']);
        $this->assertEquals('Campaign 20 days', $campaigns[1]['name']);
        $this->assertEquals('Campaign 30 days', $campaigns[2]['name']);
    }

    public function test_active_is_accessible_without_authentication(): void
    {
        // Arrange
        Campaign::factory()->forSeller($this->seller)->active()->create();

        // Act - This is a public endpoint, no authentication needed
        $response = $this->getJson('/api/campaigns/active');

        // Assert - Public endpoint should return 200 without auth
        $response->assertStatus(200)
            ->assertJsonStructure([
                'campaigns',
                'pagination',
            ]);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    public function test_empty_campaigns_returns_empty_array(): void
    {
        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/campaigns');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(0, 'campaigns')
            ->assertJsonPath('pagination.total', 0);
    }

    public function test_index_returns_campaigns_in_descending_order_by_created_at(): void
    {
        // Arrange
        $campaign1 = Campaign::factory()
            ->forSeller($this->seller)
            ->create(['name' => 'First', 'created_at' => now()->subDays(2)]);

        $campaign2 = Campaign::factory()
            ->forSeller($this->seller)
            ->create(['name' => 'Second', 'created_at' => now()->subDay()]);

        $campaign3 = Campaign::factory()
            ->forSeller($this->seller)
            ->create(['name' => 'Third', 'created_at' => now()]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/campaigns');

        // Assert
        $response->assertStatus(200);
        $campaigns = $response->json('campaigns');

        $this->assertEquals('Third', $campaigns[0]['name']);
        $this->assertEquals('Second', $campaigns[1]['name']);
        $this->assertEquals('First', $campaigns[2]['name']);
    }

    public function test_campaign_with_all_optional_fields_null(): void
    {
        // Arrange
        $campaign = Campaign::factory()
            ->forSeller($this->seller)
            ->storeDiscount()
            ->create([
                'description' => null,
                'min_purchase_amount' => null,
                'min_quantity' => null,
                'product_id' => null,
                'brand' => null,
                'gift_product_id' => null,
                'gift_quantity' => null,
            ]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/campaigns/{$campaign->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('campaign.description', null)
            ->assertJsonPath('campaign.min_purchase_amount', null)
            ->assertJsonPath('campaign.min_quantity', null);
    }

    public function test_campaign_with_maximum_discount_rate(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData(['discount_rate' => 100]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('campaign.discount_rate', '100.00');
    }

    public function test_campaign_with_minimum_discount_rate(): void
    {
        // Arrange
        $campaignData = $this->validCampaignData(['discount_rate' => 1]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/campaigns', $campaignData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('campaign.discount_rate', '1.00');
    }
}
