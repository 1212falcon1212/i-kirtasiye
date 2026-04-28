<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 10, 500);
        $quantity = fake()->numberBetween(1, 10);
        $totalPrice = $unitPrice * $quantity;
        $commissionRate = 10;
        $commissionAmount = $totalPrice * ($commissionRate / 100);
        $marketplaceFee = 0;
        $withholdingTax = 0;
        $shippingCostShare = 0;
        $netSellerAmount = $totalPrice - $commissionAmount;
        $sellerPayoutAmount = $netSellerAmount - $withholdingTax - $marketplaceFee;

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'offer_id' => Offer::factory(),
            'seller_id' => User::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'marketplace_fee' => $marketplaceFee,
            'withholding_tax' => $withholdingTax,
            'shipping_cost_share' => $shippingCostShare,
            'net_seller_amount' => $netSellerAmount,
            'seller_payout_amount' => $sellerPayoutAmount,
        ];
    }

    /**
     * Set a specific order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
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
     * Set a specific offer.
     */
    public function forOffer(Offer $offer): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $offer->product_id,
            'offer_id' => $offer->id,
            'seller_id' => $offer->seller_id,
            'unit_price' => $offer->price,
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
     * Set specific quantity and prices.
     */
    public function withQuantityAndPrice(int $quantity, float $unitPrice, float $commissionRate = 10): static
    {
        $totalPrice = $unitPrice * $quantity;
        $commissionAmount = $totalPrice * ($commissionRate / 100);
        $sellerPayoutAmount = $totalPrice - $commissionAmount;

        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'net_seller_amount' => $sellerPayoutAmount,
            'seller_payout_amount' => $sellerPayoutAmount,
        ]);
    }
}
