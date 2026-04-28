<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Onay Bekliyor',
        self::STATUS_APPROVED => 'Onaylandı',
        self::STATUS_REJECTED => 'Reddedildi',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_item_id',
        'product_id',
        'seller_id',
        'buyer_id',
        'rating',
        'delivery_rating',
        'quality_rating',
        'communication_rating',
        'comment',
        'status',
        'rejection_reason',
        'seller_reply',
        'seller_replied_at',
        'moderated_by',
        'moderated_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'delivery_rating' => 'integer',
            'quality_rating' => 'integer',
            'communication_rating' => 'integer',
            'seller_replied_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * Get the order item
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the seller
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the buyer
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Get the moderator
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Scope for approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for pending reviews
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for reviews by seller
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope for reviews by buyer
     */
    public function scopeForBuyer($query, int $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    /**
     * Scope for reviews of product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Get average rating (overall)
     */
    public function getAverageRatingAttribute(): float
    {
        $ratings = array_filter([
            $this->rating,
            $this->delivery_rating,
            $this->quality_rating,
            $this->communication_rating,
        ]);

        if (empty($ratings)) {
            return (float) $this->rating;
        }

        return round(array_sum($ratings) / count($ratings), 1);
    }

    /**
     * Add seller reply
     */
    public function addReply(string $reply): bool
    {
        return $this->update([
            'seller_reply' => $reply,
            'seller_replied_at' => now(),
        ]);
    }

    /**
     * Approve review
     */
    public function approve(int $moderatorId): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject review
     */
    public function reject(int $moderatorId, string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Get seller average ratings from their reviews
     */
    public static function getSellerRatings(int $sellerId): array
    {
        $reviews = static::forSeller($sellerId)->approved()->get();

        if ($reviews->isEmpty()) {
            return [
                'overall' => 0,
                'delivery' => 0,
                'quality' => 0,
                'communication' => 0,
                'count' => 0,
            ];
        }

        return [
            'overall' => round($reviews->avg('rating'), 1),
            'delivery' => round($reviews->whereNotNull('delivery_rating')->avg('delivery_rating'), 1),
            'quality' => round($reviews->whereNotNull('quality_rating')->avg('quality_rating'), 1),
            'communication' => round($reviews->whereNotNull('communication_rating')->avg('communication_rating'), 1),
            'count' => $reviews->count(),
        ];
    }
}
