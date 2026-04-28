<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlogController extends Controller
{
    private const CACHE_TTL = 30; // minutes

    /**
     * Blog yazilari listesi (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 12), 50);
        $categorySlug = $request->get('category');

        $query = BlogPost::with('category:id,name,slug')
            ->published()
            ->orderByDesc('published_at');

        if ($categorySlug) {
            $query->whereHas('category', fn($q) => $q->where('slug', $categorySlug));
        }

        $posts = $query->paginate($perPage);

        $categories = Cache::remember('blog.categories.with_counts', self::CACHE_TTL * 60, function () {
            return BlogCategory::active()
                ->ordered()
                ->withCount(['posts' => fn($q) => $q->published()])
                ->get()
                ->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'posts_count' => $cat->posts_count,
                ])
                ->toArray();
        });

        return response()->json([
            'posts' => $posts->getCollection()->map(fn(BlogPost $post) => $this->formatPost($post)),
            'categories' => $categories,
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Tek blog yazisi detayi
     */
    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::with(['category:id,name,slug', 'author:id,business_name,nickname'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count
        $post->increment('view_count');

        // Related posts (same category, exclude current)
        $relatedPosts = [];
        if ($post->category_id) {
            $relatedPosts = BlogPost::with('category:id,name,slug')
                ->published()
                ->where('category_id', $post->category_id)
                ->where('id', '!=', $post->id)
                ->orderByDesc('published_at')
                ->limit(3)
                ->get()
                ->map(fn(BlogPost $p) => $this->formatPost($p))
                ->toArray();
        }

        return response()->json([
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'featured_image_url' => $post->featured_image_url,
                'category' => $post->category ? [
                    'id' => $post->category->id,
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ] : null,
                'author' => $post->author ? [
                    'name' => $post->author->nickname ?? $post->author->business_name,
                ] : null,
                'tags' => $post->tags ?? [],
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'published_at' => $post->published_at?->toISOString(),
                'read_time_minutes' => $post->read_time_minutes,
                'view_count' => $post->view_count,
            ],
            'related_posts' => $relatedPosts,
        ]);
    }

    /**
     * Aktif blog kategorileri
     */
    public function categories(): JsonResponse
    {
        $categories = Cache::remember('blog.categories.active', self::CACHE_TTL * 60, function () {
            return BlogCategory::active()
                ->ordered()
                ->withCount(['posts' => fn($q) => $q->published()])
                ->get()
                ->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'description' => $cat->description,
                    'posts_count' => $cat->posts_count,
                ])
                ->toArray();
        });

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Random blog posts (for widgets)
     */
    public function random(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 3), 6);

        $posts = BlogPost::with('category:id,name,slug')
            ->published()
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(fn(BlogPost $p) => $this->formatPost($p))
            ->toArray();

        return response()->json(['posts' => $posts]);
    }

    private function formatPost(BlogPost $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'featured_image_url' => $post->featured_image_url,
            'category' => $post->category ? [
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ] : null,
            'tags' => $post->tags ?? [],
            'published_at' => $post->published_at?->toISOString(),
            'read_time_minutes' => $post->read_time_minutes,
            'view_count' => $post->view_count,
            'is_featured' => $post->is_featured,
        ];
    }
}
