<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'product_id' => Product::factory(),
            'seller_id' => User::factory(),
            'buyer_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'delivery_rating' => fake()->optional()->numberBetween(1, 5),
            'quality_rating' => fake()->optional()->numberBetween(1, 5),
            'communication_rating' => fake()->optional()->numberBetween(1, 5),
            'comment' => fake()->optional()->paragraph(),
            'status' => Review::STATUS_PENDING,
            'seller_reply' => null,
            'seller_replied_at' => null,
            'rejection_reason' => null,
            'moderated_by' => null,
            'moderated_at' => null,
        ];
    }

    /**
     * Set a specific order item.
     */
    public function forOrderItem(OrderItem $orderItem): static
    {
        return $this->state(fn (array $attributes) => [
            'order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'seller_id' => $orderItem->seller_id,
        ]);
    }

    /**
     * Set a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    /**
     * Set a specific seller.
     */
    public function forSeller(User $seller): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_id' => $seller->id,
        ]);
    }

    /**
     * Set a specific buyer.
     */
    public function forBuyer(User $buyer): static
    {
        return $this->state(fn (array $attributes) => [
            'buyer_id' => $buyer->id,
        ]);
    }

    /**
     * Set review status as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Review::STATUS_PENDING,
        ]);
    }

    /**
     * Set review status as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Review::STATUS_APPROVED,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Set review status as rejected.
     */
    public function rejected(string $reason = 'Uygunsuz icerik.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Review::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Set specific rating.
     */
    public function withRating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }

    /**
     * Set all ratings.
     */
    public function withAllRatings(int $overall, ?int $delivery = null, ?int $quality = null, ?int $communication = null): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $overall,
            'delivery_rating' => $delivery ?? $overall,
            'quality_rating' => $quality ?? $overall,
            'communication_rating' => $communication ?? $overall,
        ]);
    }

    /**
     * Set a specific comment.
     */
    public function withComment(string $comment): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => $comment,
        ]);
    }

    /**
     * Add seller reply.
     */
    public function withReply(string $reply): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_reply' => $reply,
            'seller_replied_at' => now(),
        ]);
    }

    /**
     * Set as moderated by a specific user.
     */
    public function moderatedBy(User $moderator): static
    {
        return $this->state(fn (array $attributes) => [
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);
    }
}
