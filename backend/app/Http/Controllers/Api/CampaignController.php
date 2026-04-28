<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    /**
     * List campaigns for the authenticated seller
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);

        $query = $request->user()->campaigns()
            ->with(['product:id,name,barcode,image', 'giftProduct:id,name,barcode,image']);

        if ($status) {
            $query->where('status', $status);
        }

        $campaigns = $query->latest()->paginate($perPage);

        return response()->json([
            'campaigns' => $campaigns->items(),
            'pagination' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ]);
    }

    /**
     * Create a new campaign
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if seller can create campaigns (rating >= 7)
        if (!$user->canCreateCampaign()) {
            return response()->json([
                'message' => 'Puanınız 7\'nin altında olduğu için kampanya oluşturamazsınız.',
                'seller_score' => $user->seller_score,
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:product_discount,store_discount,brand_discount,gift_product',
            'discount_rate' => 'required_unless:type,gift_product|nullable|numeric|min:1|max:100',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'product_id' => 'required_if:type,product_discount|nullable|exists:products,id',
            'brand' => 'required_if:type,brand_discount|nullable|string|max:255',
            'gift_product_id' => 'required_if:type,gift_product|nullable|exists:products,id',
            'gift_quantity' => 'nullable|integer|min:1',
            'starts_at' => 'required|date|after_or_equal:today',
            'ends_at' => 'required|date|after:starts_at',
        ], [
            'name.required' => 'Kampanya adı zorunludur.',
            'type.required' => 'Kampanya tipi seçmelisiniz.',
            'discount_rate.required_unless' => 'İndirim oranı zorunludur.',
            'discount_rate.min' => 'İndirim oranı en az %1 olmalıdır.',
            'discount_rate.max' => 'İndirim oranı en fazla %100 olabilir.',
            'product_id.required_if' => 'Ürün indirimi için ürün seçmelisiniz.',
            'brand.required_if' => 'Marka indirimi için marka seçmelisiniz.',
            'gift_product_id.required_if' => 'Hediye kampanyası için hediye ürün seçmelisiniz.',
            'starts_at.required' => 'Başlangıç tarihi zorunludur.',
            'starts_at.after_or_equal' => 'Başlangıç tarihi bugün veya sonrası olmalıdır.',
            'ends_at.required' => 'Bitiş tarihi zorunludur.',
            'ends_at.after' => 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Doğrulama hatası.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $campaign = $user->campaigns()->create([
            ...$validator->validated(),
            'status' => Campaign::STATUS_PENDING,
        ]);

        $campaign->load(['product:id,name,barcode,image', 'giftProduct:id,name,barcode,image']);

        return response()->json([
            'message' => 'Kampanya başarıyla oluşturuldu. Yönetici onayı bekleniyor.',
            'campaign' => $campaign,
        ], 201);
    }

    /**
     * Get a specific campaign
     */
    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        // Check ownership
        if ($campaign->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bu kampanyayı görüntüleme yetkiniz yok.',
            ], 403);
        }

        $campaign->load(['product:id,name,barcode,image', 'giftProduct:id,name,barcode,image']);

        return response()->json([
            'campaign' => $campaign,
        ]);
    }

    /**
     * Update a campaign
     */
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        // Check ownership
        if ($campaign->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bu kampanyayı düzenleme yetkiniz yok.',
            ], 403);
        }

        // Only allow updating pending or inactive campaigns
        if (!in_array($campaign->status, [Campaign::STATUS_PENDING, Campaign::STATUS_INACTIVE])) {
            return response()->json([
                'message' => 'Aktif veya reddedilen kampanyalar düzenlenemez.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'discount_rate' => 'nullable|numeric|min:1|max:100',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'gift_quantity' => 'nullable|integer|min:1',
            'starts_at' => 'sometimes|required|date',
            'ends_at' => 'sometimes|required|date|after:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Doğrulama hatası.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $campaign->update($validator->validated());
        $campaign->load(['product:id,name,barcode,image', 'giftProduct:id,name,barcode,image']);

        return response()->json([
            'message' => 'Kampanya başarıyla güncellendi.',
            'campaign' => $campaign,
        ]);
    }

    /**
     * Delete a campaign
     */
    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        // Check ownership
        if ($campaign->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bu kampanyayı silme yetkiniz yok.',
            ], 403);
        }

        // Only allow deleting pending, inactive, or rejected campaigns
        if ($campaign->status === Campaign::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Aktif kampanyalar silinemez. Önce pasife alın.',
            ], 400);
        }

        $campaign->delete();

        return response()->json([
            'message' => 'Kampanya başarıyla silindi.',
        ]);
    }

    /**
     * Toggle campaign status (activate/deactivate)
     */
    public function toggleStatus(Request $request, Campaign $campaign): JsonResponse
    {
        // Check ownership
        if ($campaign->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bu kampanyayı düzenleme yetkiniz yok.',
            ], 403);
        }

        // Only allow toggling approved campaigns
        if (!in_array($campaign->status, [Campaign::STATUS_ACTIVE, Campaign::STATUS_INACTIVE])) {
            return response()->json([
                'message' => 'Sadece onaylanmış kampanyaların durumu değiştirilebilir.',
            ], 400);
        }

        if ($campaign->status === Campaign::STATUS_ACTIVE) {
            $campaign->update(['status' => Campaign::STATUS_INACTIVE]);
            $message = 'Kampanya pasife alındı.';
        } else {
            // Check if campaign dates are still valid
            if ($campaign->ends_at < now()) {
                return response()->json([
                    'message' => 'Süresi dolmuş kampanya aktifleştirilemez.',
                ], 400);
            }
            $campaign->update(['status' => Campaign::STATUS_ACTIVE]);
            $message = 'Kampanya aktifleştirildi.';
        }

        return response()->json([
            'message' => $message,
            'campaign' => $campaign,
        ]);
    }

    /**
     * Get public active campaigns (for buyers)
     */
    public function active(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $type = $request->input('type');
        $sellerId = $request->input('seller_id');

        $query = Campaign::active()
            ->with(['seller:id,business_name,nickname,city', 'product:id,name,barcode,image', 'giftProduct:id,name,barcode,image']);

        if ($type) {
            $query->where('type', $type);
        }

        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }

        $campaigns = $query->orderBy('ends_at', 'asc')->paginate($perPage);

        return response()->json([
            'campaigns' => $campaigns->items(),
            'pagination' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ]);
    }
}
