<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\VergiNoWhitelist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: build a valid retailer registration payload.
     *
     * @return array<string, mixed>
     */
    protected function retailerRegisterPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Test Kırtasiye',
            'nickname' => 'testkirtasiye',
            'vergi_no' => '1234567890',
            'phone' => '5551234567',
            'address' => 'Test Address',
            'city' => 'Istanbul',
            'role' => User::ROLE_RETAILER,
        ], $overrides);
    }

    /**
     * Test user can register with valid vergi numarası.
     */
    public function test_user_can_register(): void
    {
        VergiNoWhitelist::query()->create([
            'vergi_no' => '1234567890',
            'company_name' => 'Test Kırtasiye',
            'city' => 'Istanbul',
            'district' => 'Kadıköy',
            'address' => 'Test Sokak No:1',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/register', $this->retailerRegisterPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'email',
                    'vergi_no',
                    'business_name',
                    'city',
                    'is_verified',
                    'role',
                ],
                'token',
            ])
            ->assertJson([
                'user' => [
                    'email' => 'test@example.com',
                    'vergi_no' => '1234567890',
                    'role' => User::ROLE_RETAILER,
                    'is_verified' => true,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'vergi_no' => '1234567890',
            'role' => User::ROLE_RETAILER,
        ]);
    }

    /**
     * Test user can login with valid credentials.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'is_verified' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'email',
                    'vergi_no',
                    'business_name',
                    'city',
                    'is_verified',
                    'role',
                ],
                'token',
            ])
            ->assertJson([
                'message' => 'Giriş başarılı.',
                'user' => [
                    'id' => $user->id,
                    'email' => 'test@example.com',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * Test user can logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'is_verified' => true,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Çıkış başarılı.',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * Test user cannot login with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'is_verified' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Geçersiz e-posta veya şifre.',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    /**
     * Test user cannot login with non-existent email.
     */
    public function test_user_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Geçersiz e-posta veya şifre.',
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    /**
     * Test unverified user cannot login.
     */
    public function test_unverified_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'is_verified' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Hesabınız henüz doğrulanmamış.',
                'error_code' => 'NOT_VERIFIED',
            ]);
    }

    /**
     * Test registration fails with already registered vergi numarası.
     */
    public function test_registration_fails_with_already_registered_vergi_no(): void
    {
        User::factory()->create([
            'vergi_no' => '1234567890',
        ]);

        $response = $this->postJson('/api/auth/register', $this->retailerRegisterPayload([
            'email' => 'new@example.com',
            'vergi_no' => '1234567890',
        ]));

        $response->assertStatus(422);
    }

    /**
     * Test registration fails with invalid vergi numarası format.
     */
    public function test_registration_fails_with_invalid_vergi_no_format(): void
    {
        $response = $this->postJson('/api/auth/register', $this->retailerRegisterPayload([
            'vergi_no' => '12345',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vergi_no']);
    }

    /**
     * Test registration fails with weak password.
     */
    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register', $this->retailerRegisterPayload([
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test login validation requires email and password.
     */
    public function test_login_validation_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test authenticated user can get their profile.
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'business_name' => 'Test Kırtasiye',
            'is_verified' => true,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'email',
                    'vergi_no',
                    'business_name',
                    'phone',
                    'address',
                    'city',
                    'is_verified',
                    'role',
                    'created_at',
                ],
            ])
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => 'test@example.com',
                    'business_name' => 'Test Kırtasiye',
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot access protected routes.
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    /**
     * Test user can logout from all devices.
     */
    public function test_user_can_logout_from_all_devices(): void
    {
        $user = User::factory()->create([
            'is_verified' => true,
        ]);

        $token1 = $user->createToken('auth-token-1')->plainTextToken;
        $user->createToken('auth-token-2');
        $user->createToken('auth-token-3');

        $this->assertEquals(3, $user->tokens()->count());

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token1,
        ])->postJson('/api/auth/logout-all');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tüm cihazlardan çıkış yapıldı.',
            ]);

        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    /**
     * Test login with revoke_others option.
     */
    public function test_login_with_revoke_others_deletes_old_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'is_verified' => true,
        ]);

        $user->createToken('old-token-1');
        $user->createToken('old-token-2');

        $this->assertEquals(2, $user->tokens()->count());

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'revoke_others' => true,
        ]);

        $response->assertStatus(200);

        // Should only have the new token
        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }
}
