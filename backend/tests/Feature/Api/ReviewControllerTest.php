<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;
    protected User $seller;
    protected string $buyerToken;
    protected string $sellerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buyer = User::factory()->create(['is_verified' => true]);
        $this->seller = User::factory()->seller()->create(['is_verified' => true]);
        $this->buyerToken = $this->buyer->createToken('test-token')->plainTextToken;
        $this->sellerToken = $this->seller->createToken('test-token')->plainTextToken;
    }

    /**
     * Helper method to make authenticated requests as buyer.
     */
    protected function buyerHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->buyerToken];
    }

    /**
     * Helper method to make authenticated requests as seller.
     */
    protected function sellerHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->sellerToken];
    }

    /**
     * Helper method to create a delivered order with order item.
     */
    protected function createDeliveredOrderWithItem(?User $buyer = null, ?User $seller = null): OrderItem
    {
        $buyer = $buyer ?? $this->buyer;
        $seller = $seller ?? $this->seller;

        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        $order = Order::factory()
            ->forUser($buyer)
            ->delivered()
            ->create();

        return OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($seller)
            ->withQuantityAndPrice(2, 100.00)
            ->create();
    }

    // ==========================================
    // POST /api/reviews - Create a review
    // ==========================================

    /**
     * Test creating a review with valid data.
     */
    public function test_store_creates_review_with_valid_data(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'delivery_rating' => 4,
                'quality_rating' => 5,
                'communication_rating' => 4,
                'comment' => 'Harika bir urun, cok memnun kaldim.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'review' => [
                    'id',
                    'order_item_id',
                    'product_id',
                    'seller_id',
                    'buyer_id',
                    'rating',
                    'delivery_rating',
                    'quality_rating',
                    'communication_rating',
                    'comment',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'order_item_id' => $orderItem->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'rating' => 5,
            'status' => Review::STATUS_PENDING,
        ]);
    }

    /**
     * Test creating a review with only required fields.
     */
    public function test_store_creates_review_with_only_required_fields(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 4,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'order_item_id' => $orderItem->id,
            'rating' => 4,
            'comment' => null,
        ]);
    }

    /**
     * Test creating a review requires authentication.
     */
    public function test_store_requires_authentication(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $response = $this->postJson('/api/reviews', [
            'order_item_id' => $orderItem->id,
            'rating' => 5,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test creating a review fails for non-existent order item.
     */
    public function test_store_fails_for_nonexistent_order_item(): void
    {
        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => 99999,
                'rating' => 5,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_item_id']);
    }

    /**
     * Test creating a review fails if order does not belong to user.
     */
    public function test_store_fails_if_order_not_owned_by_user(): void
    {
        $otherBuyer = User::factory()->create(['is_verified' => true]);
        $orderItem = $this->createDeliveredOrderWithItem($otherBuyer);

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Bu sipariş size ait değil.',
            ]);
    }

    /**
     * Test creating a review fails if order is not delivered.
     */
    public function test_store_fails_if_order_not_delivered(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($this->seller)
            ->withPrice(100.00)
            ->withStock(50)
            ->available()
            ->create();

        // Create pending order (not delivered)
        $order = Order::factory()
            ->forUser($this->buyer)
            ->processing()
            ->create();

        $orderItem = OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($this->seller)
            ->withQuantityAndPrice(2, 100.00)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Sadece teslim edilmiş siparişler için yorum yapabilirsiniz.',
            ]);
    }

    /**
     * Test creating a review fails if already reviewed.
     */
    public function test_store_fails_if_already_reviewed(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        // Create existing review
        Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Bu ürün için zaten yorum yaptınız.',
            ]);
    }

    /**
     * Test creating a review validates rating minimum.
     */
    public function test_store_validates_rating_minimum(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /**
     * Test creating a review validates rating maximum.
     */
    public function test_store_validates_rating_maximum(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 6,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /**
     * Test creating a review validates comment max length.
     */
    public function test_store_validates_comment_max_length(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'comment' => str_repeat('a', 1001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    /**
     * Test creating a review validates rating is required.
     */
    public function test_store_validates_rating_is_required(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    // ==========================================
    // GET /api/reviews/my-reviews - Get user's reviews
    // ==========================================

    /**
     * Test getting user's own reviews.
     */
    public function test_my_reviews_returns_users_reviews(): void
    {
        $orderItem1 = $this->createDeliveredOrderWithItem();
        $orderItem2 = $this->createDeliveredOrderWithItem();

        Review::factory()
            ->forOrderItem($orderItem1)
            ->forBuyer($this->buyer)
            ->withRating(5)
            ->create();

        Review::factory()
            ->forOrderItem($orderItem2)
            ->forBuyer($this->buyer)
            ->withRating(4)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/reviews/my-reviews');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'reviews',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(2, 'reviews');
    }

    /**
     * Test my reviews returns empty for user with no reviews.
     */
    public function test_my_reviews_returns_empty_for_no_reviews(): void
    {
        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/reviews/my-reviews');

        $response->assertStatus(200)
            ->assertJson([
                'reviews' => [],
                'pagination' => [
                    'total' => 0,
                ],
            ]);
    }

    /**
     * Test my reviews does not include other users' reviews.
     */
    public function test_my_reviews_excludes_other_users_reviews(): void
    {
        $otherBuyer = User::factory()->create(['is_verified' => true]);
        $orderItem = $this->createDeliveredOrderWithItem($otherBuyer);

        Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($otherBuyer)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/reviews/my-reviews');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'reviews');
    }

    /**
     * Test my reviews requires authentication.
     */
    public function test_my_reviews_requires_authentication(): void
    {
        $response = $this->getJson('/api/reviews/my-reviews');

        $response->assertStatus(401);
    }

    /**
     * Test my reviews pagination works.
     */
    public function test_my_reviews_pagination_works(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $orderItem = $this->createDeliveredOrderWithItem();
            Review::factory()
                ->forOrderItem($orderItem)
                ->forBuyer($this->buyer)
                ->create();
        }

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/reviews/my-reviews?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'reviews')
            ->assertJsonPath('pagination.total', 20)
            ->assertJsonPath('pagination.last_page', 2);
    }

    // ==========================================
    // GET /api/reviews/product/{productId} - Get product reviews
    // ==========================================

    /**
     * Test getting product reviews.
     */
    public function test_product_reviews_returns_approved_reviews(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($this->seller)
            ->available()
            ->create();

        $order = Order::factory()
            ->forUser($this->buyer)
            ->delivered()
            ->create();

        $orderItem = OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($this->seller)
            ->create();

        // Create approved review
        Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forProduct($product)
            ->approved()
            ->withRating(5)
            ->create();

        // Create pending review (should not appear)
        $order2 = Order::factory()->forUser($this->buyer)->delivered()->create();
        $orderItem2 = OrderItem::factory()
            ->forOrder($order2)
            ->forOffer($offer)
            ->forSeller($this->seller)
            ->create();

        Review::factory()
            ->forOrderItem($orderItem2)
            ->forBuyer($this->buyer)
            ->forProduct($product)
            ->pending()
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson("/api/reviews/product/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'reviews',
                'summary' => [
                    'average_rating',
                    'total_reviews',
                    'rating_counts',
                ],
                'pagination',
            ])
            ->assertJsonCount(1, 'reviews');
    }

    /**
     * Test product reviews returns correct summary.
     */
    public function test_product_reviews_returns_correct_summary(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();

        // Create 3 approved reviews with different ratings
        foreach ([5, 4, 5] as $rating) {
            $offer = Offer::factory()
                ->forProduct($product)
                ->forSeller($this->seller)
                ->available()
                ->create();

            $order = Order::factory()
                ->forUser($this->buyer)
                ->delivered()
                ->create();

            $orderItem = OrderItem::factory()
                ->forOrder($order)
                ->forOffer($offer)
                ->forSeller($this->seller)
                ->create();

            Review::factory()
                ->forOrderItem($orderItem)
                ->forBuyer($this->buyer)
                ->forProduct($product)
                ->approved()
                ->withRating($rating)
                ->create();
        }

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson("/api/reviews/product/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('summary.total_reviews', 3)
            ->assertJsonPath('summary.rating_counts.5', 2)
            ->assertJsonPath('summary.rating_counts.4', 1);

        // Average should be (5+4+5)/3 = 4.67 rounded to 4.7
        $this->assertEquals(4.7, $response->json('summary.average_rating'));
    }

    /**
     * Test product reviews for product with no reviews.
     */
    public function test_product_reviews_for_product_with_no_reviews(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson("/api/reviews/product/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'reviews' => [],
                'summary' => [
                    'average_rating' => 0,
                    'total_reviews' => 0,
                ],
            ]);
    }

    // ==========================================
    // GET /api/reviews/reviewable - Get reviewable items
    // ==========================================

    /**
     * Test getting reviewable items returns delivered orders without reviews.
     */
    public function test_reviewable_items_returns_delivered_orders_without_reviews(): void
    {
        // Create delivered order item without review
        $orderItem = $this->createDeliveredOrderWithItem();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/reviews/reviewable');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'items' => [
                    '*' => [
                        'id',
                        'product',
                        'seller',
                        'order',
                    ],
                ],
                'count',
            ])
            ->assertJsonPath('count', 1);
    }

    /**
     * Test reviewable items excludes items with existing reviews.
     */
    public function test_reviewable_items_excludes_reviewed_items(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        // Create review for this item
        Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/reviews/reviewable');

        $response->assertStatus(200)
            ->assertJsonPath('count', 0);
    }

    /**
     * Test reviewable items excludes non-delivered orders.
     */
    public function test_reviewable_items_excludes_non_delivered_orders(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($this->seller)
            ->available()
            ->create();

        // Create processing order (not delivered)
        $order = Order::factory()
            ->forUser($this->buyer)
            ->processing()
            ->create();

        OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($this->seller)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/reviews/reviewable');

        $response->assertStatus(200)
            ->assertJsonPath('count', 0);
    }

    /**
     * Test reviewable items requires authentication.
     */
    public function test_reviewable_items_requires_authentication(): void
    {
        $response = $this->getJson('/api/reviews/reviewable');

        $response->assertStatus(401);
    }

    /**
     * Test reviewable items only shows user's own orders.
     */
    public function test_reviewable_items_only_shows_own_orders(): void
    {
        $otherBuyer = User::factory()->create(['is_verified' => true]);
        $this->createDeliveredOrderWithItem($otherBuyer);

        $response = $this->withHeaders($this->buyerHeaders())
            ->getJson('/api/reviews/reviewable');

        $response->assertStatus(200)
            ->assertJsonPath('count', 0);
    }

    // ==========================================
    // GET /api/reviews/seller - Get seller's reviews
    // ==========================================

    /**
     * Test getting seller's reviews.
     */
    public function test_seller_reviews_returns_approved_reviews_by_default(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forSeller($this->seller)
            ->approved()
            ->create();

        // Create pending review (should not appear by default)
        $orderItem2 = $this->createDeliveredOrderWithItem();
        Review::factory()
            ->forOrderItem($orderItem2)
            ->forBuyer($this->buyer)
            ->forSeller($this->seller)
            ->pending()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/reviews/seller');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'reviews',
                'ratings' => [
                    'overall',
                    'delivery',
                    'quality',
                    'communication',
                    'count',
                ],
                'pagination',
            ])
            ->assertJsonCount(1, 'reviews');
    }

    /**
     * Test seller reviews can filter by status.
     */
    public function test_seller_reviews_can_filter_by_status(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forSeller($this->seller)
            ->pending()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/reviews/seller?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'reviews');
    }

    /**
     * Test seller reviews requires authentication.
     */
    public function test_seller_reviews_requires_authentication(): void
    {
        $response = $this->getJson('/api/reviews/seller');

        $response->assertStatus(401);
    }

    /**
     * Test seller reviews returns correct ratings summary.
     */
    public function test_seller_reviews_returns_correct_ratings_summary(): void
    {
        // Create multiple reviews with different ratings
        foreach ([5, 4, 5, 3] as $rating) {
            $orderItem = $this->createDeliveredOrderWithItem();
            Review::factory()
                ->forOrderItem($orderItem)
                ->forBuyer($this->buyer)
                ->forSeller($this->seller)
                ->approved()
                ->withAllRatings($rating)
                ->create();
        }

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/reviews/seller');

        $response->assertStatus(200)
            ->assertJsonPath('ratings.count', 4);

        // Average should be (5+4+5+3)/4 = 4.25 rounded to 4.3
        $this->assertEquals(4.3, $response->json('ratings.overall'));
    }

    /**
     * Test seller reviews only shows seller's own reviews.
     */
    public function test_seller_reviews_only_shows_own_reviews(): void
    {
        $otherSeller = User::factory()->seller()->create();
        $orderItem = $this->createDeliveredOrderWithItem($this->buyer, $otherSeller);

        Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forSeller($otherSeller)
            ->approved()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->getJson('/api/reviews/seller');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'reviews');
    }

    // ==========================================
    // POST /api/reviews/{review}/reply - Seller reply
    // ==========================================

    /**
     * Test seller can reply to a review.
     */
    public function test_reply_allows_seller_to_respond(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $review = Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forSeller($this->seller)
            ->approved()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/reviews/{$review->id}/reply", [
                'reply' => 'Tesekkur ederiz, yine bekleriz!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Yanıtınız başarıyla eklendi.',
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'seller_reply' => 'Tesekkur ederiz, yine bekleriz!',
        ]);

        $review->refresh();
        $this->assertNotNull($review->seller_replied_at);
    }

    /**
     * Test only the seller can reply to their review.
     */
    public function test_reply_fails_if_not_review_seller(): void
    {
        $otherSeller = User::factory()->seller()->create(['is_verified' => true]);
        $otherSellerToken = $otherSeller->createToken('test-token')->plainTextToken;

        $orderItem = $this->createDeliveredOrderWithItem();

        $review = Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forSeller($this->seller)
            ->approved()
            ->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $otherSellerToken])
            ->postJson("/api/reviews/{$review->id}/reply", [
                'reply' => 'Tesekkurler!',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Bu yoruma yanıt verme yetkiniz yok.',
            ]);
    }

    /**
     * Test seller cannot reply twice to the same review.
     */
    public function test_reply_fails_if_already_replied(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $review = Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forSeller($this->seller)
            ->approved()
            ->withReply('Ilk yanitim.')
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/reviews/{$review->id}/reply", [
                'reply' => 'Ikinci yanit denemesi.',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Bu yoruma zaten yanıt verdiniz.',
            ]);
    }

    /**
     * Test reply validates reply is required.
     */
    public function test_reply_validates_reply_is_required(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $review = Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forSeller($this->seller)
            ->approved()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/reviews/{$review->id}/reply", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reply']);
    }

    /**
     * Test reply validates reply max length.
     */
    public function test_reply_validates_reply_max_length(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $review = Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forSeller($this->seller)
            ->approved()
            ->create();

        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson("/api/reviews/{$review->id}/reply", [
                'reply' => str_repeat('a', 1001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reply']);
    }

    /**
     * Test reply requires authentication.
     */
    public function test_reply_requires_authentication(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $review = Review::factory()
            ->forOrderItem($orderItem)
            ->forBuyer($this->buyer)
            ->forSeller($this->seller)
            ->create();

        $response = $this->postJson("/api/reviews/{$review->id}/reply", [
            'reply' => 'Tesekkurler!',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test reply fails for non-existent review.
     */
    public function test_reply_fails_for_nonexistent_review(): void
    {
        $response = $this->withHeaders($this->sellerHeaders())
            ->postJson('/api/reviews/99999/reply', [
                'reply' => 'Tesekkurler!',
            ]);

        $response->assertStatus(404);
    }

    // ==========================================
    // Edge Cases and Business Logic
    // ==========================================

    /**
     * Test creating a review sets initial status to pending.
     */
    public function test_store_sets_status_to_pending(): void
    {
        $orderItem = $this->createDeliveredOrderWithItem();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'order_item_id' => $orderItem->id,
            'status' => Review::STATUS_PENDING,
        ]);
    }

    /**
     * Test cancelled orders cannot be reviewed.
     */
    public function test_store_fails_for_cancelled_orders(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($this->seller)
            ->available()
            ->create();

        $order = Order::factory()
            ->forUser($this->buyer)
            ->cancelled()
            ->create();

        $orderItem = OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($this->seller)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
            ]);

        $response->assertStatus(400);
    }

    /**
     * Test shipped but not delivered orders cannot be reviewed.
     */
    public function test_store_fails_for_shipped_but_not_delivered_orders(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->forCategory($category)->create();
        $offer = Offer::factory()
            ->forProduct($product)
            ->forSeller($this->seller)
            ->available()
            ->create();

        $order = Order::factory()
            ->forUser($this->buyer)
            ->shipped()
            ->create();

        $orderItem = OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer)
            ->forSeller($this->seller)
            ->create();

        $response = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
            ]);

        $response->assertStatus(400);
    }

    /**
     * Test buyer can review multiple items from same order.
     */
    public function test_buyer_can_review_multiple_items_from_same_order(): void
    {
        $category = Category::factory()->create();
        $product1 = Product::factory()->forCategory($category)->create();
        $product2 = Product::factory()->forCategory($category)->create();

        $offer1 = Offer::factory()
            ->forProduct($product1)
            ->forSeller($this->seller)
            ->available()
            ->create();

        $offer2 = Offer::factory()
            ->forProduct($product2)
            ->forSeller($this->seller)
            ->available()
            ->create();

        $order = Order::factory()
            ->forUser($this->buyer)
            ->delivered()
            ->create();

        $orderItem1 = OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer1)
            ->forSeller($this->seller)
            ->create();

        $orderItem2 = OrderItem::factory()
            ->forOrder($order)
            ->forOffer($offer2)
            ->forSeller($this->seller)
            ->create();

        // Review first item
        $response1 = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem1->id,
                'rating' => 5,
            ]);
        $response1->assertStatus(201);

        // Review second item
        $response2 = $this->withHeaders($this->buyerHeaders())
            ->postJson('/api/reviews', [
                'order_item_id' => $orderItem2->id,
                'rating' => 4,
            ]);
        $response2->assertStatus(201);

        $this->assertDatabaseCount('reviews', 2);
    }
}
