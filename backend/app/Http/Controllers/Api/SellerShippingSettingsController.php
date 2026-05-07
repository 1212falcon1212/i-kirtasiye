<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateSellerShippingSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Satıcı bazlı kargo ayarları API uç noktaları.
 *
 * Satıcı, sabit kargo ücretini ve ücretsiz kargo eşiğini kendi profili
 * üzerinden override edebilir. NULL değerlerde global default uygulanır.
 */
class SellerShippingSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Yetkilendirme gerekli.'], 401);
        }

        if (! $user->isSeller() && ! $user->isRetailer()) {
            return response()->json([
                'message' => 'Bu uç nokta yalnızca satıcılar içindir.',
            ], 403);
        }

        $globalFee = (float) Setting::getValue('shipping.flat_rate', 29.90);
        $globalThreshold = (float) Setting::getValue('shipping.free_threshold', 2500);

        $sellerFee = $user->default_shipping_fee !== null
            ? (float) $user->default_shipping_fee
            : null;

        $sellerThreshold = $user->free_shipping_threshold !== null
            ? (float) $user->free_shipping_threshold
            : null;

        return response()->json([
            'default_shipping_fee' => $sellerFee,
            'free_shipping_threshold' => $sellerThreshold,
            'effective_shipping_fee' => $sellerFee ?? $globalFee,
            'effective_threshold' => $sellerThreshold ?? $globalThreshold,
            'global' => [
                'shipping_fee' => $globalFee,
                'free_threshold' => $globalThreshold,
            ],
            'uses_override' => $sellerFee !== null || $sellerThreshold !== null,
        ]);
    }

    public function update(UpdateSellerShippingSettingsRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Yetkilendirme gerekli.'], 401);
        }

        $payload = $request->payload();

        $user->update([
            'default_shipping_fee' => $payload['default_shipping_fee'],
            'free_shipping_threshold' => $payload['free_shipping_threshold'],
        ]);

        $user->refresh();

        $globalFee = (float) Setting::getValue('shipping.flat_rate', 29.90);
        $globalThreshold = (float) Setting::getValue('shipping.free_threshold', 2500);

        return response()->json([
            'message' => 'Kargo ayarlarınız güncellendi.',
            'default_shipping_fee' => $user->default_shipping_fee !== null ? (float) $user->default_shipping_fee : null,
            'free_shipping_threshold' => $user->free_shipping_threshold !== null ? (float) $user->free_shipping_threshold : null,
            'effective_shipping_fee' => $user->default_shipping_fee !== null ? (float) $user->default_shipping_fee : $globalFee,
            'effective_threshold' => $user->free_shipping_threshold !== null ? (float) $user->free_shipping_threshold : $globalThreshold,
            'global' => [
                'shipping_fee' => $globalFee,
                'free_threshold' => $globalThreshold,
            ],
            'uses_override' => $user->default_shipping_fee !== null || $user->free_shipping_threshold !== null,
        ]);
    }
}
