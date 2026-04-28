<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SellerScoreService
{
    /**
     * Calculate and persist seller score.
     * Returns null for new sellers (no reviews AND <5 orders).
     */
    public function calculateScore(User $seller): ?float
    {
        $reviewData = $this->getReviewScore($seller->id);
        $totalOrders = $this->getTotalOrderCount($seller->id);
        $reviewCount = $reviewData['count'];

        // New seller: no reviews AND fewer than 5 orders
        if ($reviewCount === 0 && $totalOrders < 5) {
            $seller->update([
                'seller_score' => null,
                'seller_total_orders' => $totalOrders,
                'seller_review_count' => 0,
            ]);

            return null;
        }

        $shippingScore = $this->getShippingSpeedScore($seller->id);
        $cancellationScore = $this->getCancellationScore($seller->id);

        if ($reviewCount > 0) {
            // Full formula: 60% review + 25% shipping + 15% cancellation
            $score = round(
                ($reviewData['score'] * 0.60) +
                ($shippingScore * 0.25) +
                ($cancellationScore * 0.15),
                1
            );
        } else {
            // No reviews but has orders: 62.5% shipping + 37.5% cancellation
            $score = round(
                ($shippingScore * 0.625) +
                ($cancellationScore * 0.375),
                1
            );
        }

        $score = min(10.0, max(0.0, $score));

        $seller->update([
            'seller_score' => $score,
            'seller_total_orders' => $totalOrders,
            'seller_review_count' => $reviewCount,
        ]);

        return $score;
    }

    /**
     * Get review-based score (0-10 scale) and count.
     */
    public function getReviewScore(int $sellerId): array
    {
        $reviews = Review::forSeller($sellerId)->approved();
        $count = $reviews->count();

        if ($count === 0) {
            return ['score' => 0.0, 'count' => 0];
        }

        $avgRating = (float) $reviews->avg('rating');

        return [
            'score' => round($avgRating * 2, 1), // 5-star → 10-point
            'count' => $count,
        ];
    }

    /**
     * Calculate shipping speed score based on average hours between
     * order creation and shipped_at for this seller's order items.
     */
    public function getShippingSpeedScore(int $sellerId): float
    {
        $avgHours = OrderItem::where('seller_id', $sellerId)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('orders.shipped_at')
            ->whereIn('orders.status', ['shipped', 'delivered'])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, orders.created_at, orders.shipped_at)) as avg_hours'))
            ->value('avg_hours');

        if ($avgHours === null) {
            return 7.0; // Default for sellers with no shipped orders
        }

        return $this->hoursToScore((float) $avgHours);
    }

    /**
     * Calculate cancellation rate score.
     */
    public function getCancellationScore(int $sellerId): float
    {
        $stats = OrderItem::where('seller_id', $sellerId)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN orders.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            )
            ->first();

        $total = (int) $stats->total;
        if ($total === 0) {
            return 10.0;
        }

        $rate = ((int) $stats->cancelled / $total) * 100;

        return $this->cancellationRateToScore($rate);
    }

    /**
     * Get total order count for seller.
     */
    private function getTotalOrderCount(int $sellerId): int
    {
        return OrderItem::where('seller_id', $sellerId)
            ->distinct('order_id')
            ->count('order_id');
    }

    /**
     * Convert average shipping hours to a 0-10 score.
     */
    private function hoursToScore(float $hours): float
    {
        return match (true) {
            $hours <= 12 => 10.0,
            $hours <= 24 => 9.0,
            $hours <= 48 => 8.0,
            $hours <= 72 => 7.0,
            $hours <= 96 => 5.0,
            default      => 3.0,
        };
    }

    /**
     * Convert cancellation rate percentage to a 0-10 score.
     */
    private function cancellationRateToScore(float $rate): float
    {
        return match (true) {
            $rate == 0   => 10.0,
            $rate <= 3   => 9.0,
            $rate <= 5   => 8.0,
            $rate <= 10  => 6.0,
            $rate <= 20  => 4.0,
            default      => 2.0,
        };
    }
}
