<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Marka işlemlerini yöneten servis sınıfı
 */
class BrandService
{
    /**
     * Cache süresi (dakika)
     */
    private const CACHE_TTL = 60;

    /**
     * Tüm aktif markaları getirir
     */
    public function getActiveBrands(): Collection
    {
        return Cache::remember('brands.active', self::CACHE_TTL * 60, function () {
            return Brand::active()
                ->ordered()
                ->get();
        });
    }

    /**
     * Öne çıkan markaları getirir (ana sayfa için)
     */
    public function getFeaturedBrands(int $limit = 12): Collection
    {
        return Cache::remember("brands.featured.{$limit}", self::CACHE_TTL * 60, function () use ($limit) {
            return Brand::active()
                ->featured()
                ->ordered()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Marka detayını slug ile getirir
     */
    public function getBySlug(string $slug): ?Brand
    {
        return Brand::active()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Marka detayını ID ile getirir
     */
    public function getById(int $id): ?Brand
    {
        return Brand::active()->find($id);
    }

    /**
     * Markaları API response formatına dönüştürür
     */
    public function formatForApi(Collection $brands): array
    {
        return $brands->map(fn(Brand $brand) => [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'logo_url' => $brand->logo_full_url,
        ])->toArray();
    }

    /**
     * Detaylı marka bilgisi formatı
     */
    public function formatDetailForApi(Brand $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'logo_url' => $brand->logo_full_url,
            'description' => $brand->description,
            'website_url' => $brand->website_url,
        ];
    }

    /**
     * Cache'i temizler
     */
    public function clearCache(): void
    {
        Cache::forget('brands.active');

        // Featured cache'lerini de temizle (yaygın limit değerleri)
        foreach ([6, 8, 10, 12, 15, 20, 24, 30, 50] as $limit) {
            Cache::forget("brands.featured.{$limit}");
        }
    }
}
