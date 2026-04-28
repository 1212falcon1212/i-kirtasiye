<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'min_desi',
        'max_desi',
        'price',
        'region',
        'region_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_desi' => 'decimal:2',
            'max_desi' => 'decimal:2',
            'price' => 'decimal:2',
            'region_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Provider labels
    public const PROVIDER_LABELS = [
        'aras' => 'Aras Kargo',
        'yurtici' => 'Yurtiçi Kargo',
        'mng' => 'MNG Kargo',
        'sendeo' => 'Sendeo',
        'hepsijet' => 'Hepsijet',
        'ptt' => 'PTT Kargo',
        'surat' => 'Sürat Kargo',
        'kolaygelsin' => 'Kolaygelsin',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeForDesi($query, float $desi)
    {
        return $query->where('min_desi', '<=', $desi)
            ->where('max_desi', '>=', $desi);
    }

    // Helpers
    public function getProviderLabel(): string
    {
        return self::PROVIDER_LABELS[$this->provider] ?? $this->provider;
    }

    /**
     * Desi için fiyat hesapla
     */
    public static function getPriceForDesi(string $provider, float $desi, ?string $region = null): ?float
    {
        $rate = static::active()
            ->forProvider($provider)
            ->forDesi($desi)
            ->first();

        if (!$rate) {
            return null;
        }

        // İl bazlı fark varsa uygula
        if ($region && $rate->region === $region && $rate->region_price) {
            return (float) $rate->region_price;
        }

        return (float) $rate->price;
    }

    /**
     * Aktif tüm kargo firmalarını getir
     */
    public static function getActiveProviders(): array
    {
        return static::active()
            ->select('provider')
            ->distinct()
            ->pluck('provider')
            ->map(fn($p) => [
                'code' => $p,
                'name' => self::PROVIDER_LABELS[$p] ?? $p,
            ])
            ->toArray();
    }

    /**
     * Desi için tüm kargo seçeneklerini getir
     */
    public static function getOptionsForDesi(float $desi): array
    {
        $providers = static::getActiveProviders();
        $options = [];

        foreach ($providers as $provider) {
            $price = static::getPriceForDesi($provider['code'], $desi);
            if ($price !== null) {
                $options[] = [
                    'provider' => $provider['code'],
                    'name' => $provider['name'],
                    'price' => $price,
                    'formatted_price' => '₺' . number_format($price, 2, ',', '.'),
                ];
            }
        }

        // Fiyata göre sırala
        usort($options, fn($a, $b) => $a['price'] <=> $b['price']);

        return $options;
    }
}
