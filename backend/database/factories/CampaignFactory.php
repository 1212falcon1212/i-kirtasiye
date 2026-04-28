<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => User::factory()->seller(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'type' => Campaign::TYPE_STORE_DISCOUNT,
            'discount_rate' => fake()->numberBetween(5, 50),
            'min_purchase_amount' => fake()->optional()->randomFloat(2, 50, 500),
            'min_quantity' => fake()->optional()->numberBetween(1, 10),
            'product_id' => null,
            'brand' => null,
            'gift_product_id' => null,
            'gift_quantity' => null,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'status' => Campaign::STATUS_PENDING,
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
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
     * Set campaign as pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_PENDING,
        ]);
    }

    /**
     * Set campaign as active status.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
        ]);
    }

    /**
     * Set campaign as inactive status.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_INACTIVE,
        ]);
    }

    /**
     * Set campaign as rejected status.
     */
    public function rejected(string $reason = 'Kampanya kriterleri uygun degil.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Set campaign as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_ACTIVE,
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(30),
        ]);
    }

    /**
     * Set campaign type as product discount.
     */
    public function productDiscount(Product $product = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Campaign::TYPE_PRODUCT_DISCOUNT,
            'product_id' => $product?->id ?? Product::factory(),
            'brand' => null,
            'gift_product_id' => null,
            'gift_quantity' => null,
        ]);
    }

    /**
     * Set campaign type as store discount.
     */
    public function storeDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Campaign::TYPE_STORE_DISCOUNT,
            'product_id' => null,
            'brand' => null,
            'gift_product_id' => null,
            'gift_quantity' => null,
        ]);
    }

    /**
     * Set campaign type as brand discount.
     */
    public function brandDiscount(string $brand = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Campaign::TYPE_BRAND_DISCOUNT,
            'brand' => $brand ?? fake()->company(),
            'product_id' => null,
            'gift_product_id' => null,
            'gift_quantity' => null,
        ]);
    }

    /**
     * Set campaign type as gift product.
     */
    public function giftProduct(Product $giftProduct = null, int $quantity = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Campaign::TYPE_GIFT_PRODUCT,
            'discount_rate' => null,
            'gift_product_id' => $giftProduct?->id ?? Product::factory(),
            'gift_quantity' => $quantity,
            'product_id' => null,
            'brand' => null,
        ]);
    }

    /**
     * Set specific discount rate.
     */
    public function withDiscountRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_rate' => $rate,
        ]);
    }

    /**
     * Set minimum purchase amount.
     */
    public function withMinPurchase(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'min_purchase_amount' => $amount,
        ]);
    }

    /**
     * Set minimum quantity.
     */
    public function withMinQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'min_quantity' => $quantity,
        ]);
    }

    /**
     * Set campaign dates.
     */
    public function withDates(\DateTime $startsAt, \DateTime $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    /**
     * Set as reviewed by admin.
     */
    public function reviewedBy(User $reviewer): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }
}
