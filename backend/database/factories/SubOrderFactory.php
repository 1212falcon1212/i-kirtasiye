<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\SubOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubOrder>
 */
class SubOrderFactory extends Factory
{
    protected $model = SubOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 2000);
        $commission = $subtotal * 0.10;

        return [
            'order_id' => Order::factory(),
            'seller_id' => User::factory(),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'total_commission' => $commission,
            'total_payout' => $subtotal - $commission,
        ];
    }

    /**
     * Set as shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
    }

    /**
     * Set as delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivered_at' => now(),
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

    /**
     * Set specific seller.
     */
    public function forSeller(User $seller): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_id' => $seller->id,
        ]);
    }
}
