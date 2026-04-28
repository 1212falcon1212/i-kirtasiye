<?php

namespace Database\Factories;

use App\Models\SellerBankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SellerBankAccount>
 */
class SellerBankAccountFactory extends Factory
{
    protected $model = SellerBankAccount::class;

    /**
     * Turkish bank names
     */
    protected array $turkishBanks = [
        'Ziraat Bankası',
        'Türkiye İş Bankası',
        'Garanti BBVA',
        'Yapı Kredi',
        'Akbank',
        'Halkbank',
        'VakıfBank',
        'QNB Finansbank',
        'Denizbank',
        'TEB',
    ];

    /**
     * Generate a valid Turkish IBAN (26 characters)
     */
    protected function generateValidIban(): string
    {
        // TR + 2 check digits + 5 bank code + 1 reserve + 16 account number = 26 chars
        return 'TR' . fake()->numerify('##') . fake()->numerify('#####') . '0' . fake()->numerify('################');
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => User::factory()->seller(),
            'bank_name' => fake()->randomElement($this->turkishBanks),
            'iban' => $this->generateValidIban(),
            'account_holder' => fake()->name(),
            'swift_code' => strtoupper(fake()->lexify('????')) . 'TR' . strtoupper(fake()->lexify('??')),
            'is_default' => false,
            'is_verified' => false,
        ];
    }

    /**
     * Create bank account for a specific seller
     */
    public function forSeller(User $seller): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_id' => $seller->id,
        ]);
    }

    /**
     * Mark as default account
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Mark as verified
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Set specific bank name
     */
    public function withBank(string $bankName): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_name' => $bankName,
        ]);
    }

    /**
     * Set specific IBAN
     */
    public function withIban(string $iban): static
    {
        return $this->state(fn (array $attributes) => [
            'iban' => strtoupper(preg_replace('/\s+/', '', $iban)),
        ]);
    }

    /**
     * Set specific account holder
     */
    public function withAccountHolder(string $holder): static
    {
        return $this->state(fn (array $attributes) => [
            'account_holder' => $holder,
        ]);
    }
}
