<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'status' => $this->status,
            'total' => $this->formatPrice($this->total),
            'item_count' => $this->item_count,
            'is_empty' => $this->isEmpty(),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'price_at_addition' => $this->formatPrice($item->price_at_addition),
                        'subtotal' => $this->formatPrice($item->subtotal),
                        'has_price_changed' => $item->hasPriceChanged(),
                        'price_difference' => $this->formatPrice($item->price_difference),
                        'offer' => $item->offer ? [
                            'id' => $item->offer->id,
                            'price' => $this->formatPrice($item->offer->price),
                            'stock' => $item->offer->stock,
                            'expiry_date' => $item->offer->expiry_date?->format('Y-m-d'),
                            'is_available' => $item->offer->isAvailable(),
                        ] : null,
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
