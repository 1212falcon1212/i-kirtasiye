<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\SellerWallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => SellerWallet::factory(),
            'type' => WalletTransaction::TYPE_SALE,
            'amount' => fake()->randomFloat(2, 10, 1000),
            'direction' => WalletTransaction::DIRECTION_CREDIT,
            'balance_type' => WalletTransaction::BALANCE_PENDING,
            'description' => 'Test transaction',
            'order_id' => null,
            'order_item_id' => null,
            'metadata' => null,
        ];
    }

    /**
     * Create transaction for a specific wallet
     */
    public function forWallet(SellerWallet $wallet): static
    {
        return $this->state(fn (array $attributes) => [
            'wallet_id' => $wallet->id,
        ]);
    }

    /**
     * Create sale transaction
     */
    public function sale(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WalletTransaction::TYPE_SALE,
            'amount' => $amount ?? fake()->randomFloat(2, 100, 1000),
            'direction' => WalletTransaction::DIRECTION_CREDIT,
            'balance_type' => WalletTransaction::BALANCE_PENDING,
            'description' => 'Satış geliri',
        ]);
    }

    /**
     * Create commission transaction
     */
    public function commission(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WalletTransaction::TYPE_COMMISSION,
            'amount' => $amount ?? fake()->randomFloat(2, 5, 100),
            'direction' => WalletTransaction::DIRECTION_DEBIT,
            'balance_type' => WalletTransaction::BALANCE_PENDING,
            'description' => 'Komisyon kesintisi',
        ]);
    }

    /**
     * Create withdrawal transaction
     */
    public function withdrawal(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WalletTransaction::TYPE_WITHDRAWAL,
            'amount' => $amount ?? fake()->randomFloat(2, 100, 1000),
            'direction' => WalletTransaction::DIRECTION_DEBIT,
            'balance_type' => WalletTransaction::BALANCE_AVAILABLE,
            'description' => 'Para çekme',
        ]);
    }

    /**
     * Create refund transaction
     */
    public function refund(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WalletTransaction::TYPE_REFUND,
            'amount' => $amount ?? fake()->randomFloat(2, 50, 500),
            'direction' => WalletTransaction::DIRECTION_DEBIT,
            'balance_type' => WalletTransaction::BALANCE_AVAILABLE,
            'description' => 'İade',
        ]);
    }

    /**
     * Set order reference
     */
    public function forOrder(Order $order, ?int $orderItemId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'order_item_id' => $orderItemId,
            'description' => "Sipariş #{$order->order_number}",
        ]);
    }

    /**
     * Set as credit direction
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => WalletTransaction::DIRECTION_CREDIT,
        ]);
    }

    /**
     * Set as debit direction
     */
    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => WalletTransaction::DIRECTION_DEBIT,
        ]);
    }

    /**
     * Set balance type to available
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_type' => WalletTransaction::BALANCE_AVAILABLE,
        ]);
    }

    /**
     * Set balance type to pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_type' => WalletTransaction::BALANCE_PENDING,
        ]);
    }

    /**
     * Set specific amount
     */
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}
