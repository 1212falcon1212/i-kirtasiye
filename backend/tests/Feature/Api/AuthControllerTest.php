<?php

namespace Tests\Feature\Api;

use App\Models\GlnWhitelist;
use App\Models\User;
use App\Services\GLN\GlnVerificationResult;
use App\Services\GLN\WhitelistGlnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can register with valid GLN code.
     */
    public function test_user_can_register(): void
    {
        // Create a valid GLN code in the whitelist (8680000000013 has valid check digit)
        $glnWhitelist = GlnWhitelist::factory()->create([
            'gln_code' => '8680000000013',
            'pharmacy_name' => 'Test Eczanesi',
            'city' => 'Istanbul',
            'district' => 'Kadikoy',
            'address' => 'Test Sokak No:1',
            'is_active' => true,
            'is_used' => false,
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gln_code' => '8680000000013',
            'pharmacy_name' => 'Test Eczanesi',
            'nickname' => 'testeczane',
            'phone' => '5551234567',
            'address' => 'Test Address',
            'city' => 'Istanbul',
            'role' => 'pharmacy',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'email',
                    'gln_code',
                    'pharmacy_name',
                    'city',
                    'is_verified',
                    'role',
                ],
                'token',
            ])
            ->assertJson([
                'message' => 'Kayit basarili.',
                'user' => [
                    'email' => 'test@example.com',
                    'gln_code' => '8680000000013',
                    'role' => 'pharmacy',
                    'is_verified' => true,
                ],
            ]);

        // Check user was created in database
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'gln_code' => '8680000000013',
            'role' => 'pharmacy',
        ]);

        // Check GLN was marked as used
        $this->assertDatabaseHas('gln_whitelist', [
            'gln_code' => '8680000000013',
            'is_used' => true,
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
                    'gln_code',
                    'pharmacy_name',
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

        // Check token was created
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

        // Create a token for the user
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Çıkış başarılı.',
            ]);

        // Check token was deleted
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
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'is_verified' => true,
        ]);

        // Test with wrong password
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
        $user = User::factory()->create([
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
     * Test registration fails with already registered GLN.
     */
    public function test_registration_fails_with_already_registered_gln(): void
    {
        // Create a GLN that is already used
        $existingUser = User::factory()->create([
            'gln_code' => '8680000000013',
        ]);

        GlnWhitelist::factory()->create([
            'gln_code' => '8680000000013',
            'is_active' => true,
            'is_used' => true,
            'used_by_user_id' => $existingUser->id,
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'new@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gln_code' => '8680000000013',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test registration fails with invalid GLN format.
     */
    public function test_registration_fails_with_invalid_gln_format(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gln_code' => '123456', // Invalid length
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gln_code']);
    }

    /**
     * Test registration fails with weak password.
     */
    public function test_registration_fails_with_weak_password(): void
    {
        GlnWhitelist::factory()->create([
            'gln_code' => '8680000000013',
            'is_active' => true,
            'is_used' => false,
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'gln_code' => '8680000000013',
        ]);

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
            'pharmacy_name' => 'Test Eczanesi',
            'is_verified' => true,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'email',
                    'gln_code',
                    'pharmacy_name',
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
                    'pharmacy_name' => 'Test Eczanesi',
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

        // Create multiple tokens
        $token1 = $user->createToken('auth-token-1')->plainTextToken;
        $token2 = $user->createToken('auth-token-2')->plainTextToken;
        $token3 = $user->createToken('auth-token-3')->plainTextToken;

        // Verify tokens exist
        $this->assertEquals(3, $user->tokens()->count());

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->postJson('/api/auth/logout-all');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tüm cihazlardan çıkış yapıldı.',
            ]);

        // Check all tokens were deleted
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

        // Create some existing tokens
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
