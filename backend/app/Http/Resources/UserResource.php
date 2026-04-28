<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'business_name' => $this->business_name,
            'vergi_no' => $this->vergi_no,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'role' => $this->role,
            'is_verified' => $this->is_verified,
            'verification_status' => $this->verification_status,
            'verification_status_label' => $this->verification_status_label,
            'documents_approved' => $this->documents_approved,
            'can_access_platform' => $this->canAccessPlatform(),
            'is_retailer' => $this->isRetailer(),
            'is_seller' => $this->isSeller(),
            'is_super_admin' => $this->isSuperAdmin(),
            'wallet' => $this->whenLoaded('wallet', function () {
                return [
                    'id' => $this->wallet->id,
                    'balance' => $this->formatPrice($this->wallet->balance ?? 0),
                    'pending_balance' => $this->formatPrice($this->wallet->pending_balance ?? 0),
                ];
            }),
            'active_offers_count' => $this->when(
                $this->relationLoaded('activeOffers'),
                fn() => $this->activeOffers->count()
            ),
            'orders_count' => $this->when(
                $this->relationLoaded('orders'),
                fn() => $this->orders->count()
            ),
            'documents' => $this->whenLoaded('sellerDocuments', function () {
                return $this->sellerDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'type' => $doc->type,
                        'type_label' => $doc->type_label,
                        'status' => $doc->status,
                        'status_label' => $doc->status_label,
                        'file_url' => $doc->file_url,
                        'rejection_reason' => $doc->rejection_reason,
                        'uploaded_at' => $doc->created_at->toIso8601String(),
                        'reviewed_at' => $doc->reviewed_at?->toIso8601String(),
                    ];
                });
            }),
            'has_required_documents' => $this->hasRequiredDocuments(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Format price with currency.
     */
    private function formatPrice($amount): array
    {
        return [
            'amount' => (float) $amount,
            'formatted' => number_format((float) $amount, 2, ',', '.') . ' TL',
            'currency' => 'TRY',
        ];
    }
}
