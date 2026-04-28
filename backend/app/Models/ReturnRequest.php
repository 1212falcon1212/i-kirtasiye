<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'buyer_id',
        'seller_id',
        'type',
        'reason',
        'reason_detail',
        'images',
        'quantity',
        'refund_amount',
        'status',
        'seller_note',
        'admin_note',
        'return_tracking_number',
        'return_shipping_provider',
        'approved_at',
        'rejected_at',
        'shipped_at',
        'received_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'refund_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    // Reason labels
    public const REASON_LABELS = [
        'wrong_product' => 'Yanlış ürün gönderildi',
        'damaged' => 'Hasarlı ürün',
        'not_as_described' => 'Tanıtıldığı gibi değil',
        'quality_issue' => 'Kalite sorunu',
        'expired' => 'Miat sorunu',
        'changed_mind' => 'Fikir değişikliği',
        'other' => 'Diğer',
    ];

    // Status labels
    public const STATUS_LABELS = [
        'pending' => 'Beklemede',
        'approved' => 'Onaylandı',
        'rejected' => 'Reddedildi',
        'shipped' => 'Kargoya Verildi',
        'received' => 'Teslim Alındı',
        'refunded' => 'İade Edildi',
        'cancelled' => 'İptal Edildi',
    ];

    // Type labels
    public const TYPE_LABELS = [
        'return' => 'İade Talebi',
        'cancel' => 'İptal Talebi',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Accessors
    public function getReasonLabelAttribute(): string
    {
        return self::REASON_LABELS[$this->reason] ?? $this->reason;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeForBuyer($query, int $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    // Helpers
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(?string $note = null): void
    {
        $this->update([
            'status' => 'approved',
            'seller_note' => $note,
            'approved_at' => now(),
        ]);
    }

    public function reject(string $note): void
    {
        $this->update([
            'status' => 'rejected',
            'seller_note' => $note,
            'rejected_at' => now(),
        ]);
    }
}
