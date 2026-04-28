<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    /**
     * List coupons for the authenticated seller
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);

        $query = $request->user()->coupons()
            ->with(['campaign:id,name,type']);

        if ($status) {
            $query->where('status', $status);
        }

        $coupons = $query->latest()->paginate($perPage);

        return response()->json([
            'coupons' => $coupons->items(),
            'pagination' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    /**
     * Create a new coupon
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ], [
            'code.unique' => 'Bu kupon kodu zaten kullanılıyor.',
            'name.required' => 'Kupon adı zorunludur.',
            'discount_type.required' => 'İndirim tipi seçmelisiniz.',
            'discount_value.required' => 'İndirim değeri zorunludur.',
            'discount_value.min' => 'İndirim değeri 0\'dan büyük olmalıdır.',
            'ends_at.after' => 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Doğrulama hatası.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Generate code if not provided
        if (empty($data['code'])) {
            $data['code'] = strtoupper(Str::random(8));
        } else {
            $data['code'] = strtoupper($data['code']);
        }

        // Validate campaign ownership if provided
        if (!empty($data['campaign_id'])) {
            $campaign = $request->user()->campaigns()->find($data['campaign_id']);
            if (!$campaign) {
                return response()->json([
                    'message' => 'Belirtilen kampanya size ait değil.',
                ], 403);
            }
        }

        $coupon = $request->user()->coupons()->create($data);
        $coupon->load(['campaign:id,name,type']);

        return response()->json([
            'message' => 'Kupon başarıyla oluşturuldu.',
            'coupon' => $coupon,
        ], 201);
    }

    /**
     * Apply a coupon to cart
     */
    public function apply(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'cart_total' => 'required|numeric|min:0',
            'seller_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Doğrulama hatası.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = strtoupper($request->input('code'));
        $cartTotal = $request->input('cart_total');
        $sellerId = $request->input('seller_id');

        $coupon = Coupon::findByCode($code);

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Geçersiz kupon kodu.',
            ], 404);
        }

        // Validate coupon can be used
        $validation = $coupon->canBeUsedBy($request->user(), $cartTotal, $sellerId);

        if (!$validation['valid']) {
            return response()->json([
                'valid' => false,
                'message' => $validation['message'],
            ], 400);
        }

        // Calculate discount
        $discountAmount = $coupon->calculateDiscount($cartTotal);

        return response()->json([
            'valid' => true,
            'message' => 'Kupon uygulandı.',
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'formatted_discount' => $coupon->formatted_discount,
            ],
            'discount_amount' => $discountAmount,
            'formatted_discount_amount' => '₺' . number_format($discountAmount, 2, ',', '.'),
            'new_total' => $cartTotal - $discountAmount,
            'formatted_new_total' => '₺' . number_format($cartTotal - $discountAmount, 2, ',', '.'),
        ]);
    }

    /**
     * Remove applied coupon (just returns success, actual removal happens in frontend state)
     */
    public function remove(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Kupon kaldırıldı.',
        ]);
    }

    /**
     * Delete a coupon
     */
    public function destroy(Request $request, Coupon $coupon): JsonResponse
    {
        // Check ownership
        if ($coupon->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bu kuponu silme yetkiniz yok.',
            ], 403);
        }

        // Check if coupon has been used
        if ($coupon->used_count > 0) {
            return response()->json([
                'message' => 'Kullanılmış kuponlar silinemez. Pasife alabilirsiniz.',
            ], 400);
        }

        $coupon->delete();

        return response()->json([
            'message' => 'Kupon başarıyla silindi.',
        ]);
    }

    /**
     * Toggle coupon status
     */
    public function toggleStatus(Request $request, Coupon $coupon): JsonResponse
    {
        // Check ownership
        if ($coupon->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bu kuponu düzenleme yetkiniz yok.',
            ], 403);
        }

        if ($coupon->status === Coupon::STATUS_ACTIVE) {
            $coupon->update(['status' => Coupon::STATUS_INACTIVE]);
            $message = 'Kupon pasife alındı.';
        } else {
            $coupon->update(['status' => Coupon::STATUS_ACTIVE]);
            $message = 'Kupon aktifleştirildi.';
        }

        return response()->json([
            'message' => $message,
            'coupon' => $coupon,
        ]);
    }
}
