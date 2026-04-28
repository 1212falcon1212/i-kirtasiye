<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Generate a valid GLN code with proper check digit
     */
    protected function generateValidGlnCode(): string
    {
        // Generate 12 random digits starting with 868 (Turkey prefix)
        $digits = '868'.fake()->numerify('#########');

        // Calculate check digit using GS1 standard
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $multiplier = ($i % 2 === 0) ? 1 : 3;
            $sum += (int) $digits[$i] * $multiplier;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $digits.$checkDigit;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'gln_code' => $this->generateValidGlnCode(),
            'pharmacy_name' => fake()->company().' Eczanesi',
            'phone' => '5'.fake()->numerify('##').fake()->numerify('#######'),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Istanbul', 'Ankara', 'Izmir', 'Bursa', 'Antalya', 'Adana', 'Konya']),
            'role' => 'pharmacist',
            'is_verified' => true,
            'verification_status' => 'approved',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'is_verified' => false,
            'verification_status' => 'pending',
        ]);
    }

    /**
     * Super admin user
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super-admin',
            'is_verified' => true,
            'verification_status' => 'approved',
        ]);
    }

    /**
     * Seller user (eczacı that can sell)
     */
    public function seller(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'pharmacist',
            'is_verified' => true,
            'verification_status' => 'approved',
        ]);
    }

    /**
     * Pending verification user
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verification_status' => 'pending',
        ]);
    }

    /**
     * Rejected user
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verification_status' => 'rejected',
            'rejection_reason' => 'Belgeler eksik veya geçersiz.',
        ]);
    }

    /**
     * Set specific GLN code
     */
    public function withGlnCode(string $glnCode): static
    {
        return $this->state(fn (array $attributes) => [
            'gln_code' => $glnCode,
        ]);
    }

    /**
     * Set specific city
     */
    public function inCity(string $city): static
    {
        return $this->state(fn (array $attributes) => [
            'city' => $city,
        ]);
    }
}
