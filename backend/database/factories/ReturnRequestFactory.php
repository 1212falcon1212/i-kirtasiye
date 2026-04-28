<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReturnRequest>
 */
class ReturnRequestFactory extends Factory
{
    protected $model = ReturnRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'order_item_id' => null,
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'type' => 'return',
            'reason' => fake()->randomElement(array_keys(ReturnRequest::REASON_LABELS)),
            'reason_detail' => fake()->sentence(),
            'quantity' => fake()->numberBetween(1, 5),
            'refund_amount' => fake()->randomFloat(2, 50, 2000),
            'status' => 'pending',
            'seller_note' => null,
            'images' => null,
        ];
    }

    /**
     * Set as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Set as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Set as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_at' => now(),
            'seller_note' => 'Ürün hasarsız, iade kabul edilmedi.',
        ]);
    }

    /**
     * Set specific buyer.
     */
    public function forBuyer(User $buyer): static
    {
        return $this->state(fn (array $attributes) => [
            'buyer_id' => $buyer->id,
        ]);
    }

    /**
     * Set specific seller.
     */
    public function forSeller(User $seller): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_id' => $seller->id,
        ]);
    }

    /**
     * Set specific order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }
}
