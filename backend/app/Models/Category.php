<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'full_slug',
        'parent_id',
        'description',
        'commission_rate',
        'vat_rate',
        'withholding_tax_rate',
        'is_active',
        'show_on_homepage',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'withholding_tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'show_on_homepage' => 'boolean',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
            $category->full_slug = $category->generateFullSlug();
        });

        static::updating(function ($category) {
            if ($category->isDirty('slug') || $category->isDirty('parent_id')) {
                $category->full_slug = $category->generateFullSlug();
            }
        });

        static::saved(function ($category) {
            // Update children's full_slug when parent changes
            if ($category->wasChanged('full_slug')) {
                foreach ($category->children as $child) {
                    $child->full_slug = $child->generateFullSlug();
                    $child->saveQuietly();
                }
            }

            // Clear homepage cache when show_on_homepage changes
            if ($category->wasChanged('show_on_homepage')) {
                \Illuminate\Support\Facades\Cache::forget('cms.homepage.category_sections');
            }
        });
    }

    /**
     * Generate full slug including parent hierarchy
     */
    public function generateFullSlug(): string
    {
        if ($this->parent_id && $this->parent) {
            return $this->parent->full_slug.'/'.$this->slug;
        }

        return $this->slug;
    }

    /**
     * Get all products in this category
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope for active categories only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get commission rate as percentage (0-100)
     */
    public function getCommissionPercentageAttribute(): float
    {
        return (float) $this->commission_rate;
    }

    /**
     * Get commission rate as decimal (0-1)
     */
    public function getCommissionDecimalAttribute(): float
    {
        return (float) $this->commission_rate / 100;
    }

    /**
     * Get VAT rate as decimal (0-1)
     */
    public function getVatDecimalAttribute(): float
    {
        return (float) ($this->vat_rate ?? 20) / 100;
    }

    /**
     * Get withholding tax rate as decimal (0-1)
     */
    public function getWithholdingDecimalAttribute(): float
    {
        return (float) ($this->withholding_tax_rate ?? 0) / 100;
    }

    /**
     * Get the parent category
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all descendant category IDs (children, grandchildren, etc.)
     */
    public function getDescendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getDescendantIds());
        }

        return $ids;
    }

    /**
     * Get all products including those in child categories
     */
    public function getAllProducts()
    {
        $categoryIds = $this->getDescendantIds();

        return Product::whereIn('category_id', $categoryIds)->active();
    }

    /**
     * Get total product count including child categories
     */
    public function getTotalProductsCountAttribute(): int
    {
        $categoryIds = $this->getDescendantIds();

        return Product::whereIn('category_id', $categoryIds)->active()->count();
    }
}
