<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Get all active categories with product counts (including children)
     */
    public function index(): JsonResponse
    {
        return Cache::remember('categories.index', self::CACHE_TTL, function () {
            $categories = Category::active()
                ->with('children')
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'full_slug' => $category->full_slug ?? $category->slug,
                        'description' => $category->description,
                        'products_count' => $category->total_products_count,
                        'children' => $category->children->map(function ($child) {
                            return [
                                'id' => $child->id,
                                'name' => $child->name,
                                'slug' => $child->slug,
                                'full_slug' => $child->full_slug ?? $child->slug,
                                'products_count' => $child->products()->active()->count(),
                            ];
                        }),
                    ];
                });

            return response()->json([
                'categories' => $categories,
            ]);
        });
    }

    /**
     * Get single category with products (including products from child categories)
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $category = Category::with('children')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Kategori bulunamadı.'], 404);
        }

        $perPage = $request->input('per_page', 24);
        $page = $request->input('page', 1);

        $cacheKey = "categories.show.{$id}.{$perPage}.{$page}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category, $perPage) {
            $categoryIds = $category->getDescendantIds();

            $products = Product::whereIn('category_id', $categoryIds)
                ->active()
                ->with(['category:id,name,slug'])
                ->withCount(['activeOffers as offers_count'])
                ->withMin('activeOffers as lowest_price', 'price')
                ->orderBy('name')
                ->paginate($perPage);

            return response()->json([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id,
                    'children' => $category->children->map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                    ]),
                ],
                'products' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ]);
        });
    }

    /**
     * Get category by slug (supports both simple slug and full_slug with hierarchy)
     */
    public function showBySlug(string $slug, Request $request): JsonResponse
    {
        // First try to find by full_slug (hierarchical path like "gunes-urunleri/anti-age")
        $category = Category::where('full_slug', $slug)->with(['children', 'parent'])->first();

        // If not found, try simple slug (backwards compatibility)
        if (!$category) {
            $category = Category::where('slug', $slug)->with(['children', 'parent'])->first();
        }

        if (!$category) {
            return response()->json(['message' => 'Kategori bulunamadı.'], 404);
        }

        $perPage = $request->input('per_page', 24);
        $page = $request->input('page', 1);

        $cacheKey = "categories.slug.{$slug}.{$perPage}.{$page}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category, $perPage) {
            $categoryIds = $category->getDescendantIds();

            $products = Product::whereIn('category_id', $categoryIds)
                ->active()
                ->with(['category:id,name,slug,full_slug'])
                ->withCount(['activeOffers as offers_count'])
                ->withMin('activeOffers as lowest_price', 'price')
                ->orderBy('name')
                ->paginate($perPage);

            $breadcrumb = $this->buildBreadcrumb($category);

            return response()->json([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'full_slug' => $category->full_slug,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id,
                    'parent' => $category->parent ? [
                        'id' => $category->parent->id,
                        'name' => $category->parent->name,
                        'slug' => $category->parent->slug,
                        'full_slug' => $category->parent->full_slug,
                    ] : null,
                    'children' => $category->children->map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'full_slug' => $c->full_slug,
                    ]),
                ],
                'breadcrumb' => $breadcrumb,
                'products' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ]);
        });
    }

    /**
     * Build breadcrumb from category ancestors
     */
    private function buildBreadcrumb(Category $category): array
    {
        $breadcrumb = [];
        $current = $category;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
                'full_slug' => $current->full_slug,
            ]);
            $current = $current->parent;
        }

        return $breadcrumb;
    }
}
