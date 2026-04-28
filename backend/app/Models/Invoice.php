<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'sub_order_id',
        'seller_id',
        'buyer_id',
        'type',
        'status',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'total_amount',
        'commission_rate',
        'commission_amount',
        'seller_info',
        'buyer_info',
        'items',
        'erp_provider',
        'erp_invoice_id',
        'erp_invoice_url',
        'erp_status',
        'erp_error',
        'erp_synced_at',
        'pdf_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'seller_info' => 'array',
            'buyer_info' => 'array',
            'items' => 'array',
            'erp_synced_at' => 'datetime',
        ];
    }

    // Fatura tipi sabitleri
    public const TYPE_SELLER = 'seller';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_TAX = 'tax';
    public const TYPE_SHIPPING = 'shipping';

    // Durum sabitleri
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    // İlişkiler
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeNeedsSyncing($query)
    {
        return $query->where('erp_status', 'pending')
            ->whereNotNull('erp_provider');
    }

    // Helpers
    public function getFormattedTotalAttribute(): string
    {
        return '₺' . number_format((float) $this->total_amount, 2, ',', '.');
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_SELLER => 'Satış Faturası',
            self::TYPE_COMMISSION => 'Komisyon Faturası',
            self::TYPE_TAX => 'Vergi Faturası',
            self::TYPE_SHIPPING => 'Kargo Faturası',
            default => 'Fatura',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Taslak',
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_SENT => 'Gönderildi',
            self::STATUS_PAID => 'Ödendi',
            self::STATUS_CANCELLED => 'İptal Edildi',
            default => 'Bilinmiyor',
        };
    }

    public function markAsSynced(string $erpInvoiceId, ?string $erpInvoiceUrl = null): void
    {
        $data = [
            'erp_invoice_id' => $erpInvoiceId,
            'erp_status' => 'synced',
            'erp_error' => null,
            'erp_synced_at' => now(),
        ];

        if ($erpInvoiceUrl) {
            $data['erp_invoice_url'] = $erpInvoiceUrl;
        }

        $this->update($data);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'erp_status' => 'failed',
            'erp_error' => $error,
        ]);
    }

    /**
     * Fatura numarası oluştur
     */
    public static function generateInvoiceNumber(string $type = 'seller'): string
    {
        $prefix = match ($type) {
            self::TYPE_SELLER => 'SF',
            self::TYPE_COMMISSION => 'KF',
            self::TYPE_TAX => 'VF',
            self::TYPE_SHIPPING => 'KG',
            default => 'FT',
        };

        $year = date('Y');
        $month = date('m');

        // Son fatura numarasını bul
        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }
}
