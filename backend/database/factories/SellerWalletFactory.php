<?php

namespace Database\Factories;

use App\Models\SellerWallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SellerWallet>
 */
class SellerWalletFactory extends Factory
{
    protected $model = SellerWallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => User::factory()->seller(),
            'balance' => 0,
            'pending_balance' => 0,
            'withdrawn_balance' => 0,
            'total_earned' => 0,
            'total_commission' => 0,
        ];
    }

    /**
     * Create wallet for a specific seller
     */
    public function forSeller(User $seller): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_id' => $seller->id,
        ]);
    }

    /**
     * Set available balance
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
            'total_earned' => $balance,
        ]);
    }

    /**
     * Set pending balance
     */
    public function withPendingBalance(float $pendingBalance): static
    {
        return $this->state(fn (array $attributes) => [
            'pending_balance' => $pendingBalance,
            'total_earned' => ($attributes['total_earned'] ?? 0) + $pendingBalance,
        ]);
    }

    /**
     * Set withdrawn balance
     */
    public function withWithdrawnBalance(float $withdrawnBalance): static
    {
        return $this->state(fn (array $attributes) => [
            'withdrawn_balance' => $withdrawnBalance,
        ]);
    }

    /**
     * Set total commission
     */
    public function withCommission(float $commission): static
    {
        return $this->state(fn (array $attributes) => [
            'total_commission' => $commission,
        ]);
    }

    /**
     * Create wallet with typical seller activity
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => fake()->randomFloat(2, 1000, 10000),
            'pending_balance' => fake()->randomFloat(2, 500, 5000),
            'withdrawn_balance' => fake()->randomFloat(2, 0, 5000),
            'total_earned' => fake()->randomFloat(2, 5000, 50000),
            'total_commission' => fake()->randomFloat(2, 500, 5000),
        ]);
    }
}
