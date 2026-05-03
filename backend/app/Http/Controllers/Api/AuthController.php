<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * - Retailer (kırtasiyeci): vergi_no required (10 haneli, unique)
     * - Seller (tedarikçi): vergi_no optional
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $role = $validated['role'] ?? 'retailer';

        $user = DB::transaction(function () use ($validated, $role) {
            $userData = [
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'business_name' => $validated['business_name'],
                'nickname' => $validated['nickname'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'district' => $validated['district'] ?? null,
                'vergi_no' => $validated['vergi_no'] ?? null,
                'role' => $role,
                'is_verified' => true,
                'verified_at' => now(),
            ];

            return User::create($userData);
        });

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Kayıt başarılı.',
            'user' => $this->presentUser($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Login user and return token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Geçersiz e-posta veya şifre.',
                'error_code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        if (! $user->is_verified) {
            return response()->json([
                'message' => 'Hesabınız henüz doğrulanmamış.',
                'error_code' => 'NOT_VERIFIED',
            ], 403);
        }

        if ($validated['revoke_others'] ?? false) {
            $user->tokens()->delete();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Giriş başarılı.',
            'user' => $this->presentUser($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Çıkış başarılı.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Tüm cihazlardan çıkış yapıldı.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $this->presentUser($user, includeTimestamps: true),
        ]);
    }

    /**
     * Vergi numarası format ve duplicate kontrolü (public endpoint).
     */
    public function verifyVergiNo(Request $request): JsonResponse
    {
        $request->validate([
            'vergi_no' => 'required|string|digits:10',
        ]);

        if (User::where('vergi_no', $request->vergi_no)->exists()) {
            return response()->json([
                'valid' => false,
                'already_registered' => true,
                'message' => 'Bu vergi numarası zaten kayıtlı.',
                'error_code' => 'ALREADY_REGISTERED',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'already_registered' => false,
            'vergi_no' => $request->vergi_no,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mevcut şifre yanlış.',
                'error_code' => 'INVALID_CURRENT_PASSWORD',
            ], 400);
        }

        $user->update(['password' => Hash::make($validated['new_password'])]);

        return response()->json([
            'success' => true,
            'message' => 'Şifreniz başarıyla değiştirildi.',
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nickname' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city_id' => 'nullable|integer|exists:cities,id',
            'district_id' => 'nullable|integer|exists:districts,id',
            'trade_name' => 'nullable|string|max:255',
            'kep_address' => 'nullable|string|max:255',
            'mersis_no' => 'nullable|string|max:20',
            'tax_number' => 'nullable|string|max:20',
            'tax_office' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        // Retailer users: address+city+district fields come from the vergi no whitelist; lock those.
        if ($user->isRetailer()) {
            unset($validated['address'], $validated['city'], $validated['city_id']);
        }

        // Lock trade fields once set
        $tradeFields = ['trade_name', 'tax_number', 'tax_office', 'mersis_no', 'kep_address'];
        foreach ($tradeFields as $field) {
            if (! empty($user->{$field}) && isset($validated[$field])) {
                unset($validated[$field]);
            }
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil bilgileriniz güncellendi.',
            'user' => $this->presentUser($user, includeTimestamps: true),
        ]);
    }

    public function deactivateAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Şifre yanlış.',
                'error_code' => 'INVALID_PASSWORD',
            ], 400);
        }

        $user->update([
            'is_verified' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $validated['reason'] ?? null,
        ]);

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hesabınız devre dışı bırakıldı.',
        ]);
    }

    /**
     * Build the user payload for API responses.
     */
    private function presentUser(User $user, bool $includeTimestamps = false): array
    {
        $payload = [
            'id' => $user->id,
            'email' => $user->email,
            'vergi_no' => $user->vergi_no,
            'business_name' => $user->business_name,
            'nickname' => $user->nickname,
            'display_name' => $user->display_name,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'district' => $user->district,
            'city_id' => $user->city_id,
            'district_id' => $user->district_id,
            'trade_name' => $user->trade_name,
            'kep_address' => $user->kep_address,
            'mersis_no' => $user->mersis_no,
            'tax_number' => $user->tax_number,
            'tax_office' => $user->tax_office,
            'is_verified' => $user->is_verified,
            'role' => $user->role,
        ];

        if ($includeTimestamps) {
            $payload['created_at'] = $user->created_at;
        }

        return $payload;
    }
}
