<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'offer_id',
        'seller_id',
        'quantity',
        'price_at_addition',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_at_addition' => 'decimal:2',
        ];
    }

    /**
     * Get the cart that owns this item
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product for this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the offer for this item
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * Get the seller for this item
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get subtotal for this item
     */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->price_at_addition * $this->quantity;
    }

    /**
     * Check if price has changed since added to cart
     */
    public function hasPriceChanged(): bool
    {
        return $this->offer && $this->offer->price != $this->price_at_addition;
    }

    /**
     * Get price difference (positive = increased, negative = decreased)
     */
    public function getPriceDifferenceAttribute(): float
    {
        if (!$this->offer) {
            return 0;
        }
        return (float) $this->offer->price - (float) $this->price_at_addition;
    }

    /**
     * Update price to current offer price
     */
    public function syncPriceWithOffer(): void
    {
        if ($this->offer) {
            $this->update(['price_at_addition' => $this->offer->price]);
        }
    }
}
