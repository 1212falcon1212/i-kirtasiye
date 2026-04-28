<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);
        $taxRate = 18;
        $taxAmount = $subtotal * ($taxRate / 100);
        $totalAmount = $subtotal + $taxAmount;
        $commissionRate = 10;
        $commissionAmount = $subtotal * ($commissionRate / 100);

        return [
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'order_id' => Order::factory(),
            'seller_id' => User::factory(),
            'buyer_id' => User::factory(),
            'type' => Invoice::TYPE_SELLER,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'seller_info' => [
                'name' => fake()->company(),
                'tax_number' => fake()->numerify('##########'),
                'tax_office' => fake()->city().' Vergi Dairesi',
            ],
            'buyer_info' => [
                'name' => fake()->company().' Eczanesi',
                'email' => fake()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'tax_number' => fake()->numerify('##########'),
            ],
            'items' => [
                [
                    'product_id' => 1,
                    'name' => fake()->words(3, true),
                    'quantity' => 2,
                    'unit_price' => $subtotal / 2,
                    'tax_rate' => $taxRate,
                ],
            ],
            'erp_status' => 'pending',
            'pdf_path' => null,
        ];
    }

    /**
     * Set seller type.
     */
    public function seller(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Invoice::TYPE_SELLER,
        ]);
    }

    /**
     * Set commission type.
     */
    public function commission(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Invoice::TYPE_COMMISSION,
        ]);
    }

    /**
     * Set a PDF path.
     */
    public function withPdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'pdf_path' => 'invoices/test-invoice.pdf',
        ]);
    }

    /**
     * Set as draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_DRAFT,
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
     * Set specific buyer.
     */
    public function forBuyer(User $buyer): static
    {
        return $this->state(fn (array $attributes) => [
            'buyer_id' => $buyer->id,
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
