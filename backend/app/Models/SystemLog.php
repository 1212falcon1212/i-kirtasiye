<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'provider',
        'action',
        'order_id',
        'user_id',
        'request',
        'response',
        'response_code',
        'status',
        'error',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'request' => 'array',
            'response' => 'array',
        ];
    }

    /**
     * Type labels in Turkish
     */
    public const TYPE_LABELS = [
        'invoice' => 'Fatura',
        'shipping' => 'Kargo',
        'payment' => 'Ödeme',
        'auth' => 'Kimlik Doğrulama',
    ];

    /**
     * Status labels in Turkish
     */
    public const STATUS_LABELS = [
        'success' => 'Başarılı',
        'failed' => 'Başarısız',
        'pending' => 'Beklemede',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Scope for failed logs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by provider
     */
    public function scopeOfProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Log a request
     */
    public static function logRequest(
        string $type,
        string $action,
        array $request = [],
        ?string $provider = null,
        ?int $orderId = null,
        ?int $userId = null
    ): self {
        return self::create([
            'type' => $type,
            'provider' => $provider,
            'action' => $action,
            'order_id' => $orderId,
            'user_id' => $userId,
            'request' => $request,
            'status' => 'pending',
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Update with response
     */
    public function logResponse(array $response, int $code, bool $success = true, ?string $error = null): self
    {
        $this->update([
            'response' => $response,
            'response_code' => $code,
            'status' => $success ? 'success' : 'failed',
            'error' => $error,
        ]);

        return $this;
    }
}
