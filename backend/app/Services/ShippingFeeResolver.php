<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

/**
 * Satıcı bazlı kargo ücreti çözümleyicisi.
 *
 * Öncelik sırası (T0 - 2026-05, kullanıcı kuralı):
 *   1. Satıcının `users.default_shipping_fee` (override)
 *   2. Global `Setting::shipping.flat_rate` (default ₺29.90)
 *
 * Ücretsiz kargo eşiği için aynı öncelik:
 *   1. Satıcının `users.free_shipping_threshold`
 *   2. Global `Setting::shipping.free_threshold` (default ₺2500)
 *
 * Eşik 0 veya negatif ise ücretsiz kargo devre dışı sayılır.
 */
class ShippingFeeResolver
{
    /**
     * Belirli bir satıcı + alt toplam için kargo ücretini hesapla.
     *
     * @param  User  $seller  Satıcı (User modeli)
     * @param  float  $sellerSubtotal  Satıcının kalemlerinin KDV dahil toplamı
     * @return array{
     *     fee: float,
     *     base_fee: float,
     *     threshold: float,
     *     is_free: bool,
     *     remaining_for_free: float,
     *     uses_seller_override: bool
     * }
     */
    public function resolveForSeller(User $seller, float $sellerSubtotal): array
    {
        $globalFee = (float) Setting::getValue('shipping.flat_rate', 29.90);
        $globalThreshold = (float) Setting::getValue('shipping.free_threshold', 2500);

        $sellerFee = $seller->default_shipping_fee !== null
            ? (float) $seller->default_shipping_fee
            : null;

        $sellerThreshold = $seller->free_shipping_threshold !== null
            ? (float) $seller->free_shipping_threshold
            : null;

        $effectiveFee = $sellerFee ?? $globalFee;
        $effectiveThreshold = $sellerThreshold ?? $globalThreshold;

        $isFree = $effectiveThreshold > 0 && $sellerSubtotal >= $effectiveThreshold;

        $remaining = ($effectiveThreshold > 0 && ! $isFree)
            ? max(0.0, $effectiveThreshold - $sellerSubtotal)
            : 0.0;

        return [
            'fee' => $isFree ? 0.0 : round($effectiveFee, 2),
            'base_fee' => round($effectiveFee, 2),
            'threshold' => round($effectiveThreshold, 2),
            'is_free' => $isFree,
            'remaining_for_free' => round($remaining, 2),
            'uses_seller_override' => $sellerFee !== null || $sellerThreshold !== null,
        ];
    }

    /**
     * Sepet kalemlerini satıcı bazında grupla ve toplam kargo ücretini hesapla.
     *
     * @param  iterable<int, array{seller_id: int, subtotal: float}>  $sellerSubtotals
     *                                                                                  Her satıcı için (seller_id, subtotal) tuple listesi.
     * @return array{
     *     total: float,
     *     per_seller: array<int, array{
     *         seller_id: int,
     *         subtotal: float,
     *         fee: float,
     *         base_fee: float,
     *         threshold: float,
     *         is_free: bool,
     *         remaining_for_free: float,
     *         uses_seller_override: bool
     *     }>
     * }
     */
    public function resolveForCart(iterable $sellerSubtotals): array
    {
        $perSeller = [];
        $total = 0.0;

        $sellerIds = collect($sellerSubtotals)->pluck('seller_id')->unique()->all();
        $sellers = User::whereIn('id', $sellerIds)->get()->keyBy('id');

        foreach ($sellerSubtotals as $entry) {
            $sellerId = (int) $entry['seller_id'];
            $subtotal = (float) $entry['subtotal'];

            $seller = $sellers->get($sellerId);
            if (! $seller) {
                // Satıcı bulunamadıysa global default uygula.
                $seller = new User;
            }

            $resolved = $this->resolveForSeller($seller, $subtotal);
            $perSeller[$sellerId] = array_merge(['seller_id' => $sellerId, 'subtotal' => round($subtotal, 2)], $resolved);
            $total += $resolved['fee'];
        }

        return [
            'total' => round($total, 2),
            'per_seller' => $perSeller,
        ];
    }
}
