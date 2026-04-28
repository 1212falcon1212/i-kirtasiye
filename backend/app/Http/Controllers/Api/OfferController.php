<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOfferRequest;
use App\Http\Requests\Api\UpdateOfferRequest;
use App\Models\DistributorRetailerLink;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    /**
     * Get all offers (filtered by product)
     * All users can see all active offers (retailer-to-retailer allowed)
     */
    public function index(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $perPage = $request->input('per_page', 15);

        // Show all active offers
        $query = Offer::with(['product:id,name,barcode,brand', 'seller:id,business_name,nickname,city,role'])
            ->where('status', Offer::STATUS_ACTIVE);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $offers = $query->orderBy('price', 'asc')->paginate($perPage);

        return response()->json([
            'offers' => $offers->items(),
            'pagination' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }

    /**
     * Get current user's offers
     */
    public function myOffers(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);

        $query = $request->user()->offers()
            ->with(['product:id,name,barcode,brand,image']);

        if ($status) {
            $query->where('status', $status);
        }

        $offers = $query->latest()->paginate($perPage);

        return response()->json([
            'offers' => $offers->items(),
            'pagination' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }

    /**
     * Create a new offer
     * New offers are immediately active (no admin approval required)
     */
    public function store(StoreOfferRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // New offers are immediately active
        $validated['status'] = Offer::STATUS_ACTIVE;

        $offer = $request->user()->offers()->create($validated);

        $offer->load(['product:id,name,barcode,brand']);

        return response()->json([
            'message' => 'Teklif başarıyla oluşturuldu ve yayınlandı.',
            'offer' => $offer,
        ], 201);
    }

    /**
     * Toggle offer status between active and inactive
     */
    public function toggleStatus(Request $request, Offer $offer): JsonResponse
    {
        $this->authorize('update', $offer);

        // Toggle between active and inactive
        if ($offer->status === Offer::STATUS_ACTIVE) {
            $offer->status = Offer::STATUS_INACTIVE;
            $message = 'İlan pasife alındı.';
        } else {
            $offer->status = Offer::STATUS_ACTIVE;
            $message = 'İlan aktife alındı.';
        }

        $offer->save();
        $offer->load(['product:id,name,barcode,brand']);

        return response()->json([
            'message' => $message,
            'offer' => $offer,
        ]);
    }

    /**
     * Get single offer
     */
    public function show(Offer $offer): JsonResponse
    {
        $offer->load(['product', 'seller:id,business_name,nickname,city,role']);

        return response()->json([
            'offer' => $offer,
        ]);
    }

    /**
     * Update an offer (only owner can update)
     */
    public function update(UpdateOfferRequest $request, Offer $offer): JsonResponse
    {
        $this->authorize('update', $offer);

        $offer->update($request->validated());
        $offer->load(['product:id,name,barcode,brand']);

        return response()->json([
            'message' => 'Teklif başarıyla güncellendi.',
            'offer' => $offer,
        ]);
    }

    /**
     * Delete an offer (only owner can delete)
     */
    public function destroy(Request $request, Offer $offer): JsonResponse
    {
        $this->authorize('delete', $offer);

        $offer->delete();

        return response()->json([
            'message' => 'Teklif başarıyla silindi.',
        ]);
    }

    /**
     * Get a seller's active offers
     * Accessible by all authenticated users (pharmacies as buyers, companies with approved link)
     */
    public function getSellerOffers(int $sellerId, Request $request): JsonResponse
    {
        $seller = User::find($sellerId);

        if (! $seller) {
            return response()->json([
                'message' => 'Satıcı bulunamadı.',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);

        $offers = Offer::where('seller_id', $sellerId)
            ->where('status', Offer::STATUS_ACTIVE)
            ->with(['product:id,name,barcode,brand,image'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'seller' => [
                'id' => $seller->id,
                'business_name' => $seller->business_name,
                'nickname' => $seller->nickname,
                'city' => $seller->city,
                'seller_score' => $seller->seller_score,
                'seller_review_count' => $seller->seller_review_count,
            ],
            'offers' => $offers->items(),
            'pagination' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }
}
