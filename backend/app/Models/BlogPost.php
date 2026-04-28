<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'category_id',
        'author_id',
        'tags',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
        'view_count',
        'read_time_minutes',
        'is_featured',
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    protected $appends = ['featured_image_url'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
            $post->read_time_minutes = self::calculateReadTime($post->content);
            // Auto-set published_at when status is published
            if ($post->status === 'published' && empty($post->published_at)) {
                $post->published_at = now();
            }
        });

        static::updating(function (self $post) {
            if ($post->isDirty('content')) {
                $post->read_time_minutes = self::calculateReadTime($post->content);
            }
            // Auto-set published_at when status changes to published
            if ($post->isDirty('status') && $post->status === 'published' && empty($post->published_at)) {
                $post->published_at = now();
            }
        });
    }

    private static function calculateReadTime(?string $content): int
    {
        if (empty($content)) {
            return 1;
        }
        $wordCount = str_word_count(strip_tags($content));
        return max(1, (int) ceil($wordCount / 200));
    }

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Accessors
    /**
     * Content with absolute URLs for images/media (needed for frontend on different domain).
     */
    public function getContentAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return $value;
        }

        $baseUrl = rtrim(config('app.url'), '/');

        // Convert relative src="/storage/..." to absolute URLs
        return preg_replace(
            '/(<(?:img|source|video|audio)[^>]+(?:src|poster))=(["\'])\/storage\//i',
            '$1=$2' . $baseUrl . '/storage/',
            $value
        );
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (empty($this->featured_image)) {
            return null;
        }
        if (str_starts_with($this->featured_image, 'http')) {
            return $this->featured_image;
        }
        return asset('storage/' . $this->featured_image);
    }
}
