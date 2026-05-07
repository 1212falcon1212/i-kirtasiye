<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Page extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Page $page) {
            self::invalidatePageCaches($page);
        });

        static::deleted(function (Page $page) {
            self::invalidatePageCaches($page);
        });
    }

    /**
     * Bir Page kaydı değiştiğinde ilgili tüm cache anahtarlarını temizler.
     * Tek sayfa cache'i + slug prefix bazlı grup cache'leri.
     */
    private static function invalidatePageCaches(Page $page): void
    {
        Cache::forget("cms.page.{$page->slug}");
        Cache::forget("legal.page.{$page->slug}");

        // Grup cache invalidation: slug "{group}-..." veya slug == group ise grup cache'i temizlenir.
        // Ornek: "yardim", "yardim-baslarken" -> grup "yardim".
        $group = $page->slug;
        if (str_contains($page->slug, '-')) {
            $group = explode('-', $page->slug, 2)[0];
        }
        Cache::forget("cms.pages.group.{$group}");
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
        'status',
        'template',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Sadece yayindaki sayfalari getirir
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Siralamaya gore getirir
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
