<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'direction',
        'balance_type',
        'description',
        'order_id',
        'sub_order_id',
        'order_item_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    const TYPE_SALE = 'sale';
    const TYPE_COMMISSION = 'commission';
    const TYPE_SHIPPING = 'shipping';
    const TYPE_VAT = 'vat';
    const TYPE_WITHHOLDING = 'withholding';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_REFUND = 'refund';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_RELEASE = 'release';

    const DIRECTION_CREDIT = 'credit';
    const DIRECTION_DEBIT = 'debit';

    const BALANCE_PENDING = 'pending';
    const BALANCE_AVAILABLE = 'available';

    /**
     * Type labels in Turkish
     */
    public static array $typeLabels = [
        'sale' => 'Satış Geliri',
        'commission' => 'Hizmet Bedeli',
        'shipping' => 'Kargo Masrafı',
        'vat' => 'KDV',
        'withholding' => 'Stopaj',
        'withdrawal' => 'Para Çekme',
        'refund' => 'İade',
        'adjustment' => 'Düzeltme',
        'release' => 'Bakiye Serbest Bırakma',
    ];

    /**
     * Get the wallet
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(SellerWallet::class, 'wallet_id');
    }

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the sub-order
     */
    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::$typeLabels[$this->type] ?? $this->type;
    }

    /**
     * Get signed amount (positive for credit, negative for debit)
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->direction === self::DIRECTION_CREDIT ? $this->amount : -$this->amount;
    }

    /**
     * Scope for specific wallet
     */
    public function scopeForWallet($query, int $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }

    /**
     * Scope for specific order
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }
}
