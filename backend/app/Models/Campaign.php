<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Campaign type constants
     */
    public const TYPE_PRODUCT_DISCOUNT = 'product_discount';
    public const TYPE_STORE_DISCOUNT = 'store_discount';
    public const TYPE_BRAND_DISCOUNT = 'brand_discount';
    public const TYPE_GIFT_PRODUCT = 'gift_product';

    public const TYPE_LABELS = [
        self::TYPE_PRODUCT_DISCOUNT => 'Ürüne İndirim',
        self::TYPE_STORE_DISCOUNT => 'Mağazaya İndirim',
        self::TYPE_BRAND_DISCOUNT => 'Markaya İndirim',
        self::TYPE_GIFT_PRODUCT => 'Hediye Ürün',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Onay Bekliyor',
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_INACTIVE => 'Pasif',
        self::STATUS_REJECTED => 'Reddedildi',
        self::STATUS_EXPIRED => 'Süresi Doldu',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'seller_id',
        'name',
        'description',
        'type',
        'discount_rate',
        'min_purchase_amount',
        'min_quantity',
        'product_id',
        'brand',
        'gift_product_id',
        'gift_quantity',
        'starts_at',
        'ends_at',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'discount_rate' => 'decimal:2',
            'min_purchase_amount' => 'decimal:2',
            'min_quantity' => 'integer',
            'gift_quantity' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the seller for this campaign
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the product for product-specific campaigns
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the gift product for gift campaigns
     */
    public function giftProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }

    /**
     * Get the admin who reviewed this campaign
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get coupons associated with this campaign
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Scope for active campaigns
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    /**
     * Scope for pending campaigns
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for campaigns by seller
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Check if campaign is currently active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->starts_at <= now()
            && $this->ends_at >= now();
    }

    /**
     * Check if campaign has expired
     */
    public function isExpired(): bool
    {
        return $this->ends_at < now();
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Calculate discount for a given amount
     */
    public function calculateDiscount(float $amount): float
    {
        if (!$this->discount_rate) {
            return 0;
        }

        return $amount * ($this->discount_rate / 100);
    }

    /**
     * Check if campaign can be applied to a purchase
     */
    public function canApply(float $amount, int $quantity = 1): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->min_purchase_amount && $amount < $this->min_purchase_amount) {
            return false;
        }

        if ($this->min_quantity && $quantity < $this->min_quantity) {
            return false;
        }

        return true;
    }
}
