<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'icon',
        'parent_id',
        'location',
        'sort_order',
        'is_active',
        'open_in_new_tab',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_in_new_tab' => 'boolean',
    ];

    // Relations
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(NavigationMenu::class, 'parent_id')->ordered();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    public function scopeParentOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // Get menu tree for a location
    public static function getMenuTree(string $location): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->location($location)
            ->parentOnly()
            ->ordered()
            ->with('children')
            ->get();
    }

    // Location options for forms
    public static function locationOptions(): array
    {
        return [
            'header' => 'Header Menü',
            'footer' => 'Footer Menü',
            'categories_dropdown' => 'Kategoriler Dropdown',
            'mobile_menu' => 'Mobil Menü',
        ];
    }
}
