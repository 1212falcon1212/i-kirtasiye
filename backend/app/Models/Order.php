<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'subtotal',
        'total_commission',
        'total_amount',
        'shipping_cost',
        'coupon_id',
        'coupon_discount',
        'shipping_provider',
        'tracking_number',
        'shipping_status',
        'shipping_label_url',
        'shipped_at',
        'delivered_at',
        'status',
        'payment_status',
        'payment_method',
        'shipping_address',
        'notes',
        'buyer_confirmed_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'coupon_discount' => 'decimal:2',
            'shipping_address' => 'array',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'buyer_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Status labels in Turkish
     */
    public const STATUS_LABELS = [
        'pending' => 'Beklemede',
        'confirmed' => 'Onaylandı',
        'processing' => 'Hazırlanıyor',
        'shipped' => 'Kargoya Verildi',
        'delivered' => 'Teslim Edildi',
        'returned' => 'İade Edildi',
        'cancelled' => 'İptal Edildi',
    ];

    /**
     * Payment status labels in Turkish
     */
    public const PAYMENT_STATUS_LABELS = [
        'pending' => 'Ödeme Bekleniyor',
        'paid' => 'Ödendi',
        'failed' => 'Ödeme Başarısız',
        'refunded' => 'İade Edildi',
        'expired' => 'Süresi Doldu',
    ];

    /**
     * Shipping status labels in Turkish
     */
    public const SHIPPING_STATUS_LABELS = [
        'pending' => 'Kargo Bekleniyor',
        'processing' => 'Hazırlanıyor',
        'shipped' => 'Kargoya Verildi',
        'in_transit' => 'Yolda',
        'out_for_delivery' => 'Dağıtımda',
        'delivered' => 'Teslim Edildi',
        'returned' => 'İade Edildi',
        'failed' => 'Başarısız',
    ];

    /**
     * Get the user that placed this order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all items in this order
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all sub-orders for this order
     */
    public function subOrders(): HasMany
    {
        return $this->hasMany(SubOrder::class);
    }

    /**
     * Get the coupon used for this order
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get reviews for this order's items
     */
    public function reviews(): HasMany
    {
        return $this->hasManyThrough(Review::class, OrderItem::class);
    }

    /**
     * Get invoices for this order
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get status label in Turkish
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Get payment status label in Turkish
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUS_LABELS[$this->payment_status] ?? $this->payment_status;
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
                    'subtotal' => $sellerItems->sum('total_price'),
                    'commission' => $sellerItems->sum('commission_amount'),
                    'payout' => $sellerItems->sum('seller_payout_amount'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Derive overall status from sub_orders
     * Used to sync the parent order's status field from sub_order statuses.
     */
    public function getOverallStatusAttribute(): string
    {
        $subOrders = $this->subOrders;

        if ($subOrders->isEmpty()) {
            return $this->status;
        }

        $statuses = $subOrders->pluck('status');

        $terminalStatuses = ['cancelled', 'returned'];

        // All cancelled
        if ($statuses->every(fn($s) => $s === 'cancelled')) {
            return 'cancelled';
        }

        // All returned (or mix of returned + cancelled)
        if ($statuses->every(fn($s) => in_array($s, $terminalStatuses))) {
            return $statuses->contains('returned') ? 'returned' : 'cancelled';
        }

        // All delivered
        if ($statuses->every(fn($s) => $s === 'delivered')) {
            return 'delivered';
        }

        // All same status (ignoring terminal)
        $activeStatuses = $statuses->reject(fn($s) => in_array($s, $terminalStatuses));
        if ($activeStatuses->unique()->count() === 1) {
            return $activeStatuses->first();
        }

        // Priority order: pending < confirmed < processing < shipped < delivered
        $priority = ['pending' => 0, 'confirmed' => 1, 'processing' => 2, 'shipped' => 3, 'delivered' => 4];

        // Return the lowest (slowest) active sub_order status
        return $activeStatuses->sortBy(fn($s) => $priority[$s] ?? 0)->first();
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing']);
    }

    /**
     * Cancel the order
     */
    public function cancel(): void
    {
        if ($this->canBeCancelled()) {
            $this->update(['status' => 'cancelled']);
        }
    }

    /**
     * Scope for orders by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for user's orders
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if buyer has confirmed delivery
     */
    public function getIsBuyerConfirmedAttribute(): bool
    {
        return $this->buyer_confirmed_at !== null;
    }

    /**
     * Check if buyer can confirm delivery
     */
    public function canBuyerConfirm(): bool
    {
        return $this->status === 'delivered' && $this->buyer_confirmed_at === null;
    }

    /**
     * Scope for orders ready for wallet balance release
     */
    public function scopeReadyForWalletRelease($query, int $holdDays)
    {
        return $query->where('status', 'delivered')
            ->whereNotNull('buyer_confirmed_at')
            ->where('buyer_confirmed_at', '<=', now()->subDays($holdDays))
            ->where('payment_status', 'paid');
    }
}
