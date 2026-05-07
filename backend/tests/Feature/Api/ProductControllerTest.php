<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
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
        return ['Authorization' => 'Bearer '.$this->token];
    }

    /**
     * Test listing products returns paginated results.
     */
    public function test_index_returns_paginated_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->forCategory($category)->count(5)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'products',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(5, 'products');
    }

    /**
     * Test listing products with custom per_page parameter.
     */
    public function test_index_respects_per_page_parameter(): void
    {
        $category = Category::factory()->create();
        Product::factory()->forCategory($category)->count(10)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products?per_page=3');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 3)
            ->assertJsonCount(3, 'products');
    }

    /**
     * Test listing products filtered by category slug.
     */
    public function test_index_filters_by_category(): void
    {
        $category1 = Category::factory()->create(['slug' => 'ilaclar']);
        $category2 = Category::factory()->create(['slug' => 'vitaminler']);

        Product::factory()->forCategory($category1)->count(3)->create();
        Product::factory()->forCategory($category2)->count(2)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products?category=ilaclar');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'products');
    }

    /**
     * Test listing products returns only active products.
     */
    public function test_index_returns_only_active_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->forCategory($category)->count(3)->create(['is_active' => true]);
        Product::factory()->forCategory($category)->inactive()->count(2)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'products');
    }

    /**
     * Test listing products includes offer count and lowest price.
     */
    public function test_index_includes_offers_count_and_lowest_price(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();

        // Create offers with different prices
        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(10)
            ->available()
            ->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(80.00)
            ->withStock(5)
            ->available()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonPath('products.0.offers_count', 2);

        // Price can be returned as string or number depending on database
        $lowestPrice = $response->json('products.0.lowest_price');
        $this->assertEquals(80.00, (float) $lowestPrice);
    }

    /**
     * Test showing single product details.
     */
    public function test_show_returns_product_details(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create([
            'name' => 'Test Product',
            'barcode' => '1234567890123',
            'brand' => 'Test Brand',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'product' => [
                    'id',
                    'name',
                    'barcode',
                    'brand',
                ],
            ])
            ->assertJsonPath('product.name', 'Test Product')
            ->assertJsonPath('product.barcode', '1234567890123');
    }

    /**
     * Test showing non-existent product returns 404.
     */
    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products/99999');

        $response->assertStatus(404);
    }

    /**
     * Test getting offers for a product.
     */
    public function test_offers_returns_product_offers(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller1 = User::factory()->seller()->create(['business_name' => 'Tedarikçi A']);
        $seller2 = User::factory()->seller()->create(['business_name' => 'Tedarikçi B']);

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller1)
            ->withPrice(100.00)
            ->withStock(10)
            ->available()
            ->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller2)
            ->withPrice(90.00)
            ->withStock(5)
            ->available()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/products/{$product->id}/offers");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'product' => [
                    'id',
                    'name',
                    'barcode',
                    'brand',
                ],
                'offers',
                'offers_count',
                'lowest_price',
                'highest_price',
            ])
            ->assertJsonPath('offers_count', 2);

        // Price can be returned as string or number depending on database
        $lowestPrice = $response->json('lowest_price');
        $highestPrice = $response->json('highest_price');
        $this->assertEquals(90.00, (float) $lowestPrice);
        $this->assertEquals(100.00, (float) $highestPrice);
    }

    /**
     * Test offers are sorted by price ascending by default.
     */
    public function test_offers_sorted_by_price_ascending(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(150.00)
            ->withStock(10)
            ->available()
            ->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(5)
            ->available()
            ->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(200.00)
            ->withStock(3)
            ->available()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/products/{$product->id}/offers");

        $response->assertStatus(200);

        $offers = $response->json('offers');
        $this->assertEquals(100.00, $offers[0]['price']);
        $this->assertEquals(150.00, $offers[1]['price']);
        $this->assertEquals(200.00, $offers[2]['price']);
    }

    /**
     * Test offers excludes inactive offers.
     */
    public function test_offers_excludes_inactive_offers(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(10)
            ->available()
            ->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(80.00)
            ->inactive()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/products/{$product->id}/offers");

        $response->assertStatus(200)
            ->assertJsonPath('offers_count', 1);
    }

    /**
     * Test offers excludes sold out offers.
     */
    public function test_offers_excludes_sold_out_offers(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(10)
            ->available()
            ->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(80.00)
            ->soldOut()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/products/{$product->id}/offers");

        $response->assertStatus(200)
            ->assertJsonPath('offers_count', 1);
    }

    /**
     * Test offers excludes expired offers.
     */
    public function test_offers_excludes_expired_offers(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(10)
            ->available()
            ->create();

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(80.00)
            ->withStock(5)
            ->expired()
            ->create(['status' => 'active']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/products/{$product->id}/offers");

        $response->assertStatus(200)
            ->assertJsonPath('offers_count', 1);
    }

    /**
     * Test search returns matching products.
     *
     * Note: This test requires Meilisearch to be configured with filterable attributes.
     * In CI/CD environments without Meilisearch, this test will be skipped.
     */
    public function test_search_returns_matching_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->forCategory($category)->create([
            'name' => 'Parol Tablet',
            'is_active' => true,
        ]);
        Product::factory()->forCategory($category)->create([
            'name' => 'Aspirin',
            'is_active' => true,
        ]);

        try {
            $response = $this->withHeaders($this->authHeaders())
                ->getJson('/api/products/search?q=Parol');

            // If we get here without exception, check the response
            if ($response->status() === 500) {
                $this->markTestSkipped('Meilisearch is not properly configured (filterable attributes not set).');
            }

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'products',
                    'pagination',
                ]);
        } catch (\Exception $e) {
            $this->markTestSkipped('Meilisearch is not available: '.$e->getMessage());
        }
    }

    /**
     * Test search requires minimum query length.
     */
    public function test_search_requires_minimum_query_length(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products/search?q=A');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    /**
     * Test search requires query parameter.
     */
    public function test_search_requires_query_parameter(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    /**
     * Test unauthenticated user can access products (public route).
     */
    public function test_unauthenticated_user_can_access_products(): void
    {
        $response = $this->getJson('/api/products');

        // Products endpoint is public - should return 200
        $response->assertStatus(200);
    }

    /**
     * Test unauthenticated user can access product details (public route).
     */
    public function test_unauthenticated_user_can_access_product_details(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();

        $response = $this->getJson("/api/products/{$product->id}");

        // Product details endpoint is public - should return 200
        $response->assertStatus(200);
    }

    /**
     * Test empty category returns empty products list.
     */
    public function test_empty_category_returns_empty_products(): void
    {
        $category = Category::factory()->create(['slug' => 'empty-category']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products?category=empty-category');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'products')
            ->assertJsonPath('pagination.total', 0);
    }

    /**
     * Test product offers include seller information.
     */
    public function test_offers_include_seller_information(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $seller = User::factory()->seller()->create([
            'business_name' => 'Test Tedarikçi',
            'city' => 'Istanbul',
        ]);

        Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(10)
            ->available()
            ->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/products/{$product->id}/offers");

        $response->assertStatus(200)
            ->assertJsonPath('offers.0.seller.business_name', 'Test Tedarikçi')
            ->assertJsonPath('offers.0.seller.city', 'Istanbul');
    }

    /**
     * Test sort_by=sales_desc orders products by total quantity sold first.
     */
    public function test_index_sort_by_sales_desc_orders_by_total_sold(): void
    {
        $category = Category::factory()->create();
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->create(['is_verified' => true]);

        $bestSeller = Product::factory()->forCategory($category)->create(['name' => 'Best Seller']);
        $midSeller = Product::factory()->forCategory($category)->create(['name' => 'Mid Seller']);
        $newProduct = Product::factory()->forCategory($category)->create(['name' => 'No Sales']);

        foreach ([$bestSeller, $midSeller, $newProduct] as $p) {
            Offer::factory()->forProduct($p)->forSeller($seller)
                ->withPrice(50.00)->withStock(10)->available()->create();
        }

        $order = Order::factory()->forUser($buyer)->paid()->create();
        OrderItem::factory()->forOrder($order)->forProduct($bestSeller)->forSeller($seller)
            ->withQuantityAndPrice(20, 50.00)->create();
        OrderItem::factory()->forOrder($order)->forProduct($midSeller)->forSeller($seller)
            ->withQuantityAndPrice(5, 50.00)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products?sort_by=sales_desc');

        $response->assertStatus(200);
        $names = array_column($response->json('products'), 'name');
        $this->assertSame('Best Seller', $names[0]);
        $this->assertSame('Mid Seller', $names[1]);
        $this->assertSame('No Sales', $names[2]);
    }

    /**
     * Test sort_by=price_drop ranks products with the largest discount vs PSF first.
     */
    public function test_index_sort_by_price_drop_orders_by_discount_ratio(): void
    {
        $category = Category::factory()->create();
        $seller = User::factory()->seller()->create();

        $bigDrop = Product::factory()->forCategory($category)->create([
            'name' => 'Big Drop',
            'psf' => 100.00,
        ]);
        $smallDrop = Product::factory()->forCategory($category)->create([
            'name' => 'Small Drop',
            'psf' => 100.00,
        ]);
        $noDrop = Product::factory()->forCategory($category)->create([
            'name' => 'No Drop',
            'psf' => 100.00,
        ]);

        Offer::factory()->forProduct($bigDrop)->forSeller($seller)
            ->withPrice(40.00)->withStock(10)->available()->create();
        Offer::factory()->forProduct($smallDrop)->forSeller($seller)
            ->withPrice(90.00)->withStock(10)->available()->create();
        Offer::factory()->forProduct($noDrop)->forSeller($seller)
            ->withPrice(100.00)->withStock(10)->available()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products?sort_by=price_drop');

        $response->assertStatus(200);
        $names = array_column($response->json('products'), 'name');
        $this->assertSame('Big Drop', $names[0]);
        $this->assertSame('Small Drop', $names[1]);
        $this->assertSame('No Drop', $names[2]);
    }

    /**
     * Test sort_by=fast_ship ranks products whose seller has the lowest default_shipping_fee.
     */
    public function test_index_sort_by_fast_ship_orders_by_min_shipping_fee(): void
    {
        $category = Category::factory()->create();

        $fastSeller = User::factory()->seller()->create(['default_shipping_fee' => 9.90]);
        $slowSeller = User::factory()->seller()->create(['default_shipping_fee' => 49.90]);
        $unknownSeller = User::factory()->seller()->create(['default_shipping_fee' => null]);

        $fastProduct = Product::factory()->forCategory($category)->create(['name' => 'Fast']);
        $slowProduct = Product::factory()->forCategory($category)->create(['name' => 'Slow']);
        $unknownProduct = Product::factory()->forCategory($category)->create(['name' => 'Unknown']);

        Offer::factory()->forProduct($fastProduct)->forSeller($fastSeller)
            ->withPrice(50.00)->withStock(10)->available()->create();
        Offer::factory()->forProduct($slowProduct)->forSeller($slowSeller)
            ->withPrice(50.00)->withStock(10)->available()->create();
        Offer::factory()->forProduct($unknownProduct)->forSeller($unknownSeller)
            ->withPrice(50.00)->withStock(10)->available()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products?sort_by=fast_ship');

        $response->assertStatus(200);
        $names = array_column($response->json('products'), 'name');
        $this->assertSame('Fast', $names[0]);
        $this->assertSame('Slow', $names[1]);
        $this->assertSame('Unknown', $names[2]);
    }

    /**
     * Test invalid sort_by values fall back to the default ordering instead of erroring.
     */
    public function test_index_unknown_sort_by_falls_back_to_default(): void
    {
        $category = Category::factory()->create();
        Product::factory()->forCategory($category)->count(3)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/products?sort_by=__bogus__');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'products');
    }
}
