<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;

/**
 * Marka API Controller - Marka endpoint'lerini yönetir
 */
class BrandController extends Controller
{
    public function __construct(
        private readonly BrandService $brandService
    ) {}

    /**
     * Tüm aktif markaları listeler
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $brands = $this->brandService->getActiveBrands();

        return response()->json([
            'status' => 'success',
            'data' => $this->brandService->formatForApi($brands),
        ]);
    }

    /**
     * Öne çıkan markaları listeler (ana sayfa için)
     *
     * @return JsonResponse
     */
    public function featured(): JsonResponse
    {
        $brands = $this->brandService->getFeaturedBrands(12);

        return response()->json([
            'status' => 'success',
            'data' => $this->brandService->formatForApi($brands),
        ]);
    }

    /**
     * Marka detayını getirir
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $brand = $this->brandService->getBySlug($slug);

        if (!$brand) {
            return response()->json([
                'status' => 'error',
                'message' => 'Marka bulunamadı',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->brandService->formatDetailForApi($brand),
        ]);
    }
}
