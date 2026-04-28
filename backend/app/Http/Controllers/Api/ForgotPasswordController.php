<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * Şifre sıfırlama bağlantısı gönderme işlemlerini yönetir
 */
class ForgotPasswordController extends Controller
{
    /**
     * Kullanıcıya şifre sıfırlama bağlantısı gönderir
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Güvenlik: Kullanıcı var olsun ya da olmasın aynı mesajı döndür
        return response()->json([
            'success' => true,
            'message' => 'E-posta adresiniz sistemde kayıtlıysa, şifre sıfırlama bağlantısı gönderildi.',
        ]);
    }
}
