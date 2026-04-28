<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Discount type constants
     */
    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    public const DISCOUNT_TYPE_FIXED = 'fixed';
    public const DISCOUNT_TYPE_FREE_SHIPPING = 'free_shipping';

    public const DISCOUNT_TYPE_LABELS = [
        self::DISCOUNT_TYPE_PERCENTAGE => 'Yüzde İndirim',
        self::DISCOUNT_TYPE_FIXED => 'Sabit İndirim',
        self::DISCOUNT_TYPE_FREE_SHIPPING => 'Kargo Tutarı Kadar İndirim',
    ];

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_INACTIVE => 'Pasif',
        self::STATUS_EXPIRED => 'Süresi Doldu',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'seller_id',
        'campaign_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_purchase_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'starts_at',
        'ends_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_purchase_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_limit_per_user' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Get the seller for this coupon
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the campaign for this coupon
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get usages of this coupon
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Scope for active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * Scope for coupons by seller
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Find coupon by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper($code))->first();
    }

    /**
     * Check if coupon can be used by a user
     */
    public function canBeUsedBy(User $user, float $purchaseAmount, ?int $sellerId = null): array
    {
        // Check if coupon is active
        if ($this->status !== self::STATUS_ACTIVE) {
            return ['valid' => false, 'message' => 'Bu kupon aktif değil.'];
        }

        // Check date range
        if ($this->starts_at && $this->starts_at > now()) {
            return ['valid' => false, 'message' => 'Bu kupon henüz geçerli değil.'];
        }

        if ($this->ends_at && $this->ends_at < now()) {
            return ['valid' => false, 'message' => 'Bu kuponun süresi dolmuş.'];
        }

        // Check total usage limit
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return ['valid' => false, 'message' => 'Bu kuponun kullanım limiti dolmuş.'];
        }

        // Check per-user usage limit
        if ($this->usage_limit_per_user) {
            $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $this->usage_limit_per_user) {
                return ['valid' => false, 'message' => 'Bu kuponu maksimum kullanım sayısına ulaştınız.'];
            }
        }

        // Check minimum purchase amount
        if ($this->min_purchase_amount && $purchaseAmount < $this->min_purchase_amount) {
            $formatted = number_format($this->min_purchase_amount, 2, ',', '.');
            return ['valid' => false, 'message' => "Bu kupon için minimum sepet tutarı ₺{$formatted} olmalıdır."];
        }

        // Check if coupon belongs to specific seller (if provided)
        if ($sellerId && $this->seller_id !== $sellerId) {
            return ['valid' => false, 'message' => 'Bu kupon bu satıcı için geçerli değil.'];
        }

        return ['valid' => true, 'message' => 'Kupon geçerli.'];
    }

    /**
     * Calculate discount amount for a given purchase amount
     */
    public function calculateDiscount(float $purchaseAmount, float $shippingAmount = 0): float
    {
        if ($this->discount_type === self::DISCOUNT_TYPE_FREE_SHIPPING) {
            return round($shippingAmount, 2);
        }

        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE) {
            $discount = $purchaseAmount * ($this->discount_value / 100);

            // Apply max discount cap if set
            if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
                $discount = $this->max_discount_amount;
            }

            return round($discount, 2);
        }

        // Fixed discount
        return min($this->discount_value, $purchaseAmount);
    }

    /**
     * Record coupon usage
     */
    public function recordUsage(User $user, Order $order, float $discountAmount): CouponUsage
    {
        $usage = $this->usages()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'discount_amount' => $discountAmount,
        ]);

        $this->increment('used_count');

        return $usage;
    }

    /**
     * Check if coupon is currently valid (active and within date range)
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->starts_at && $this->starts_at > now()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at < now()) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Get discount type label
     */
    public function getDiscountTypeLabelAttribute(): string
    {
        return self::DISCOUNT_TYPE_LABELS[$this->discount_type] ?? $this->discount_type;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Get formatted discount value (with % or TL)
     */
    public function getFormattedDiscountAttribute(): string
    {
        if ($this->discount_type === self::DISCOUNT_TYPE_FREE_SHIPPING) {
            return 'Ücretsiz Kargo';
        }

        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE) {
            return '%' . number_format($this->discount_value, 0);
        }

        return '₺' . number_format($this->discount_value, 2, ',', '.');
    }
}
