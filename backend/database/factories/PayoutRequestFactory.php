<?php

namespace Database\Factories;

use App\Models\PayoutRequest;
use App\Models\SellerBankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayoutRequest>
 */
class PayoutRequestFactory extends Factory
{
    protected $model = PayoutRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => User::factory()->seller(),
            'bank_account_id' => SellerBankAccount::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'status' => PayoutRequest::STATUS_PENDING,
            'notes' => null,
            'admin_notes' => null,
            'processed_by' => null,
            'processed_at' => null,
            'transaction_reference' => null,
            'metadata' => null,
        ];
    }

    /**
     * Create payout request for a specific seller
     */
    public function forSeller(User $seller): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_id' => $seller->id,
        ]);
    }

    /**
     * Create payout request with a specific bank account
     */
    public function forBankAccount(SellerBankAccount $bankAccount): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_account_id' => $bankAccount->id,
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

    /**
     * Set notes
     */
    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }

    /**
     * Pending status (default)
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutRequest::STATUS_PENDING,
        ]);
    }

    /**
     * Approved status
     */
    public function approved(User $admin = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutRequest::STATUS_APPROVED,
            'processed_by' => $admin?->id ?? User::factory()->superAdmin()->create()->id,
            'processed_at' => now(),
        ]);
    }

    /**
     * Rejected status
     */
    public function rejected(User $admin = null, string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutRequest::STATUS_REJECTED,
            'processed_by' => $admin?->id ?? User::factory()->superAdmin()->create()->id,
            'processed_at' => now(),
            'admin_notes' => $reason ?? 'Reddedildi',
        ]);
    }

    /**
     * Processing status
     */
    public function processing(User $admin = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutRequest::STATUS_PROCESSING,
            'processed_by' => $admin?->id ?? User::factory()->superAdmin()->create()->id,
            'processed_at' => now(),
        ]);
    }

    /**
     * Completed status
     */
    public function completed(string $transactionReference = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutRequest::STATUS_COMPLETED,
            'processed_by' => User::factory()->superAdmin()->create()->id,
            'processed_at' => now(),
            'transaction_reference' => $transactionReference ?? fake()->uuid(),
        ]);
    }

    /**
     * Failed status
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutRequest::STATUS_FAILED,
            'processed_at' => now(),
        ]);
    }
}
