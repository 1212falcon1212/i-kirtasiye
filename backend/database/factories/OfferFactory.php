<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'seller_id' => User::factory(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'stock' => fake()->numberBetween(10, 500),
            'expiry_date' => fake()->dateTimeBetween('+6 months', '+2 years'),
            'batch_number' => strtoupper(fake()->bothify('??###??')),
            'status' => 'active',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the offer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the offer is sold out.
     */
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
            'status' => 'sold_out',
        ]);
    }

    /**
     * Indicate that the offer is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => fake()->dateTimeBetween('-1 year', '-1 day'),
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
     * Set a specific price.
     */
    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }

    /**
     * Set a specific stock.
     */
    public function withStock(int $stock): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $stock,
        ]);
    }

    /**
     * Create an available offer (active, not expired).
     * Use withStock() separately if you need specific stock amount.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'expiry_date' => fake()->dateTimeBetween('+6 months', '+2 years'),
        ]);
    }

    /**
     * Create an unavailable offer.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
