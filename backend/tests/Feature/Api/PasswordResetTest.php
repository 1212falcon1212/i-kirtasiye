<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // FORGOT PASSWORD TESTS
    // ==========================================

    /**
     * Test forgot password sends success response for valid email.
     */
    public function test_forgot_password_returns_success_for_valid_email(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'E-posta adresiniz sistemde kayıtlıysa, şifre sıfırlama bağlantısı gönderildi.',
            ]);
    }

    /**
     * Test forgot password returns same success message for nonexistent email (security).
     */
    public function test_forgot_password_returns_success_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'E-posta adresiniz sistemde kayıtlıysa, şifre sıfırlama bağlantısı gönderildi.',
            ]);
    }

    /**
     * Test forgot password validates email is required.
     */
    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test forgot password validates email format.
     */
    public function test_forgot_password_validates_email_format(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ==========================================
    // RESET PASSWORD TESTS
    // ==========================================

    /**
     * Test reset password with valid token.
     */
    public function test_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Şifreniz başarıyla sıfırlandı.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'token',
                'user' => ['id', 'email', 'role'],
            ]);

        // Verify the password was changed
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    /**
     * Test reset password with invalid token returns error.
     */
    public function test_reset_password_with_invalid_token(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test reset password deletes all existing tokens.
     */
    public function test_reset_password_revokes_existing_tokens(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $user->createToken('old-token-1');
        $user->createToken('old-token-2');

        $this->assertEquals(2, $user->tokens()->count());

        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        // Old tokens should be deleted; only the new auth token should exist
        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }

    /**
     * Test reset password validates short password.
     */
    public function test_reset_password_validates_short_password(): void
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test reset password validates password confirmation.
     */
    public function test_reset_password_validates_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test reset password requires all fields.
     */
    public function test_reset_password_requires_all_fields(): void
    {
        $response = $this->postJson('/api/auth/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    }

    /**
     * Test reset password with wrong email returns error.
     */
    public function test_reset_password_with_wrong_email(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => 'wrong@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }
}
