<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'seller_id',
        'bank_account_id',
        'amount',
        'status',
        'notes',
        'admin_notes',
        'processed_by',
        'processed_at',
        'transaction_reference',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Status labels in Turkish
     */
    public static array $statusLabels = [
        'pending' => 'Beklemede',
        'approved' => 'Onaylandı',
        'rejected' => 'Reddedildi',
        'processing' => 'İşleniyor',
        'completed' => 'Tamamlandı',
        'failed' => 'Başarısız',
    ];

    /**
     * Status colors for badges
     */
    public static array $statusColors = [
        'pending' => 'warning',
        'approved' => 'info',
        'rejected' => 'danger',
        'processing' => 'primary',
        'completed' => 'success',
        'failed' => 'danger',
    ];

    /**
     * Get the seller
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the bank account
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(SellerBankAccount::class, 'bank_account_id');
    }

    /**
     * Get the admin who processed this request
     */
    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::$statusLabels[$this->status] ?? $this->status;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return self::$statusColors[$this->status] ?? 'gray';
    }

    /**
     * Check if can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if can be completed
     */
    public function canBeCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PROCESSING]);
    }

    /**
     * Approve the request
     */
    public function approve(User $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Reject the request
     */
    public function reject(User $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Complete the request
     */
    public function complete(?string $transactionReference = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'transaction_reference' => $transactionReference,
        ]);
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for seller's requests
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }
}
