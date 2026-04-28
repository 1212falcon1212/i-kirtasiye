<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    public function cities(): JsonResponse
    {
        $cities = Cache::remember('locations.cities', 86400, function () {
            return City::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'slug']);
        });

        return response()->json(['success' => true, 'data' => $cities]);
    }

    public function districts(City $city): JsonResponse
    {
        $districts = Cache::remember("locations.districts.{$city->id}", 86400, function () use ($city) {
            return $city->districts()
                ->orderBy('name')
                ->get(['id', 'city_id', 'name', 'slug']);
        });

        return response()->json([
            'success' => true,
            'city' => ['id' => $city->id, 'code' => $city->code, 'name' => $city->name],
            'data' => $districts,
        ]);
    }
}
