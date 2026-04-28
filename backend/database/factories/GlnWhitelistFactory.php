<?php

namespace Database\Factories;

use App\Models\GlnWhitelist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GlnWhitelist>
 */
class GlnWhitelistFactory extends Factory
{
    protected $model = GlnWhitelist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gln_code' => $this->generateValidGlnCode(),
            'pharmacy_name' => fake()->company() . ' Eczanesi',
            'city' => fake()->randomElement(['Istanbul', 'Ankara', 'Izmir', 'Bursa', 'Antalya']),
            'district' => fake()->citySuffix(),
            'address' => fake()->streetAddress(),
            'is_active' => true,
            'is_used' => false,
            'used_by_user_id' => null,
            'used_at' => null,
        ];
    }

    /**
     * Generate a valid GLN code with proper check digit
     */
    protected function generateValidGlnCode(): string
    {
        // Generate 12 random digits
        $digits = '';
        for ($i = 0; $i < 12; $i++) {
            $digits .= fake()->randomDigit();
        }

        // Calculate check digit using GS1 standard
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $multiplier = ($i % 2 === 0) ? 1 : 3;
            $sum += (int) $digits[$i] * $multiplier;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $digits . $checkDigit;
    }

    /**
     * Mark GLN as inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Mark GLN as used
     */
    public function used(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_used' => true,
            'used_by_user_id' => $user?->id ?? User::factory(),
            'used_at' => now(),
        ]);
    }

    /**
     * Set a specific GLN code
     */
    public function withGlnCode(string $glnCode): static
    {
        return $this->state(fn (array $attributes) => [
            'gln_code' => $glnCode,
        ]);
    }

    /**
     * Set a specific pharmacy name
     */
    public function withPharmacyName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'pharmacy_name' => $name,
        ]);
    }

    /**
     * Set a specific city
     */
    public function inCity(string $city): static
    {
        return $this->state(fn (array $attributes) => [
            'city' => $city,
        ]);
    }
}
