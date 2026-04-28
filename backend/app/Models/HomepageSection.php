<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'type',
        'settings',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // Type options for forms
    public static function typeOptions(): array
    {
        return [
            'product_carousel' => 'Ürün Karuseli',
            'category_grid' => 'Kategori Grid',
            'banner_full_width' => 'Tam Genişlik Banner',
            'featured_products' => 'Öne Çıkan Ürünler',
            'new_arrivals' => 'Yeni Gelenler',
            'best_sellers' => 'Çok Satanlar',
            'deals' => 'Fırsat Ürünleri',
            'custom_html' => 'Özel HTML',
        ];
    }

    // Get setting value with default
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
}
