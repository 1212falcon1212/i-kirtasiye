<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);
        $totalCommission = $subtotal * 0.10; // 10% commission

        return [
            'order_number' => $this->generateOrderNumber(),
            'user_id' => User::factory(),
            'subtotal' => $subtotal,
            'total_commission' => $totalCommission,
            'total_amount' => $subtotal,
            'shipping_cost' => 0,
            'shipping_provider' => null,
            'tracking_number' => null,
            'shipping_status' => 'pending',
            'shipping_label_url' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipping_address' => [
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->streetAddress(),
                'city' => fake()->city(),
                'district' => fake()->citySuffix(),
                'postal_code' => fake()->postcode(),
            ],
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Generate unique order number.
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'EPZ';
        $date = now()->format('ymd');
        $random = strtoupper(Str::random(4));
        $sequence = str_pad(fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$date}{$sequence}{$random}";
    }

    /**
     * Set a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set status as confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Set status as processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Set status as shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'shipping_status' => 'shipped',
            'shipped_at' => now(),
            'tracking_number' => strtoupper(fake()->bothify('??#########??')),
            'shipping_provider' => fake()->randomElement(['Aras', 'Yurtici', 'MNG', 'Surat']),
        ]);
    }

    /**
     * Set status as delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'shipping_status' => 'delivered',
            'shipped_at' => now()->subDays(3),
            'delivered_at' => now(),
            'tracking_number' => strtoupper(fake()->bothify('??#########??')),
            'shipping_provider' => fake()->randomElement(['Aras', 'Yurtici', 'MNG', 'Surat']),
        ]);
    }

    /**
     * Set status as cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Set payment status as paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Set payment status as failed.
     */
    public function paymentFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'failed',
        ]);
    }

    /**
     * Set specific amounts.
     */
    public function withAmounts(float $subtotal, float $commission = null): static
    {
        $commission = $commission ?? ($subtotal * 0.10);

        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'total_commission' => $commission,
            'total_amount' => $subtotal,
        ]);
    }
}
