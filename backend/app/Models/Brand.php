<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Marka modeli - Ürün markalarını temsil eder
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $logo_url
 * @property string|null $description
 * @property string|null $website_url
 * @property bool $is_active
 * @property bool $is_featured
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Brand extends Model
{
    use HasFactory;

    /**
     * Toplu atamaya izin verilen alanlar
     */
    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'description',
        'website_url',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    /**
     * Tip dönüşümleri
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Model boot - slug otomatik oluşturma
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Brand $brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });

        static::updating(function (Brand $brand) {
            if ($brand->isDirty('name') && !$brand->isDirty('slug')) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    /**
     * Bu markaya ait ürünler
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand', 'name');
    }

    /**
     * Sadece aktif markaları getirir
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Sadece öne çıkan markaları getirir
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Sıralı getirir
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Logo URL'ini döndürür (tam URL veya storage)
     */
    public function getLogoFullUrlAttribute(): ?string
    {
        if (empty($this->logo_url)) {
            return null;
        }

        if (str_starts_with($this->logo_url, 'http')) {
            return $this->logo_url;
        }

        // /storage/brands/x.png → asset ile tam URL yap
        if (str_starts_with($this->logo_url, '/storage/')) {
            return asset(ltrim($this->logo_url, '/'));
        }

        return asset('storage/' . $this->logo_url);
    }
}
