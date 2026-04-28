<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_id' => Product::factory(),
            'offer_id' => Offer::factory(),
            'seller_id' => User::factory()->seller(),
            'quantity' => fake()->numberBetween(1, 10),
            'price_at_addition' => fake()->randomFloat(2, 10, 500),
        ];
    }

    /**
     * Set a specific cart.
     */
    public function forCart(Cart $cart): static
    {
        return $this->state(fn (array $attributes) => [
            'cart_id' => $cart->id,
        ]);
    }

    /**
     * Set a specific offer with its related product and seller.
     */
    public function forOffer(Offer $offer): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $offer->product_id,
            'offer_id' => $offer->id,
            'seller_id' => $offer->seller_id,
            'price_at_addition' => $offer->price,
        ]);
    }

    /**
     * Set a specific quantity.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Set a specific price at addition.
     */
    public function withPriceAtAddition(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price_at_addition' => $price,
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
}
