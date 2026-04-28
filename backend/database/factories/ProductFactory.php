<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'barcode' => fake()->unique()->ean13(),
            'name' => fake()->words(3, true),
            'brand' => fake()->company(),
            'manufacturer' => fake()->company(),
            'description' => fake()->paragraph(),
            'desi' => fake()->randomFloat(2, 0.1, 10),
            'weight' => fake()->randomFloat(2, 0.01, 5),
            'width' => fake()->randomFloat(2, 1, 50),
            'height' => fake()->randomFloat(2, 1, 50),
            'depth' => fake()->randomFloat(2, 1, 50),
            'image' => null,
            'category_id' => Category::factory(),
            'is_active' => true,
            'approval_status' => 'approved',
            'source' => 'manual',
            'created_by_id' => null,
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific category.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Set pending approval status.
     */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'pending',
        ]);
    }

    /**
     * Set rejected approval status.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'rejected',
        ]);
    }

    /**
     * Set created by user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by_id' => $user->id,
        ]);
    }

    /**
     * Set a specific barcode.
     */
    public function withBarcode(string $barcode): static
    {
        return $this->state(fn (array $attributes) => [
            'barcode' => $barcode,
        ]);
    }
}
