<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(),
            'badge_text' => fake()->optional()->word(),
            'image_path' => 'banners/'.fake()->uuid().'.jpg',
            'link_url' => fake()->optional()->url(),
            'button_text' => fake()->optional()->words(2, true),
            'location' => fake()->randomElement(['home_hero', 'home_promo', 'home_middle', 'home_brand', 'home_grid', 'home_bottom', 'home_showcase']),
            'tab_name' => null,
            'bg_color' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    /**
     * Set as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
