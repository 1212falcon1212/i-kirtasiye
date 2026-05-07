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
     * Generate a 10-digit Turkish vergi numarası (rastgele, sadece test verisi).
     */
    protected function generateVergiNo(): string
    {
        return (string) fake()->numerify('##########');
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
            'vergi_no' => $this->generateVergiNo(),
            'business_name' => fake()->company().' Kırtasiye',
            'phone' => '5'.fake()->numerify('##').fake()->numerify('#######'),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Istanbul', 'Ankara', 'Izmir', 'Bursa', 'Antalya', 'Adana', 'Konya']),
            'role' => User::ROLE_RETAILER,
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
            'role' => User::ROLE_SUPER_ADMIN,
            'is_verified' => true,
            'verification_status' => 'approved',
        ]);
    }

    /**
     * Seller / tedarikçi (kırtasiye ürünü satışı yapan).
     */
    public function seller(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SELLER,
            'is_verified' => true,
            'verification_status' => 'approved',
        ]);
    }

    /**
     * Retailer / kırtasiyeci (alıcı).
     */
    public function retailer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_RETAILER,
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
     * Set specific vergi numarası.
     */
    public function withVergiNo(string $vergiNo): static
    {
        return $this->state(fn (array $attributes) => [
            'vergi_no' => $vergiNo,
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
