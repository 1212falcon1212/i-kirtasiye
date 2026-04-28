<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'shipping_status' => $this->shipping_status,
            'subtotal' => $this->formatPrice($this->subtotal),
            'total_commission' => $this->formatPrice($this->total_commission),
            'shipping_cost' => $this->formatPrice($this->shipping_cost),
            'total_amount' => $this->formatPrice($this->total_amount),
            'shipping_address' => $this->shipping_address,
            'shipping_provider' => $this->shipping_provider,
            'tracking_number' => $this->tracking_number,
            'shipping_label_url' => $this->shipping_label_url,
            'notes' => $this->notes,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'business_name' => $this->user->business_name,
                    'phone' => $this->user->phone,
                ];
            }),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'unit_price' => $this->formatPrice($item->unit_price),
                        'total_price' => $this->formatPrice($item->total_price),
                        'commission_rate' => $item->commission_rate,
                        'commission_amount' => $this->formatPrice($item->commission_amount),
                        'seller_payout_amount' => $this->formatPrice($item->seller_payout_amount),
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'barcode' => $item->product->barcode,
                            'image' => $item->product->image,
                            'brand' => $item->product->brand,
                        ] : null,
                        'seller' => $item->seller ? [
                            'id' => $item->seller->id,
                            'business_name' => $item->seller->business_name,
                        ] : null,
                    ];
                });
            }),
            'items_by_seller' => $this->when(
                $this->relationLoaded('items'),
                fn() => $this->items_by_seller
            ),
            'can_be_cancelled' => $this->canBeCancelled(),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
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
