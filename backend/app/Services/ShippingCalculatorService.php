<?php

namespace App\Services;

use App\Models\ShippingRate;
use App\Models\FreeShippingRule;
use App\Models\Setting;

class ShippingCalculatorService
{
    /**
     * Ürünlerin toplam desisini hesapla
     */
    public function calculateTotalDesi(array $items): float
    {
        $totalDesi = 0;

        foreach ($items as $item) {
            $desi = $item['desi'] ?? 0;
            $quantity = $item['quantity'] ?? 1;
            $totalDesi += $desi * $quantity;
        }

        // Minimum 1 desi
        return max($totalDesi, 1);
    }

    /**
     * Boyutlardan desi hesapla
     * Formül: (En x Boy x Yükseklik) / 3000
     */
    public function calculateDesiFromDimensions(float $width, float $height, float $depth): float
    {
        return ($width * $height * $depth) / 3000;
    }

    /**
     * Tüm aktif kargo seçeneklerini fiyatlarıyla getir
     */
    public function getShippingOptions(float $totalDesi, float $orderAmount): array
    {
        $providers = ShippingRate::getActiveProviders();
        $options = [];

        foreach ($providers as $provider) {
            $price = ShippingRate::getPriceForDesi($provider['code'], $totalDesi);

            if ($price === null) {
                continue;
            }

            // Ücretsiz kargo kontrolü
            $isFree = FreeShippingRule::isEligible($orderAmount, $totalDesi, $provider['code']);
            $remainingForFree = FreeShippingRule::getRemainingForFree($orderAmount, $totalDesi, $provider['code']);

            $options[] = [
                'provider' => $provider['code'],
                'name' => $provider['name'],
                'price' => $isFree ? 0 : $price,
                'original_price' => $price,
                'formatted_price' => $isFree ? 'Ücretsiz' : '₺' . number_format($price, 2, ',', '.'),
                'is_free' => $isFree,
                'remaining_for_free' => $remainingForFree,
                'remaining_for_free_formatted' => $remainingForFree
                    ? '₺' . number_format($remainingForFree, 2, ',', '.') . ' daha harcayın, ücretsiz kargo!'
                    : null,
            ];
        }

        // Fiyata göre sırala (ücretsiz olanlar önce)
        usort($options, function ($a, $b) {
            if ($a['is_free'] && !$b['is_free'])
                return -1;
            if (!$a['is_free'] && $b['is_free'])
                return 1;
            return $a['price'] <=> $b['price'];
        });

        return $options;
    }

    /**
     * Varsayılan kargo ücretini getir (en ucuz aktif seçenek)
     */
    public function getDefaultShippingCost(float $totalDesi, float $orderAmount): array
    {
        $options = $this->getShippingOptions($totalDesi, $orderAmount);

        if (empty($options)) {
            // Fallback: Sabit kargo ücreti
            $flatRate = (float) Setting::getValue('shipping.flat_rate', 29.90);
            $freeThreshold = (float) Setting::getValue('shipping.free_threshold', 500);

            $isFree = $orderAmount >= $freeThreshold;

            return [
                'provider' => 'default',
                'name' => 'Standart Kargo',
                'price' => $isFree ? 0 : $flatRate,
                'is_free' => $isFree,
            ];
        }

        return $options[0]; // En ucuz seçenek
    }

    /**
     * Belirli kargo firması için fiyat getir
     */
    public function getShippingCostForProvider(string $provider, float $totalDesi, float $orderAmount): ?array
    {
        $options = $this->getShippingOptions($totalDesi, $orderAmount);

        foreach ($options as $option) {
            if ($option['provider'] === $provider) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Demo desi değerleri oluştur
     */
    public static function generateRandomDesi(): float
    {
        // Kırtasiye paketleri genellikle 0.1 - 3 desi arası
        $types = [
            'small' => [0.1, 0.5],   // Küçük kırtasiye paketi
            'medium' => [0.5, 1.5],  // Orta boy
            'large' => [1.5, 3.0],   // Büyük kutu
            'box' => [3.0, 10.0],    // Koli
        ];

        $type = array_rand($types);
        $range = $types[$type];

        return round($range[0] + (mt_rand() / mt_getrandmax()) * ($range[1] - $range[0]), 2);
    }
}
