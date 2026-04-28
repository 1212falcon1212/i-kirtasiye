<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Şifre sıfırlama işlemini gerçekleştirir
 */
class ResetPasswordController extends Controller
{
    /**
     * Token ile şifre sıfırlama işlemini tamamlar
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'token.required' => 'Sıfırlama tokeni zorunludur.',
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'password.required' => 'Yeni şifre zorunludur.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Mevcut tüm token'ları iptal et (tüm cihazlardan çıkış)
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => $this->getErrorMessage($status),
            ], 422);
        }

        // Yeni oturum token'ı oluştur
        $user = User::where('email', $request->email)->first();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Şifreniz başarıyla sıfırlandı.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'business_name' => $user->business_name,
                'nickname' => $user->nickname,
                'display_name' => $user->display_name,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Password broker durum koduna göre Türkçe hata mesajı döndürür
     */
    private function getErrorMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'Bu e-posta adresiyle kayıtlı bir kullanıcı bulunamadı.',
            Password::INVALID_TOKEN => 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş.',
            Password::RESET_THROTTLED => 'Lütfen tekrar denemeden önce biraz bekleyiniz.',
            default => 'Şifre sıfırlama işlemi başarısız oldu.',
        };
    }
}
