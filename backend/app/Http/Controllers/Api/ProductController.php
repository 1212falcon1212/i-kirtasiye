<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Get all active products with pagination
     * Supports filtering by category (including subcategories), brand, price range
     * Supports sorting by offers_count, price, name, newest
     */
    private const CACHE_TTL = 300; // 5 minutes

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $categorySlug = $request->input('category');
        $brand = $request->input('brand');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $sortBy = $request->input('sort_by', 'offers_count');
        $search = $request->input('search');
        $page = $request->input('page', 1);

        $cacheKey = 'products.index.'.md5(serialize([
            $perPage, $categorySlug, $brand, $minPrice, $maxPrice, $sortBy, $search, $page,
        ]));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $perPage, $categorySlug, $brand, $minPrice, $maxPrice, $sortBy, $search
        ) {
            return $this->buildIndexResponse($perPage, $categorySlug, $brand, $minPrice, $maxPrice, $sortBy, $search);
        });
    }

    private function buildIndexResponse(
        int $perPage,
        ?string $categorySlug,
        ?string $brand,
        ?string $minPrice,
        ?string $maxPrice,
        string $sortBy,
        ?string $search = null
    ): JsonResponse {
        $query = Product::active()
            ->with('category:id,name,slug')
            ->withCount(['activeOffers as offers_count'])
            ->withMin('activeOffers as lowest_price', 'price');

        // Search filter
        if ($search && mb_strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('barcode', 'LIKE', "%{$search}%")
                    ->orWhere('brand', 'LIKE', "%{$search}%");
            });
        }

        // Category filter - includes subcategories
        $category = null;
        if ($categorySlug) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                $categoryIds = $category->getDescendantIds();
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // Brand filter
        if ($brand) {
            $query->where('brand', $brand);
        }

        // Price filter (based on active offers)
        if ($minPrice || $maxPrice) {
            $query->whereHas('activeOffers', function ($q) use ($minPrice, $maxPrice) {
                if ($minPrice) {
                    $q->where('price', '>=', $minPrice);
                }
                if ($maxPrice) {
                    $q->where('price', '<=', $maxPrice);
                }
            });
        }

        // Sorting - products with offers always first
        switch ($sortBy) {
            case 'price_asc':
                $query->orderByRaw('CASE WHEN offers_count > 0 THEN 0 ELSE 1 END')
                    ->orderBy('lowest_price', 'asc');
                break;
            case 'price_desc':
                $query->orderByRaw('CASE WHEN offers_count > 0 THEN 0 ELSE 1 END')
                    ->orderBy('lowest_price', 'desc');
                break;
            case 'name':
                $query->orderByRaw('CASE WHEN offers_count > 0 THEN 0 ELSE 1 END')
                    ->orderBy('name', 'asc');
                break;
            case 'newest':
                $query->orderByRaw('CASE WHEN offers_count > 0 THEN 0 ELSE 1 END')
                    ->orderByDesc('created_at');
                break;
            case 'random':
                $query->orderByRaw('CASE WHEN offers_count > 0 THEN 0 ELSE 1 END')
                    ->inRandomOrder();
                break;
            default: // offers_count
                $query->orderByDesc('offers_count')
                    ->orderBy('name');
        }

        $products = $query->paginate($perPage);

        // Get available brands for filter dropdown
        $brandsQuery = Product::active()->whereNotNull('brand')->where('brand', '!=', '');
        if ($category) {
            $brandsQuery->whereIn('category_id', $category->getDescendantIds());
        }
        $availableBrands = $brandsQuery->distinct()->pluck('brand')->sort()->values();

        // Get subcategories if viewing a parent category
        $subcategories = [];
        if ($category) {
            $subcategories = $category->children()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']);
        }

        return response()->json([
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'filters' => [
                'brands' => $availableBrands,
                'subcategories' => $subcategories,
                'category' => $category ? [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parent_id' => $category->parent_id,
                ] : null,
            ],
        ]);
    }

    /**
     * Get single product details
     */
    public function show(Product $product): JsonResponse
    {
        $cacheKey = "products.show.{$product->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($product) {
            $product->loadCount(['activeOffers as offers_count']);
            $product->loadMin('activeOffers as lowest_price', 'price');
            $product->loadMax('activeOffers as highest_price', 'price');

            return response()->json([
                'product' => $product,
            ]);
        });
    }

    /**
     * Get all offers for a product (Cimri model - price comparison)
     */
    public function offers(Product $product, Request $request): JsonResponse
    {
        $sortBy = $request->input('sort_by', 'price');
        $sortOrder = $request->input('sort_order', 'asc');

        $cacheKey = "products.offers.{$product->id}.{$sortBy}.{$sortOrder}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($product, $sortBy, $sortOrder) {
            $offers = $product->activeOffers()
                ->with(['seller:id,business_name,nickname,city,role,seller_score,seller_review_count'])
                ->inStock()
                ->notExpired()
                ->orderBy($sortBy, $sortOrder)
                ->get()
                ->map(function ($offer) use ($product) {
                    $campaigns = Campaign::active()
                        ->where('seller_id', $offer->seller->id)
                        ->where(function ($q) use ($product) {
                            $q->where('type', 'store_discount')
                                ->orWhere(function ($q2) use ($product) {
                                    $q2->where('type', 'product_discount')
                                        ->where('product_id', $product->id);
                                })
                                ->orWhere(function ($q2) use ($product) {
                                    $q2->where('type', 'brand_discount')
                                        ->where('brand', $product->brand);
                                });
                        })
                        ->get()
                        ->map(function ($campaign) {
                            return [
                                'id' => $campaign->id,
                                'name' => $campaign->name,
                                'type' => $campaign->type,
                                'discount_rate' => $campaign->discount_rate,
                                'min_purchase_amount' => $campaign->min_purchase_amount,
                                'min_quantity' => $campaign->min_quantity,
                                'starts_at' => $campaign->starts_at,
                                'ends_at' => $campaign->ends_at,
                            ];
                        });

                    return [
                        'id' => $offer->id,
                        'price' => $offer->price,
                        'stock' => $offer->stock,
                        'expiry_date' => $offer->expiry_date?->format('Y-m-d'),
                        'batch_number' => $offer->batch_number,
                        'seller' => [
                            'id' => $offer->seller->id,
                            'business_name' => $offer->seller->business_name,
                            'nickname' => $offer->seller->nickname,
                            'city' => $offer->seller->city,
                            'role' => $offer->seller->role,
                            'seller_score' => $offer->seller->seller_score,
                            'seller_review_count' => $offer->seller->seller_review_count,
                        ],
                        'campaigns' => $campaigns,
                    ];
                });

            return response()->json([
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'brand' => $product->brand,
                    'psf' => $product->psf,
                    'image' => $product->image,
                    'image_url' => $product->image_url,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'slug' => $product->category->slug,
                    ] : null,
                ],
                'offers' => $offers,
                'offers_count' => $offers->count(),
                'lowest_price' => $offers->min('price'),
                'highest_price' => $offers->max('price'),
            ]);
        });
    }

    /**
     * Search products by name, barcode, or brand via Meilisearch (Laravel Scout).
     *
     * Barcode lookups (pure digits, length >= 8) are executed as exact filter
     * matches on the `barcode` attribute for sub-millisecond response.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $query = trim($request->input('q'));
        $perPage = (int) $request->input('per_page', 15);

        $isBarcodeLookup = preg_match('/^\d{8,}$/', $query) === 1;

        $hydrate = function ($builder) {
            return $builder
                ->withCount(['activeOffers as offers_count'])
                ->withMin('activeOffers as lowest_price', 'price');
        };

        if ($isBarcodeLookup) {
            $paginator = Product::search('')
                ->where('barcode', $query)
                ->where('is_active', true)
                ->query($hydrate)
                ->paginate($perPage);
        } else {
            $paginator = Product::search($query)
                ->where('is_active', true)
                ->query($hydrate)
                ->paginate($perPage);
        }

        return response()->json([
            'products' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'meta' => [
                'barcode_lookup' => $isBarcodeLookup,
            ],
        ]);
    }
}
