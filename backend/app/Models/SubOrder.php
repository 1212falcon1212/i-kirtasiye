<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'status',
        'shipped_at',
        'delivered_at',
        'buyer_confirmed_at',
        'subtotal',
        'total_commission',
        'total_payout',
        'tracking_number',
        'shipping_provider',
        'shipping_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'total_payout' => 'decimal:2',
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
     * Allowed status transitions
     */
    public const STATUS_TRANSITIONS = [
        'pending' => ['confirmed', 'processing', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => ['returned'],
        'returned' => [],
        'cancelled' => [],
    ];

    // ── Relations ──

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }

    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Accessors ──

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getIsBuyerConfirmedAttribute(): bool
    {
        return $this->buyer_confirmed_at !== null;
    }

    // ── Status Transition Methods ──

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing']);
    }

    public function canBuyerConfirm(): bool
    {
        return $this->status === 'delivered' && $this->buyer_confirmed_at === null;
    }

    /**
     * Scope for sub_orders ready for wallet balance release
     */
    public function scopeReadyForWalletRelease($query, int $holdDays)
    {
        return $query->where('status', 'delivered')
            ->whereNotNull('buyer_confirmed_at')
            ->where('buyer_confirmed_at', '<=', now()->subDays($holdDays))
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'));
    }
}
