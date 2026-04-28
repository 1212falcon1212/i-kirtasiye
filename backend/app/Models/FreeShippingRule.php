<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreeShippingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'min_order_amount',
        'max_desi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_order_amount' => 'decimal:2',
            'max_desi' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProvider($query, ?string $provider)
    {
        if ($provider) {
            return $query->where(function ($q) use ($provider) {
                $q->where('provider', $provider)->orWhereNull('provider');
            });
        }
        return $query->whereNull('provider');
    }

    /**
     * Ücretsiz kargo uygunluğunu kontrol et
     */
    public static function isEligible(float $orderAmount, float $totalDesi, ?string $provider = null): bool
    {
        $rule = static::active()
            ->where(function ($query) use ($provider) {
                $query->whereNull('provider');
                if ($provider) {
                    $query->orWhere('provider', $provider);
                }
            })
            ->where('min_order_amount', '<=', $orderAmount)
            ->where(function ($query) use ($totalDesi) {
                $query->whereNull('max_desi')
                    ->orWhere('max_desi', '>=', $totalDesi);
            })
            ->first();

        return $rule !== null;
    }

    /**
     * Ücretsiz kargo için kalan tutarı hesapla
     */
    public static function getRemainingForFree(float $orderAmount, float $totalDesi, ?string $provider = null): ?float
    {
        $rule = static::active()
            ->where(function ($query) use ($provider) {
                $query->whereNull('provider');
                if ($provider) {
                    $query->orWhere('provider', $provider);
                }
            })
            ->where(function ($query) use ($totalDesi) {
                $query->whereNull('max_desi')
                    ->orWhere('max_desi', '>=', $totalDesi);
            })
            ->orderBy('min_order_amount', 'asc')
            ->first();

        if (!$rule) {
            return null;
        }

        $remaining = $rule->min_order_amount - $orderAmount;
        return $remaining > 0 ? $remaining : 0;
    }
}
