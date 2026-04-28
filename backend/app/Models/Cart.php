<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'status',
    ];

    /**
     * Get the user that owns this cart
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all items in this cart
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Scope for active carts only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get items grouped by seller
     * Eager loading ile N+1 problemi cozuldu
     */
    public function getItemsBySellerAttribute(): array
    {
        // Eager load seller relation to avoid N+1 queries
        $items = $this->items->load('seller');

        return $items
            ->groupBy('seller_id')
            ->map(function ($sellerItems) {
                return [
                    'seller' => $sellerItems->first()->seller,
                    'items' => $sellerItems,
                    'subtotal' => $sellerItems->sum(fn($item) => $item->subtotal),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get total cart amount
     */
    public function getTotalAttribute(): float
    {
        return $this->items->sum(fn($item) => $item->subtotal);
    }

    /**
     * Get total item count
     */
    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Mark cart as converted (after order created)
     */
    public function markAsConverted(): void
    {
        $this->update(['status' => 'converted']);
    }
}
