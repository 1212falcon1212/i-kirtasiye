<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'sub_order_id',
        'product_id',
        'offer_id',
        'seller_id',
        'quantity',
        'unit_price',
        'total_price',
        'commission_rate',
        'commission_amount',
        'marketplace_fee',
        'withholding_tax',
        'shipping_cost_share',
        'net_seller_amount',
        'seller_payout_amount',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'marketplace_fee' => 'decimal:2',
            'withholding_tax' => 'decimal:2',
            'shipping_cost_share' => 'decimal:2',
            'net_seller_amount' => 'decimal:2',
            'seller_payout_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the order that owns this item
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the sub-order this item belongs to
     */
    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
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
     * Get the review for this item
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
