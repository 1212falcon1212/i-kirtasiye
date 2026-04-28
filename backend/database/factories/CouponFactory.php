<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => User::factory()->seller(),
            'campaign_id' => null,
            'code' => strtoupper(Str::random(8)),
            'name' => fake()->words(2, true) . ' Kuponu',
            'description' => fake()->optional()->sentence(),
            'discount_type' => Coupon::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => fake()->randomFloat(2, 5, 30),
            'min_purchase_amount' => null,
            'max_discount_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => 1,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'status' => Coupon::STATUS_ACTIVE,
        ];
    }

    /**
     * Set the seller for this coupon.
     */
    public function forSeller(User $seller): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_id' => $seller->id,
        ]);
    }

    /**
     * Associate with a campaign.
     */
    public function forCampaign(Campaign $campaign): static
    {
        return $this->state(fn (array $attributes) => [
            'campaign_id' => $campaign->id,
            'seller_id' => $campaign->seller_id,
        ]);
    }

    /**
     * Set a specific coupon code.
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => strtoupper($code),
        ]);
    }

    /**
     * Active coupon.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Coupon::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    /**
     * Inactive coupon.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Coupon::STATUS_INACTIVE,
        ]);
    }

    /**
     * Expired coupon (date-based).
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Coupon::STATUS_ACTIVE,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Coupon that hasn't started yet.
     */
    public function notStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Coupon::STATUS_ACTIVE,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    /**
     * Percentage discount coupon.
     */
    public function percentage(float $value = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => Coupon::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => $value,
        ]);
    }

    /**
     * Fixed amount discount coupon.
     */
    public function fixed(float $value = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => Coupon::DISCOUNT_TYPE_FIXED,
            'discount_value' => $value,
        ]);
    }

    /**
     * Set discount value.
     */
    public function withDiscountValue(float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_value' => $value,
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
     * Set maximum discount amount (cap for percentage discounts).
     */
    public function withMaxDiscount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'max_discount_amount' => $amount,
        ]);
    }

    /**
     * Set total usage limit.
     */
    public function withUsageLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $limit,
        ]);
    }

    /**
     * Set per-user usage limit.
     */
    public function withUsageLimitPerUser(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit_per_user' => $limit,
        ]);
    }

    /**
     * Set used count.
     */
    public function withUsedCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'used_count' => $count,
        ]);
    }

    /**
     * Coupon with exhausted usage limit.
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 10,
            'used_count' => 10,
        ]);
    }

    /**
     * Set date range.
     */
    public function withDates(?\DateTime $startsAt, ?\DateTime $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    /**
     * No date restrictions.
     */
    public function noDateRestrictions(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    /**
     * No usage limit.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => null,
            'usage_limit_per_user' => null,
        ]);
    }
}
