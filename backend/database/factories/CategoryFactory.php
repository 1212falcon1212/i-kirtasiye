<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'parent_id' => null,
            'description' => fake()->sentence(),
            'commission_rate' => fake()->randomFloat(2, 5, 15),
            'vat_rate' => fake()->randomElement([8, 10, 18, 20]),
            'withholding_tax_rate' => fake()->randomFloat(2, 0, 5),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the category as a child of another category.
     */
    public function childOf(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Set a specific commission rate.
     */
    public function withCommissionRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_rate' => $rate,
        ]);
    }

    /**
     * Set a specific VAT rate.
     */
    public function withVatRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'vat_rate' => $rate,
        ]);
    }
}
